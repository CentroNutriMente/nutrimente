<?php

namespace App\Http\Controllers;

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

        return back()->with('success', 'Intervisione aggiornata.');
    }

    public function destroy(Intervisione $intervisione): RedirectResponse
    {
        $intervisione->delete();
        return redirect()->route('intervisioni.index')->with('success', 'Intervisione eliminata.');
    }
}
