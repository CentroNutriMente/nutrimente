<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupEnrollmentRequest;
use App\Models\GroupParticipant;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientRegistrar;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupController extends Controller
{
    private function categories(): array
    {
        return collect(config('groups.categories'))
            ->map(fn ($c, $key) => array_merge($c, ['key' => $key]))
            ->values()->all();
    }

    public function index(Request $request): Response
    {
        $filter = $request->query('status', 'tutti'); // tutti|attivo|in_partenza|concluso
        $search = trim((string) $request->query('q', ''));

        $groups = Group::query()
            ->withCount('participants')
            ->with('leader:id,name')
            ->when($filter !== 'tutti', fn ($q) => $q->where('status', $filter))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Group $g) => $this->present($g));

        return Inertia::render('Groups/Index', [
            'groups'     => $groups,
            'categories' => $this->categories(),
            'filters'    => ['status' => $filter, 'q' => $search],
            'statuses'   => config('groups.statuses'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Groups/Create', [
            'categories' => $this->categories(),
            'leaders'    => $this->leaders(),
            'cadences'   => config('groups.cadences'),
            'modalities' => config('groups.modalities'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);
        $data['created_by'] = $request->user()->id;

        $group = Group::create($data);

        return redirect()->route('groups.show', $group)->with('flash', ['banner' => 'Gruppo creato.']);
    }

    public function show(Group $group): Response
    {
        $group->load([
            'leader:id,name',
            'participants' => fn ($q) => $q->orderBy('name'),
            'participants.patient:id,first_name,last_name',
            'enrollmentRequests' => fn ($q) => $q->whereNotIn('status', ['confermata', 'rifiutata'])->orderByDesc('created_at'),
        ]);

        $patients = Patient::orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone'])
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => "{$p->first_name} {$p->last_name}",
                'email' => $p->email,
                'phone' => $p->phone,
            ]);

        return Inertia::render('Groups/Show', [
            'group'        => $this->present($group, true),
            'participants' => $group->participants->map(fn (GroupParticipant $p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'email'  => $p->email,
                'phone'  => $p->phone,
                'status' => $p->status,
                'is_patient' => ! is_null($p->patient_id),
            ]),
            'requests' => $group->enrollmentRequests->map(fn (GroupEnrollmentRequest $r) => [
                'id'     => $r->id,
                'name'   => $r->name,
                'email'  => $r->email,
                'phone'  => $r->phone,
                'status' => $r->status,
                'date'   => $r->created_at->format('d/m/Y'),
            ]),
            'patientsOptions'      => $patients,
            'categories'           => $this->categories(),
            'leaders'              => $this->leaders(),
            'cadences'             => config('groups.cadences'),
            'modalities'           => config('groups.modalities'),
            'statuses'             => config('groups.statuses'),
            'participantStatuses'  => config('groups.participant_statuses'),
        ]);
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $group->update($this->validateGroup($request));

        return back()->with('flash', ['banner' => 'Gruppo aggiornato.']);
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect()->route('groups.index')->with('flash', ['banner' => 'Gruppo eliminato.']);
    }

    // ── Partecipanti ─────────────────────────────────────────────────────────
    public function addParticipant(Request $request, Group $group, PatientRegistrar $registrar): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id'     => ['nullable', 'exists:patients,id'],
            'first_name'     => ['required_without:patient_id', 'nullable', 'string', 'max:100'],
            'last_name'      => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'email', 'max:160'],
            'phone'          => ['nullable', 'string', 'max:40'],
            'codice_fiscale' => ['nullable', 'regex:/^[A-Za-z0-9]{16}$/'],
            'status'         => ['required', Rule::in(config('groups.participant_statuses'))],
        ]);

        $proIds = array_filter([$group->leader_user_id]);

        if (! empty($validated['patient_id'])) {
            // Paziente esistente: associa al professionista del gruppo, nessun duplicato.
            $patient = Patient::findOrFail($validated['patient_id']);
            $patient->professionals()->syncWithoutDetaching($proIds);
        } else {
            // Contatto esterno: crea (o ritrova) la scheda + account portale.
            $patient = $registrar->findOrCreate([
                'first_name'     => $validated['first_name'] ?? '',
                'last_name'      => $validated['last_name'] ?? '',
                'email'          => $validated['email'] ?? null,
                'phone'          => $validated['phone'] ?? null,
                'codice_fiscale' => $validated['codice_fiscale'] ?? null,
            ], $request->user()->id, $proIds);
        }

        $group->participants()->create([
            'patient_id' => $patient->id,
            'name'       => trim("{$patient->first_name} {$patient->last_name}"),
            'email'      => $patient->email,
            'phone'      => $patient->phone,
            'status'     => $validated['status'],
            'joined_at'  => now(),
        ]);

        return back()->with('flash', ['banner' => 'Partecipante aggiunto.']);
    }

    public function updateParticipant(Request $request, Group $group, GroupParticipant $participant): RedirectResponse
    {
        abort_unless($participant->group_id === $group->id, 404);

        $participant->update($request->validate([
            'status' => ['required', Rule::in(config('groups.participant_statuses'))],
        ]));

        return back();
    }

    public function removeParticipant(Group $group, GroupParticipant $participant): RedirectResponse
    {
        abort_unless($participant->group_id === $group->id, 404);
        $participant->delete();

        return back()->with('flash', ['banner' => 'Partecipante rimosso.']);
    }

    // ── Richieste d'iscrizione ───────────────────────────────────────────────
    public function approveEnrollment(Request $request, Group $group, GroupEnrollmentRequest $enrollment, PatientRegistrar $registrar): RedirectResponse
    {
        abort_unless($enrollment->group_id === $group->id, 404);

        // Crea (o ritrova) la scheda anagrafica + account portale dai dati della richiesta.
        [$first, $last] = PatientRegistrar::splitName($enrollment->name);
        $patient = $registrar->findOrCreate([
            'first_name'     => $first,
            'last_name'      => $last,
            'email'          => $enrollment->email,
            'phone'          => $enrollment->phone,
            'codice_fiscale' => $enrollment->codice_fiscale,
        ], $request->user()->id, array_filter([$group->leader_user_id]));

        $group->participants()->create([
            'patient_id' => $patient->id,
            'name'       => trim("{$patient->first_name} {$patient->last_name}"),
            'email'      => $patient->email,
            'phone'      => $patient->phone,
            'status'     => 'in_attesa',
            'joined_at'  => now(),
        ]);

        $enrollment->update(['status' => 'confermata']);

        return back()->with('flash', ['banner' => 'Richiesta approvata: partecipante inserito e scheda creata.']);
    }

    public function rejectEnrollment(Group $group, GroupEnrollmentRequest $enrollment): RedirectResponse
    {
        abort_unless($enrollment->group_id === $group->id, 404);
        $enrollment->update(['status' => 'rifiutata']);

        return back();
    }

    // ── Esporta CSV ──────────────────────────────────────────────────────────
    public function exportParticipants(Group $group): StreamedResponse
    {
        $filename = 'partecipanti-'.Str::slug($group->name).'.csv';

        return response()->streamDownload(function () use ($group) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nome', 'Email', 'Telefono', 'Stato']);
            foreach ($group->participants()->orderBy('name')->get() as $p) {
                fputcsv($out, [$p->name, $p->email, $p->phone, $p->status]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── Volantino PDF + QR ───────────────────────────────────────────────────
    public function flyer(Request $request, Group $group)
    {
        $url = route('groups.public.show', $group->public_token);

        // SVG (nessuna dipendenza da ext-gd, che su Aruba può non essere attiva);
        // DomPDF renderizza l'SVG via data-uri.
        $qr = (new Builder(
            writer: new SvgWriter(),
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 8,
        ))->build();

        // Anteprima: restituisce solo il PNG del QR (per l'<img> nella pagina gruppo).
        if ($request->boolean('preview')) {
            return new HttpResponse($qr->getString(), 200, ['Content-Type' => $qr->getMimeType()]);
        }

        $cat = $group->categoryConfig();
        $pdf = Pdf::loadView('pdf.group-flyer', [
            'group'      => $group,
            'category'   => $cat,
            'qrDataUri'  => $qr->getDataUri(),
            'url'        => $url,
        ])->setPaper('a4');

        return $pdf->download('volantino-'.Str::slug($group->name).'.pdf');
    }

    // ── Helper ───────────────────────────────────────────────────────────────
    private function leaders(): array
    {
        return User::whereHas('professionalProfile')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->all();
    }

    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'category'        => ['required', Rule::in(array_keys(config('groups.categories')))],
            'name'            => ['required', 'string', 'max:160'],
            'description'     => ['nullable', 'string'],
            'leader_user_id'  => ['nullable', 'exists:users,id'],
            'cadence'         => ['nullable', Rule::in(config('groups.cadences'))],
            'modality'        => ['nullable', Rule::in(config('groups.modalities'))],
            'location'        => ['nullable', 'string', 'max:160'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:200'],
            'next_meeting_at' => ['nullable', 'date'],
            'status'          => ['required', Rule::in(config('groups.statuses'))],
        ]);
    }

    private function present(Group $g, bool $full = false): array
    {
        $cat = $g->categoryConfig();

        $base = [
            'id'              => $g->id,
            'name'            => $g->name,
            'category'        => $g->category,
            'category_label'  => $cat['label'],
            'tone'            => $cat['tone'],
            'description'     => $g->description ?: $cat['description'],
            'status'          => $g->status,
            'capacity'        => $g->capacity,
            'enrolled'        => $g->participants_count ?? $g->participants()->count(),
        ];

        if (! $full) {
            return $base;
        }

        return array_merge($base, [
            'leader'          => $g->leader?->name,
            'leader_user_id'  => $g->leader_user_id,
            'cadence'         => $g->cadence,
            'modality'        => $g->modality,
            'location'        => $g->location,
            'next_meeting_at' => $g->next_meeting_at?->toISOString(),
            'public_token'    => $g->public_token,
            'public_url'      => route('groups.public.show', $g->public_token),
        ]);
    }
}
