<?php

namespace App\Http\Controllers;

use App\Mail\ContactConfirmedMail;
use App\Mail\NewContactRequestMail;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactRequestController extends Controller
{
    // Fixed option sets mirrored on the public form.
    private const HOW_FOUND      = ['passaparola', 'social', 'miodottore'];
    private const CONTACT_METHOD = ['whatsapp_tel_mail', 'social', 'miodottore'];
    private const DAYS           = ['lun', 'mar', 'mer', 'gio', 'ven', 'sab'];

    // ── Public: Scheda Primo Contatto ──────────────────────────────────────────

    public function create(): Response
    {
        return Inertia::render('Booking/PrimoContatto');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'surname'          => 'required|string|max:100',
            'email'            => 'required|email|max:255',
            'phone'            => 'nullable|string|max:30',
            'how_found'        => 'nullable|array',
            'how_found.*'      => ['string', Rule::in(self::HOW_FOUND)],
            'contact_method'   => 'nullable|array',
            'contact_method.*' => ['string', Rule::in(self::CONTACT_METHOD)],
            'availability'     => 'nullable|array',
            'availability.*'   => ['string', 'regex:/^(' . implode('|', self::DAYS) . ')\|([01]\d|2[0-3]):00$/'],
            'notes'            => 'nullable|string|max:1000',
        ]);

        $contact = ContactRequest::create([
            ...$validated,
            'status'        => 'pending',
            'confirm_token' => Str::random(48),
        ]);

        // Route to the triage hub (Sara): in-app notification + email.
        if ($triage = ContactRequest::triageUser()) {
            Notification::send(
                $triage->id,
                'contact_request_new',
                'Nuova richiesta di primo contatto',
                "{$contact->fullName()} ha inviato una scheda di primo contatto.",
                ['contact_request_id' => $contact->id],
            );

            try {
                Mail::to($triage->email)->send(new NewContactRequestMail($contact));
            } catch (\Exception $e) {
                \Log::error('NewContactRequest mail failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Richiesta inviata! Ti contatteremo a breve per fissare il primo colloquio.');
    }

    // ── Professional inbox ─────────────────────────────────────────────────────

    public function inbox(Request $request): Response
    {
        $user     = $request->user();
        $isTriage = $this->isTriage($user);

        $query = ContactRequest::with(['assignee', 'acceptedBy', 'patient'])
            ->orderByRaw("CASE status
                WHEN 'pending' THEN 0
                WHEN 'assigned' THEN 1
                WHEN 'accepted' THEN 2
                WHEN 'rejected' THEN 3
                ELSE 4 END")
            ->orderByDesc('created_at');

        if ($isTriage) {
            // Triage hub sees every still-open request, plus its own resolved history.
            $query->where(fn ($q) => $q
                ->whereIn('status', ['pending', 'assigned'])
                ->orWhere('accepted_by', $user->id));
        } else {
            // Colleagues only see what was routed to them, or that they handled.
            $query->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('assigned_to', $user->id)->where('status', 'assigned'))
                ->orWhere('accepted_by', $user->id));
        }

        $requests = $query->get()->map(fn ($c) => $this->present($c));

        // Colleagues Sara can route a request to.
        $colleagues = $isTriage
            ? User::whereHas('professionalProfile')
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->values()
            : [];

        return Inertia::render('ContactRequests/Inbox', [
            'requests'   => $requests,
            'colleagues' => $colleagues,
            'is_triage'  => $isTriage,
        ]);
    }

    // ── Sara routes a request to a colleague ────────────────────────────────────

    public function assign(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        abort_unless($this->isTriage($request->user()), 403);
        abort_unless(in_array($contactRequest->status, ['pending', 'assigned']), 422);

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::notIn([$request->user()->id]),
            ],
        ]);

        $contactRequest->update([
            'status'      => 'assigned',
            'assigned_to' => $validated['assigned_to'],
        ]);

        Notification::send(
            $validated['assigned_to'],
            'contact_request_assigned',
            'Richiesta di primo contatto assegnata',
            "{$contactRequest->fullName()} ti è stato assegnato per il primo colloquio.",
            ['contact_request_id' => $contactRequest->id],
        );

        return back()->with('success', 'Richiesta smistata al collega.');
    }

    // ── A colleague (or Sara) accepts and fixes the appointment ─────────────────

    public function accept(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canHandle($user, $contactRequest), 403);
        abort_unless(in_array($contactRequest->status, ['pending', 'assigned']), 422);

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
        ]);

        // Soft-check the chosen slot falls within the client's stated availability.
        $slot = strtolower(self::DAYS[Carbon::parse($validated['date'])->isoWeekday() - 1] ?? '')
            . '|' . $validated['time'];
        if (! empty($contactRequest->availability) && ! in_array($slot, $contactRequest->availability)) {
            return back()->withErrors(['time' => 'Lo slot scelto non rientra nelle disponibilità indicate dal cliente.']);
        }

        // Find or create the patient record from the contact's anagrafica.
        $patient = Patient::where('email', $contactRequest->email)->first();
        if (! $patient) {
            $patient = Patient::create([
                'first_name' => $contactRequest->name,
                'last_name'  => $contactRequest->surname,
                'email'      => $contactRequest->email,
                'phone'      => $contactRequest->phone,
                'created_by' => $user->id,
                'is_active'  => true,
            ]);
        }
        if (! $patient->professionals()->where('user_id', $user->id)->exists()) {
            $patient->professionals()->attach($user->id);
        }

        $startAt  = Carbon::parse("{$validated['date']} {$validated['time']}");
        $duration = $user->professionalProfile?->session_duration_minutes ?? 50;

        $appointment = Appointment::create([
            'user_id'     => $user->id,
            'patient_id'  => $patient->id,
            'title'       => "Primo colloquio – {$contactRequest->fullName()}",
            'description' => $contactRequest->notes,
            'start_at'    => $startAt,
            'end_at'      => $startAt->copy()->addMinutes($duration),
            'type'        => 'session',
            'status'      => 'confirmed',
            'color'       => '#10b981',
        ]);

        $contactRequest->update([
            'status'         => 'accepted',
            'accepted_by'    => $user->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $appointment->id,
        ]);

        try {
            Mail::to($contactRequest->email)->send(
                new ContactConfirmedMail($contactRequest->fresh(['acceptedBy']), $startAt)
            );
        } catch (\Exception $e) {
            \Log::error('ContactConfirmed mail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Appuntamento fissato e cliente avvisato.');
    }

    // ── A colleague declines: request bounces back to the triage hub ────────────

    public function reject(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($contactRequest->assigned_to === $user->id && $contactRequest->status === 'assigned', 403);

        $contactRequest->update([
            'status'      => 'pending',
            'assigned_to' => null,
        ]);

        if ($triage = ContactRequest::triageUser()) {
            Notification::send(
                $triage->id,
                'contact_request_rejected',
                'Richiesta di primo contatto rifiutata',
                "{$user->name} ha rifiutato la richiesta di {$contactRequest->fullName()}.",
                ['contact_request_id' => $contactRequest->id],
            );
        }

        return back()->with('success', 'Richiesta rifiutata e rimandata allo smistamento.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    private function isTriage(User $user): bool
    {
        return ContactRequest::triageUser()?->id === $user->id;
    }

    private function canHandle(User $user, ContactRequest $contact): bool
    {
        return $this->isTriage($user) || $contact->assigned_to === $user->id;
    }

    private function present(ContactRequest $c): array
    {
        return [
            'id'             => $c->id,
            'name'           => $c->name,
            'surname'        => $c->surname,
            'email'          => $c->email,
            'phone'          => $c->phone,
            'how_found'      => $c->how_found ?? [],
            'contact_method' => $c->contact_method ?? [],
            'availability'   => $c->availability ?? [],
            'notes'          => $c->notes,
            'status'         => $c->status,
            'assigned_to'    => $c->assigned_to,
            'assignee'       => $c->assignee?->name,
            'accepted_by'    => $c->acceptedBy?->name,
            'patient_id'     => $c->patient_id,
            'created_at'     => $c->created_at->diffForHumans(),
        ];
    }
}
