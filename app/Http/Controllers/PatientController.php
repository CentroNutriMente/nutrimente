<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\PatientTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Patient::with('tags')
            ->when($request->search, fn ($q) => $q
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
                ->orWhere('codice_fiscale', 'like', "%{$request->search}%")
            )
            ->when($request->tag, fn ($q) => $q
                ->whereHas('tags', fn ($q) => $q->where('patient_tags.id', $request->tag))
            )
            ->orderBy('last_name');

        return Inertia::render('Patients/Index', [
            'patients' => $query->paginate(20)->withQueryString(),
            'tags' => PatientTag::orderBy('name')->get(),
            'filters' => $request->only(['search', 'tag']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Patients/Create', [
            'tags' => PatientTag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'codice_fiscale' => 'nullable|string|max:16|unique:patients',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F,altro',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'cap' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:patient_tags,id',
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $patient = Patient::create($validated);
        $patient->tags()->sync($tags);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente creato.');
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'tags',
            'records.user',
            'appointments.user',
            'consents',
            'invoices' => fn ($q) => $q->orderByDesc('issued_at'),
            'reports'  => fn ($q) => $q->with(['user', 'template'])->orderByDesc('report_date'),
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('Patients/Edit', [
            'patient' => $patient->load('tags'),
            'tags' => PatientTag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'codice_fiscale' => 'nullable|string|max:16|unique:patients,codice_fiscale,' . $patient->id,
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F,altro',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'cap' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:patient_tags,id',
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $patient->update($validated);
        $patient->tags()->sync($tags);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente aggiornato.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paziente archiviato.');
    }

    public function records(Patient $patient): Response
    {
        return Inertia::render('Patients/Records', [
            'patient' => $patient,
            'records' => $patient->records()->with('user')->orderByDesc('record_date')->get(),
        ]);
    }

    public function storeRecord(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'record_type' => 'required|string',
            'title' => 'required|string|max:255',
            'data' => 'nullable|array',
            'notes' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'record_date' => 'required|date',
            'is_shared_with_team' => 'boolean',
        ]);

        $patient->records()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'data' => $validated['data'] ?? [],
        ]);

        return back()->with('success', 'Scheda salvata.');
    }
}
