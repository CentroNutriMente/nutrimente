<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupEnrollmentRequest;
use App\Models\GroupMeeting;
use App\Models\Notification;
use App\Models\ProfessionalProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GroupEnrollmentController extends Controller
{
    // Form pubblico generico: l'utente sceglie il gruppo d'interesse.
    public function create(): Response
    {
        return Inertia::render('Booking/GroupEnrollment', [
            'groups'    => $this->openGroups(),
            'preselect' => null,
            'source'    => 'form',
        ])->withViewData(['meta' => [
            'title'       => 'Iscriviti a un gruppo — '.config('app.name'),
            'description' => 'Richiedi l\'iscrizione a un gruppo di parola: compila il modulo e verrai ricontattato con tutte le informazioni.',
            'canonical'   => route('groups.public.create'),
            'image'       => asset('logo.jpeg'),
        ]]);
    }

    // Pagina conclusiva di conferma dopo l'invio della richiesta.
    public function done(Request $request): Response
    {
        return Inertia::render('Booking/GroupEnrollmentDone', [
            'name' => $request->session()->get('enrolled_name'),
        ])->withViewData(['meta' => [
            'title'  => 'Richiesta inviata — '.config('app.name'),
            'robots' => 'noindex,nofollow',
        ]]);
    }

    // Form pre-compilato dal QR del volantino (token del gruppo).
    public function show(string $token): Response
    {
        $group = Group::where('public_token', $token)->firstOrFail();

        return Inertia::render('Booking/GroupEnrollment', [
            // Il gruppo del QR è sempre incluso, anche se non più fra quelli aperti.
            'groups'    => $this->openGroups($group),
            'preselect' => ['id' => $group->id, 'name' => $group->name, 'edition' => $group->edition],
            'source'    => 'qr',
        ])->withViewData(['meta' => [
            'title'  => 'Iscriviti a un gruppo — '.config('app.name'),
            // URL con token da QR: non deve finire nell'indice.
            'robots' => 'noindex,nofollow',
        ]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'email'           => ['required', 'email', 'max:160'],
            'phone'           => ['required', 'string', 'max:40'],
            'codice_fiscale'  => ['required', 'string', 'regex:/^[A-Za-z0-9]{16}$/'],
            'group_id'        => ['nullable', 'exists:support_groups,id'],
            'how_heard'       => ['nullable', 'string', 'max:120'],
            'privacy_consent' => ['accepted'],
            'source'          => ['nullable', Rule::in(['form', 'qr'])],
        ]);

        $group = ! empty($validated['group_id']) ? Group::find($validated['group_id']) : null;

        $enrollment = GroupEnrollmentRequest::create([
            'group_id'        => $validated['group_id'] ?? null,
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'],
            'codice_fiscale'  => strtoupper($validated['codice_fiscale']),
            'how_heard'       => $validated['how_heard'] ?? null,
            'privacy_consent' => true,
            'source'          => $validated['source'] ?? 'form',
            'status'          => 'da_contattare',
        ]);

        // Notifica in dashboard: conduttrice del gruppo, o la founder come fallback.
        $recipientId = $group?->leader_user_id
            ?? ProfessionalProfile::where('is_founder', true)->value('user_id');

        if ($recipientId) {
            Notification::send(
                $recipientId,
                'group_enrollment_new',
                'Nuova iscrizione a un gruppo',
                $group
                    ? "{$enrollment->name} ha richiesto l'iscrizione al gruppo «{$group->name}»."
                    : "{$enrollment->name} ha inviato una richiesta d'iscrizione a un gruppo.",
                ['group_id' => $group?->id, 'enrollment_id' => $enrollment->id],
            );
        }

        $firstName = trim(explode(' ', $validated['name'])[0]);

        return redirect()->route('groups.public.done')->with('enrolled_name', $firstName);
    }

    /**
     * Gruppi aperti con tutte le informazioni utili a capire se si può partecipare:
     * calendario degli incontri, quota, dove/quando, posti rimasti.
     */
    private function openGroups(?Group $ensure = null): array
    {
        $groups = Group::query()
            ->where(fn ($q) => $q
                ->whereIn('status', ['attivo', 'in_partenza'])
                ->when($ensure, fn ($q2) => $q2->orWhere('id', $ensure->id)))
            ->withCount('participants')
            ->with([
                'leader:id,name',
                // Solo gli incontri non ancora passati: il calendario serve a decidere.
                'meetings' => fn ($q) => $q->where('scheduled_at', '>=', now()->startOfDay()),
            ])
            ->orderBy('name')
            ->get();

        return $groups->map(fn (Group $g) => [
            'id'              => $g->id,
            'name'            => $g->name,
            'edition'         => $g->edition,
            'description'     => $g->description,
            'status'          => $g->status,
            'leader'          => $g->leader?->name,
            'cadence'         => $g->cadence,
            'modality'        => $g->modality,
            'location'        => $g->modality === 'online' ? null : $g->location,
            'capacity'        => $g->capacity,
            'available_seats' => $g->available_seats,
            'price_label'     => $g->price_label,
            'duration_label'  => $g->duration_label,
            'session_minutes' => $g->session_duration_minutes,
            'next_meeting_at' => $g->next_meeting_at?->format('Y-m-d\TH:i:s'),
            'meetings'        => $g->meetings->map(fn (GroupMeeting $m) => [
                'id'       => $m->id,
                'title'    => $m->title,
                'datetime' => $m->scheduled_at->format('Y-m-d\TH:i:s'),
                'duration' => $m->duration_minutes,
            ])->values()->all(),
        ])->all();
    }
}
