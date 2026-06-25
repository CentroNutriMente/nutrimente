<?php

namespace App\Http\Controllers;

use App\Mail\ContactConfirmedMail;
use App\Mail\NewContactRequestMail;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\ProfessionalProfile;
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
    private const CONTACT_METHOD = ['whatsapp', 'telefono'];
    private const DAYS           = ['lun', 'mar', 'mer', 'gio', 'ven', 'sab'];

    // ── Public: Sara-focused landing page (site home) ───────────────────────────

    public function home()
    {
        // Authenticated users skip the landing and go to their area.
        if (auth()->check()) {
            $professionalRoles = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];

            return auth()->user()->hasAnyRole($professionalRoles)
                ? redirect()->route('dashboard')
                : redirect()->route('patient.dashboard');
        }

        $profile = ProfessionalProfile::where('is_founder', true)
            ->with('user')
            ->firstOrFail();

        // curriculum is not cast on the model — decode defensively.
        $curriculum = is_array($profile->curriculum)
            ? $profile->curriculum
            : (json_decode($profile->curriculum ?? '[]', true) ?: []);

        return Inertia::render('Landing', [
            'sara' => [
                'name'                     => $profile->user->name,
                'title'                    => $profile->title,
                'category'                 => $profile->category,
                'bio'                      => $profile->bio,
                'photo'                    => $profile->user->profile_photo_url,
                'slug'                     => $profile->slug,
                'phone'                    => $profile->phone,
                'session_duration_minutes' => $profile->session_duration_minutes,
                'session_price'            => $profile->session_price,
                // Esclude l'area "Psicologia oncologica" (titolo non posseduto;
                // "Supporto nei percorsi di malattia" resta a coprire l'ambito).
                'areas'                    => collect($curriculum['aree'] ?? [])
                    ->reject(fn ($a) => str_contains(mb_strtolower((string) $a), 'oncolog'))
                    ->values()->all(),
            ],
        ]);
    }

    // ── Public: choose a professional (Centro NutriMente team page, kept for later) ──

    public function index(): Response
    {
        $professionals = User::with(['professionalProfile', 'roles'])
            ->whereHas('professionalProfile', fn ($q) => $q->where('is_bookable', true))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->get()
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'slug'       => $u->professionalProfile?->slug,
                'role'       => $u->getRoleNames()->first(),
                'category'   => $u->professionalProfile?->category,
                'title'      => $u->professionalProfile?->title,
                'bio'        => $u->professionalProfile?->bio
                    ? Str::limit($u->professionalProfile->bio, 200)
                    : null,
                'photo'      => $u->profile_photo_url,
                'is_founder' => (bool) $u->professionalProfile?->is_founder,
            ])
            ->sortByDesc('is_founder')
            ->values();

        return Inertia::render('Booking/Index', compact('professionals'));
    }

    // ── Public: Scheda Primo Contatto for a given professional ──────────────────

    public function create(string $slug): Response
    {
        $profile = ProfessionalProfile::where('slug', $slug)
            ->where('is_bookable', true)
            ->with('user')
            ->firstOrFail();

        return Inertia::render('Booking/PrimoContatto', [
            'professional' => [
                'name'     => $profile->user->name,
                'slug'     => $slug,
                'title'    => $profile->title,
                'category' => $profile->category,
                'photo'    => $profile->user->profile_photo_url,
            ],
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $profile = ProfessionalProfile::where('slug', $slug)
            ->where('is_bookable', true)
            ->with('user')
            ->firstOrFail();

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
            'professional_id' => $profile->user_id,
            'status'          => 'pending',
            'confirm_token'   => Str::random(48),
        ]);

        // Notify the chosen professional: in-app notification + email.
        Notification::send(
            $profile->user_id,
            'contact_request_new',
            'Nuova richiesta di primo contatto',
            "{$contact->fullName()} ti ha inviato una scheda di primo contatto.",
            ['contact_request_id' => $contact->id],
        );

        try {
            Mail::to($profile->user->email)->send(new NewContactRequestMail($contact->load('professional')));
        } catch (\Exception $e) {
            \Log::error('NewContactRequest mail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Richiesta inviata! Il professionista ti contatterà a breve per fissare il primo colloquio.');
    }

    // ── Professional inbox: only own requests ───────────────────────────────────

    public function inbox(Request $request): Response
    {
        $requests = ContactRequest::with(['patient'])
            ->where('professional_id', $request->user()->id)
            ->orderByRaw("CASE status
                WHEN 'pending' THEN 0
                WHEN 'accepted' THEN 1
                WHEN 'rejected' THEN 2
                ELSE 3 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => $this->present($c));

        return Inertia::render('ContactRequests/Inbox', [
            'requests' => $requests,
        ]);
    }

    // ── Professional accepts and fixes the appointment ──────────────────────────

    public function accept(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($contactRequest->professional_id === $user->id, 403);
        abort_unless($contactRequest->status === 'pending', 422);

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
        ]);

        // Il professionista è libero di fissare qualsiasi giorno/orario, a prescindere
        // dalle disponibilità indicate dal cliente (che restano solo come indicazione).

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

    // ── Professional declines the request ───────────────────────────────────────

    public function reject(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        abort_unless($contactRequest->professional_id === $request->user()->id, 403);
        abort_unless($contactRequest->status === 'pending', 422);

        $contactRequest->update(['status' => 'rejected']);

        return back()->with('success', 'Richiesta rifiutata.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

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
            'patient_id'     => $c->patient_id,
            'created_at'     => $c->created_at->diffForHumans(),
        ];
    }
}
