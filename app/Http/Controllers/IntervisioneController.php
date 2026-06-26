<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Intervisione;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntervisioneController extends Controller
{
    /** Accesso consentito al creatore, ai partecipanti e agli admin. */
    private function ensureMember(Intervisione $intervisione): void
    {
        $user = request()->user();
        if ($user->hasRole('admin')) return;
        if ($intervisione->created_by === $user->id) return;
        if ($intervisione->participants()->where('users.id', $user->id)->exists()) return;

        abort(403, 'Non sei autorizzato ad accedere a questa intervisione.');
    }

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $intervisioni = Intervisione::with('createdBy', 'patient')
            // Solo le intervisioni di cui l'utente è creatore o partecipante (admin: tutte).
            ->when(! $request->user()->hasRole('admin'), fn ($q) => $q
                ->where(fn ($q) => $q
                    ->where('created_by', $userId)
                    ->orWhereHas('participants', fn ($q) => $q->where('users.id', $userId))
                )
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'status' => $i->status,
                'scheduled_at' => $i->scheduled_at,
                'patient_name' => $i->patient ? $i->patient->full_name : null,
                'created_by_name' => $i->createdBy->name,
                'created_at' => $i->created_at,
            ]);

        return Inertia::render('Intervisioni/Index', [
            'intervisioni' => $intervisioni,
        ]);
    }

    public function create(Request $request): Response
    {
        $patients = Patient::visibleTo($request->user())
            ->select('id', 'first_name', 'last_name')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name]);

        $users = User::whereHas('professionalProfile')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn ($u) => $u->id !== $request->user()->id)
            ->values();

        return Inertia::render('Intervisioni/Create', [
            'patients' => $patients,
            'users'    => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'patient_id'      => 'nullable|exists:patients,id',
            'scheduled_at'    => 'nullable|date',
            'status'          => 'in:draft,scheduled,completed',
            'meet_link'       => 'nullable|url|max:500',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $intervisione = Intervisione::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status'     => $validated['status'] ?? 'draft',
        ]);

        // Salva partecipanti (includi sempre il creatore)
        $ids = collect($validated['participant_ids'] ?? [])
            ->push($request->user()->id)
            ->unique()
            ->all();
        $intervisione->participants()->sync($ids);

        // Notify all participants except creator
        $creatorName = $request->user()->name;
        $when = $intervisione->scheduled_at ? ' — ' . $intervisione->scheduled_at->format('d/m/Y H:i') : '';
        collect($ids)
            ->reject(fn ($id) => $id === $request->user()->id)
            ->each(fn ($uid) => Notification::send(
                $uid,
                'intervisione',
                "Sei stato incluso in un'intervisione",
                "{$intervisione->title}{$when} (da {$creatorName})",
                ['intervisione_id' => $intervisione->id]
            ));

        $this->syncAppointments($intervisione, $ids);

        return redirect()->route('intervisioni.show', $intervisione->id)
            ->with('success', 'Intervisione creata.');
    }

    public function show(Intervisione $intervisione): Response
    {
        $this->ensureMember($intervisione);

        $patients = Patient::visibleTo(auth()->user())
            ->select('id', 'first_name', 'last_name')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name]);

        $users = User::whereHas('professionalProfile')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Intervisioni/Show', [
            'intervisione' => $intervisione->load('createdBy', 'patient', 'participants'),
            'patients'     => $patients,
            'users'        => $users,
        ]);
    }

    public function update(Request $request, Intervisione $intervisione): RedirectResponse
    {
        $this->ensureMember($intervisione);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'discussion_notes'  => 'nullable|string',
            'conclusions'       => 'nullable|string',
            'patient_id'        => 'nullable|exists:patients,id',
            'scheduled_at'      => 'nullable|date',
            'status'            => 'in:draft,scheduled,completed',
            'meet_link'         => 'nullable|url|max:500',
            'participant_ids'   => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $intervisione->update($validated);

        if (isset($validated['participant_ids'])) {
            $newIds = collect($validated['participant_ids'])
                ->push($intervisione->created_by)
                ->unique();

            // Find newly added participants to notify them
            $existingIds = $intervisione->participants()->pluck('users.id');
            $addedIds    = $newIds->diff($existingIds)->reject(fn ($id) => $id === $request->user()->id);

            $intervisione->participants()->sync($newIds->all());

            $when = $intervisione->scheduled_at ? ' — ' . $intervisione->scheduled_at->format('d/m/Y H:i') : '';
            $updaterName = $request->user()->name;
            $addedIds->each(fn ($uid) => Notification::send(
                $uid,
                'intervisione',
                "Sei stato aggiunto a un'intervisione",
                "{$intervisione->title}{$when} (da {$updaterName})",
                ['intervisione_id' => $intervisione->id]
            ));
        }

        $participantIds = $intervisione->participants()->pluck('users.id')->all();
        $this->syncAppointments($intervisione, $participantIds);

        return back()->with('success', 'Intervisione aggiornata.');
    }

    public function destroy(Intervisione $intervisione): RedirectResponse
    {
        $user = request()->user();
        abort_unless($user->hasRole('admin') || $intervisione->created_by === $user->id, 403);

        $intervisione->delete();
        return redirect()->route('intervisioni.index')->with('success', 'Intervisione eliminata.');
    }

    private function syncAppointments(Intervisione $intervisione, array $participantIds): void
    {
        Appointment::where('intervisione_id', $intervisione->id)->delete();

        if (! $intervisione->scheduled_at) return;

        $endAt  = $intervisione->scheduled_at->copy()->addHour();
        $status = $intervisione->status === 'completed' ? 'completed' : 'scheduled';

        foreach ($participantIds as $userId) {
            Appointment::create([
                'user_id'         => $userId,
                'patient_id'      => $intervisione->patient_id,
                'type'            => 'intervision',
                'title'           => $intervisione->title,
                'start_at'        => $intervisione->scheduled_at,
                'end_at'          => $endAt,
                'intervisione_id' => $intervisione->id,
                'is_shared'       => true,
                'status'          => $status,
                'color'           => '#c084fc',
            ]);
        }
    }
}
