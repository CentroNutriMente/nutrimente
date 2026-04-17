<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\PatientTag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /** Professionals the current user is allowed to see. */
    private function visibleScope($query): void
    {
        $userId = auth()->id();
        $query->where(function ($q) use ($userId) {
            $q->whereNull('created_by')                                    // legacy records
              ->orWhere('created_by', $userId)                             // own patients
              ->orWhereHas('professionals', fn ($q) => $q->where('user_id', $userId)); // shared with me
        });
    }

    private function authorizePatient(Patient $patient): void
    {
        // Legacy patients (no creator) remain visible to all.
        if ($patient->created_by === null) return;

        $userId = auth()->id();
        if ($patient->created_by !== $userId
            && ! $patient->professionals()->where('user_id', $userId)->exists()
        ) {
            abort(403, 'Non sei autorizzato ad accedere a questo paziente.');
        }
    }

    private function allProfessionals(): \Illuminate\Support\Collection
    {
        return User::whereHas('professionalProfile')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function index(Request $request): Response
    {
        $query = Patient::with(['tags', 'creator'])
            ->when($request->search, fn ($q) => $q
                ->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('codice_fiscale', 'like', "%{$request->search}%")
                )
            )
            ->when($request->tag, fn ($q) => $q
                ->whereHas('tags', fn ($q) => $q->where('patient_tags.id', $request->tag))
            )
            ->orderBy('last_name');

        $this->visibleScope($query);

        return Inertia::render('Patients/Index', [
            'patients' => $query->paginate(20)->withQueryString(),
            'tags'     => PatientTag::orderBy('name')->get(),
            'filters'  => $request->only(['search', 'tag']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Patients/Create', [
            'tags'          => PatientTag::orderBy('name')->get(),
            'professionals' => $this->allProfessionals(),
            'authId'        => auth()->id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'codice_fiscale'=> 'nullable|string|max:16|unique:patients',
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:M,F,altro',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'cap'           => 'nullable|string|max:10',
            'notes'         => 'nullable|string',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:patient_tags,id',
            'professionals' => 'nullable|array',
            'professionals.*' => 'exists:users,id',
        ]);

        $tags          = $validated['tags'] ?? [];
        $professionals = $validated['professionals'] ?? [];
        unset($validated['tags'], $validated['professionals']);

        $patient = Patient::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $patient->tags()->sync($tags);
        // Sync the selected professionals (creator always has access via created_by)
        $patient->professionals()->sync($professionals);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente creato.');
    }

    public function show(Patient $patient): Response
    {
        $this->authorizePatient($patient);

        $patient->load([
            'tags',
            'creator',
            'professionals',
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
        $this->authorizePatient($patient);

        $patient->load(['tags', 'creator', 'professionals']);

        return Inertia::render('Patients/Edit', [
            'patient'       => $patient,
            'tags'          => PatientTag::orderBy('name')->get(),
            'professionals' => $this->allProfessionals(),
            'authId'        => auth()->id(),
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'codice_fiscale'=> 'nullable|string|max:16|unique:patients,codice_fiscale,' . $patient->id,
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:M,F,altro',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'cap'           => 'nullable|string|max:10',
            'notes'         => 'nullable|string',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:patient_tags,id',
            'professionals' => 'nullable|array',
            'professionals.*' => 'exists:users,id',
        ]);

        $tags          = $validated['tags'] ?? [];
        $professionals = $validated['professionals'] ?? [];
        unset($validated['tags'], $validated['professionals']);

        $patient->update($validated);
        $patient->tags()->sync($tags);
        $patient->professionals()->sync($professionals);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente aggiornato.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paziente archiviato.');
    }

    public function records(Patient $patient): Response
    {
        $this->authorizePatient($patient);

        return Inertia::render('Patients/Records', [
            'patient' => $patient,
            'records' => $patient->records()->with('user')->orderByDesc('record_date')->get(),
        ]);
    }

    public function storeRecord(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'category'          => 'required|string',
            'record_type'       => 'required|string',
            'title'             => 'required|string|max:255',
            'data'              => 'nullable|array',
            'notes'             => 'nullable|string',
            'treatment_plan'    => 'nullable|string',
            'record_date'       => 'required|date',
            'is_shared_with_team' => 'boolean',
        ]);

        $patient->records()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'data'    => $validated['data'] ?? [],
        ]);

        return back()->with('success', 'Scheda salvata.');
    }
}
