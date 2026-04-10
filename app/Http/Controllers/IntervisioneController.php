<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Intervisione;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntervisioneController extends Controller
{
    public function index(Request $request): Response
    {
        $intervisioni = Intervisione::with('createdBy', 'patient')
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

    public function create(): Response
    {
        $patients = Patient::select('id', 'first_name', 'last_name')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name]);

        return Inertia::render('Intervisioni/Create', [
            'patients' => $patients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'patient_id' => 'nullable|exists:patients,id',
            'scheduled_at' => 'nullable|date',
            'status' => 'in:draft,scheduled,completed',
        ]);

        $intervisione = Intervisione::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status' => $validated['status'] ?? 'draft',
        ]);

        if ($intervisione->scheduled_at) {
            Appointment::create([
                'user_id' => $request->user()->id,
                'patient_id' => $intervisione->patient_id,
                'type' => 'intervision',
                'title' => $intervisione->title,
                'start_at' => $intervisione->scheduled_at,
                'end_at' => $intervisione->scheduled_at->addHour(),
                'intervisione_id' => $intervisione->id,
                'is_shared' => true,
                'status' => 'scheduled',
                'color' => '#c084fc',
            ]);
        }

        return redirect()->route('intervisioni.show', $intervisione->id)
            ->with('success', 'Intervisione creata.');
    }

    public function show(Intervisione $intervisione): Response
    {
        $patients = Patient::select('id', 'first_name', 'last_name')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name]);

        return Inertia::render('Intervisioni/Show', [
            'intervisione' => $intervisione->load('createdBy', 'patient'),
            'patients' => $patients,
        ]);
    }

    public function update(Request $request, Intervisione $intervisione): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discussion_notes' => 'nullable|string',
            'conclusions' => 'nullable|string',
            'patient_id' => 'nullable|exists:patients,id',
            'scheduled_at' => 'nullable|date',
            'status' => 'in:draft,scheduled,completed',
        ]);

        $intervisione->update($validated);

        // Sync the linked appointment
        $appointment = Appointment::where('intervisione_id', $intervisione->id)->first();
        if ($intervisione->scheduled_at) {
            $data = [
                'title' => $intervisione->title,
                'start_at' => $intervisione->scheduled_at,
                'end_at' => $intervisione->scheduled_at->addHour(),
                'patient_id' => $intervisione->patient_id,
                'status' => $intervisione->status === 'completed' ? 'completed' : 'scheduled',
            ];
            if ($appointment) {
                $appointment->update($data);
            } else {
                Appointment::create([
                    ...$data,
                    'user_id' => $intervisione->created_by,
                    'type' => 'intervision',
                    'intervisione_id' => $intervisione->id,
                    'is_shared' => true,
                    'color' => '#c084fc',
                ]);
            }
        } elseif ($appointment) {
            $appointment->delete();
        }

        return back()->with('success', 'Intervisione aggiornata.');
    }

    public function destroy(Intervisione $intervisione): RedirectResponse
    {
        $intervisione->delete();
        return redirect()->route('intervisioni.index')->with('success', 'Intervisione eliminata.');
    }
}
