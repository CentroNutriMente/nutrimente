<?php

namespace App\Http\Controllers;

use App\Mail\PatientWelcomeMail;
use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\PatientTag;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /** Professionals the current user is allowed to see. */
    private function visibleScope($query): void
    {
        if (auth()->user()->hasRole('admin')) return;

        $userId = auth()->id();
        $query->where(function ($q) use ($userId) {
            $q->whereNull('created_by')                                    // legacy records
              ->orWhere('created_by', $userId)                             // own patients
              ->orWhereHas('professionals', fn ($q) => $q->where('user_id', $userId)); // shared with me
        });
    }

    private function authorizePatient(Patient $patient): void
    {
        if (auth()->user()->hasRole('admin')) return;

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

    /**
     * Regola di unicità del codice fiscale via blind index (la colonna è cifrata,
     * quindi `unique:patients,codice_fiscale` non è utilizzabile).
     */
    private function uniqueCodiceFiscaleRule(?int $ignoreId = null): \Closure
    {
        return function (string $attribute, $value, $fail) use ($ignoreId) {
            if (! $value) {
                return;
            }

            $exists = Patient::withTrashed()
                ->where('codice_fiscale_hash', BlindIndex::hash($value))
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('Il codice fiscale è già presente in anagrafica.');
            }
        };
    }

    /**
     * Normalizza l'elenco parenti/tutori: scarta le righe vuote e forza i booleani.
     * Restituisce null quando non resta alcun nominativo valido.
     */
    private function cleanRelatives(?array $relatives): ?array
    {
        if (! $relatives) {
            return null;
        }

        $cleaned = collect($relatives)
            ->map(fn ($r) => [
                'name'              => trim((string) ($r['name'] ?? '')),
                'relationship'      => trim((string) ($r['relationship'] ?? '')),
                'phone'             => trim((string) ($r['phone'] ?? '')),
                'email'             => trim((string) ($r['email'] ?? '')),
                'can_access_data'   => (bool) ($r['can_access_data'] ?? false),
                'is_legal_guardian' => (bool) ($r['is_legal_guardian'] ?? false),
            ])
            ->filter(fn ($r) => $r['name'] !== '' || $r['relationship'] !== '' || $r['phone'] !== '' || $r['email'] !== '')
            ->values()
            ->all();

        return $cleaned ?: null;
    }

    /**
     * Crea l'account portale del paziente se ha un'email e non ne esiste già uno.
     * Usato sia alla creazione sia alla modifica (quando l'email viene aggiunta dopo).
     */
    private function ensurePortalAccount(Patient $patient): void
    {
        if (! $patient->email || User::where('email', $patient->email)->exists()) {
            return;
        }

        $tempPassword = Str::random(10);
        $patientUser  = User::create([
            'name'              => "{$patient->first_name} {$patient->last_name}",
            'email'             => $patient->email,
            'password'          => Hash::make($tempPassword),
            'email_verified_at' => now(),
        ]);
        $patientUser->assignRole('patient');

        try {
            Mail::to($patient->email)->send(new PatientWelcomeMail($patient, $tempPassword));
        } catch (\Exception $e) {
            \Log::error('PatientWelcomeMail failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request): Response
    {
        // Nome/cognome/CF sono cifrati: ricerca e ordinamento avvengono lato PHP
        // (la scala di un singolo studio lo rende sostenibile). Il filtro per tag
        // resta in SQL perché relazionale.
        $query = Patient::with(['tags', 'creator'])
            ->when($request->tag, fn ($q) => $q
                ->whereHas('tags', fn ($q) => $q->where('patient_tags.id', $request->tag))
            );

        $this->visibleScope($query);

        $patients = $query->get();

        if ($search = trim((string) $request->search)) {
            $needle = mb_strtolower($search);
            $patients = $patients->filter(fn (Patient $p) => str_contains(mb_strtolower((string) $p->first_name), $needle)
                || str_contains(mb_strtolower((string) $p->last_name), $needle)
                || str_contains(mb_strtolower((string) $p->codice_fiscale), $needle));
        }

        $patients = $patients->sortBy(fn (Patient $p) => mb_strtolower((string) $p->last_name))->values();

        $perPage = 20;
        $page    = Paginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $patients->forPage($page, $perPage)->values(),
            $patients->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        return Inertia::render('Patients/Index', [
            'patients' => $paginated,
            'tags'     => PatientTag::orderBy('name')->get(),
            'filters'  => $request->only(['search', 'tag']),
        ]);
    }

    /**
     * Archivio delle cartelle eliminate: restano consultabili (obbligo di
     * conservazione) ma fuori dall'elenco generale dei pazienti.
     */
    public function archived(Request $request): Response
    {
        $this->ensureAdmin();

        $query = Patient::onlyTrashed()->with(['tags', 'creator']);
        $this->visibleScope($query);

        $patients = $query->get();

        if ($search = trim((string) $request->search)) {
            $needle = mb_strtolower($search);
            $patients = $patients->filter(fn (Patient $p) => str_contains(mb_strtolower((string) $p->first_name), $needle)
                || str_contains(mb_strtolower((string) $p->last_name), $needle)
                || str_contains(mb_strtolower((string) $p->codice_fiscale), $needle));
        }

        $patients = $patients
            ->sortByDesc('deleted_at')
            ->values()
            ->map(fn (Patient $p) => [
                'id'         => $p->id,
                'first_name' => $p->first_name,
                'last_name'  => $p->last_name,
                'email'      => $p->email,
                'phone'      => $p->phone,
                'status'     => $p->status,
                'creator'    => $p->creator?->name,
                'deleted_at' => $p->deleted_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Patients/Archived', [
            'patients' => $patients,
            'filters'  => $request->only(['search']),
        ]);
    }

    /** L'archivio è riservato all'amministratore: i professionisti vedono solo le cartelle attive. */
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, 'Archivio riservato all\'amministratore.');
    }

    /** Riporta una cartella archiviata nell'elenco generale. */
    public function restore(Patient $patient): RedirectResponse
    {
        $this->ensureAdmin();
        $this->authorizePatient($patient);

        $patient->restore();
        \App\Models\AuditLog::record('restored', $patient);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente ripristinato nell\'elenco.');
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
            'codice_fiscale'=> ['nullable', 'string', 'max:16', $this->uniqueCodiceFiscaleRule()],
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:M,F,altro',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'cap'           => 'nullable|string|max:10',
            'medico_base'             => 'nullable|string|max:150',
            'emergency_contact_name'  => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'stato_civile'  => 'nullable|string|max:50',
            'educazione'    => 'nullable|string|max:80',
            'professione'   => 'nullable|string|max:150',
            'relatives'                       => 'nullable|array',
            'relatives.*.name'                => 'nullable|string|max:150',
            'relatives.*.relationship'        => 'nullable|string|max:80',
            'relatives.*.phone'               => 'nullable|string|max:20',
            'relatives.*.email'               => 'nullable|email|max:255',
            'relatives.*.can_access_data'     => 'boolean',
            'relatives.*.is_legal_guardian'   => 'boolean',
            'notes'         => 'nullable|string',
            'diagnosis'     => 'nullable|string|max:500',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:patient_tags,id',
            'professionals' => 'nullable|array',
            'professionals.*' => 'exists:users,id',
        ]);

        $validated['relatives'] = $this->cleanRelatives($validated['relatives'] ?? null);

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

        // Crea l'account portale se è stata fornita un'email e non esiste già.
        $this->ensurePortalAccount($patient);

        \App\Models\AuditLog::record('created', $patient);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente creato.');
    }

    public function show(Patient $patient): Response
    {
        // Una cartella archiviata esiste solo per l'amministratore: per gli altri
        // è come se non ci fosse più.
        abort_if($patient->trashed() && ! auth()->user()->hasRole('admin'), 404);

        $this->authorizePatient($patient);

        $userId    = auth()->id();
        $isCreator = auth()->user()->hasRole('admin') || $patient->created_by === $userId;

        $relations = [
            'tags',
            'creator',
            'professionals',
            'appointments.user',
            'consents',
            'invoices'         => fn ($q) => $q->orderByDesc('issued_at'),
            'consentDocuments' => fn ($q) => $q->whereNull('deleted_at')->orderByDesc('created_at'),
            'questionnaires'   => fn ($q) => $q->with(['template', 'user'])->orderByDesc('filled_at'),
        ];

        if ($isCreator) {
            $relations['reports'] = fn ($q) => $q->with(['user', 'template'])->orderByDesc('report_date');
            // Le note riservate sono visibili solo al loro autore.
            $relations['clinicalNotes'] = fn ($q) => $q->with('author:id,name')
                ->where(fn ($q) => $q->where('is_private', false)->orWhere('user_id', auth()->id()));
        }

        $patient->load($relations);

        \App\Models\AuditLog::record('viewed', $patient);

        return Inertia::render('Patients/Show', [
            'patient'                => $patient,
            'isCreator'              => $isCreator,
            'canCreateQuestionnaire' => $isCreator && QuestionnaireTemplate::exists(),
            'archived'               => $patient->trashed(),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $this->authorizePatient($patient);
        if (! auth()->user()->hasRole('admin')
            && $patient->created_by !== null
            && (int) $patient->created_by !== auth()->id()) {
            abort(403, 'Solo il creatore del paziente può modificarne i dati.');
        }

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
        if (! auth()->user()->hasRole('admin')
            && $patient->created_by !== null
            && (int) $patient->created_by !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'codice_fiscale'=> ['nullable', 'string', 'max:16', $this->uniqueCodiceFiscaleRule($patient->id)],
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:M,F,altro',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'cap'           => 'nullable|string|max:10',
            'medico_base'             => 'nullable|string|max:150',
            'emergency_contact_name'  => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'stato_civile'  => 'nullable|string|max:50',
            'educazione'    => 'nullable|string|max:80',
            'professione'   => 'nullable|string|max:150',
            'relatives'                       => 'nullable|array',
            'relatives.*.name'                => 'nullable|string|max:150',
            'relatives.*.relationship'        => 'nullable|string|max:80',
            'relatives.*.phone'               => 'nullable|string|max:20',
            'relatives.*.email'               => 'nullable|email|max:255',
            'relatives.*.can_access_data'     => 'boolean',
            'relatives.*.is_legal_guardian'   => 'boolean',
            'notes'         => 'nullable|string',
            'diagnosis'     => 'nullable|string|max:500',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:patient_tags,id',
            'professionals' => 'nullable|array',
            'professionals.*' => 'exists:users,id',
        ]);

        $validated['relatives'] = $this->cleanRelatives($validated['relatives'] ?? null);

        $tags          = $validated['tags'] ?? [];
        $professionals = $validated['professionals'] ?? [];
        unset($validated['tags'], $validated['professionals']);

        $patient->update($validated);
        $patient->tags()->sync($tags);
        $patient->professionals()->sync($professionals);

        // Se l'email è stata aggiunta ora e non esiste ancora un account, crealo.
        $this->ensurePortalAccount($patient);

        \App\Models\AuditLog::record('updated', $patient);

        return redirect()->route('patients.show', $patient)->with('success', 'Paziente aggiornato.');
    }

    public function updateStatus(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);
        if (! auth()->user()->hasRole('admin')
            && $patient->created_by !== null
            && (int) $patient->created_by !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:primo_contatto,attivo,sospeso,concluso',
        ]);

        $patient->update([
            'status'    => $validated['status'],
            'is_active' => $validated['status'] === 'attivo',
        ]);

        return back()->with('success', 'Stato aggiornato.');
    }

    // ── Note cliniche (sezione autonoma, scollegata dai referti) ─────────────
    // Un unico testo per paziente, da aggiornare di volta in volta. Le eventuali
    // note multiple del vecchio elenco vengono proposte unite nel campo e, al primo
    // salvataggio, confluiscono in una sola riga (le altre finiscono nel cestino).
    public function updateNote(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'body' => 'nullable|string|max:20000',
        ]);

        $body = trim((string) ($validated['body'] ?? ''));

        // Stessa visibilità della scheda: le note riservate altrui non si toccano.
        $notes = $patient->clinicalNotes()
            ->where(fn ($q) => $q->where('is_private', false)->orWhere('user_id', auth()->id()))
            ->reorder('created_at')
            ->get();

        $note = $notes->first();

        foreach ($notes->slice(1) as $extra) {
            \App\Models\AuditLog::record('deleted', $extra);
            $extra->delete();
        }

        if ($body === '') {
            if ($note) {
                \App\Models\AuditLog::record('deleted', $note);
                $note->delete();
            }

            return back()->with('success', 'Note cliniche svuotate.');
        }

        if ($note) {
            $note->update([
                'user_id'    => auth()->id(),
                'body'       => $body,
                // Se nel testo è confluita una nota riservata, il testo resta riservato.
                'is_private' => $notes->contains('is_private', true),
            ]);
            \App\Models\AuditLog::record('updated', $note);
        } else {
            $note = $patient->clinicalNotes()->create([
                'user_id'    => auth()->id(),
                'body'       => $body,
                'is_private' => false,
            ]);
            \App\Models\AuditLog::record('created', $note);
        }

        return back()->with('success', 'Note cliniche salvate.');
    }

    // ── Consensi (GDPR) ──────────────────────────────────────────────────────
    public function storeConsent(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'type'     => 'required|in:gdpr,trattamento_dati,consenso_informato',
            'accepted' => 'required|boolean',
            'method'   => 'required|in:digital,paper,verbal',
        ]);

        $consent = $patient->consents()->create([
            'user_id'     => auth()->id(),
            'type'        => $validated['type'],
            'accepted'    => $validated['accepted'],
            'accepted_at' => $validated['accepted'] ? now() : null,
            'method'      => $validated['method'],
            'ip_address'  => $request->ip(),
        ]);

        \App\Models\AuditLog::record('created', $consent);

        return back()->with('success', 'Consenso registrato.');
    }

    public function destroyConsent(Patient $patient, \App\Models\PatientConsent $consent): RedirectResponse
    {
        $this->authorizePatient($patient);
        abort_unless($consent->patient_id === $patient->id, 404);
        \App\Models\AuditLog::record('deleted', $consent);
        $consent->delete();

        return back()->with('success', 'Consenso rimosso.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorizePatient($patient);
        \App\Models\AuditLog::record('deleted', $patient);
        $patient->delete();

        // Dalla scheda del paziente si torna all'elenco (la pagina non ha più senso);
        // da altri elenchi (es. gestione utenti) si resta dov'eravamo.
        $fromPatientPage = str_contains((string) url()->previous(), '/patients/'.$patient->id);

        return $fromPatientPage
            ? redirect()->route('patients.index')->with('success', 'Paziente archiviato.')
            : back()->with('success', 'Paziente archiviato.');
    }

    /**
     * Esporta tutti i dati del paziente in un archivio ZIP (diritto di accesso e
     * portabilità, art. 15 e 20 GDPR): un JSON strutturato + gli allegati decifrati.
     */
    public function export(Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->load([
            'tags', 'consents', 'records', 'clinicalNotes.author:id,name',
            'reports.template', 'questionnaires.template',
            'appointments', 'invoices', 'documents',
        ]);

        $data = [
            'esportato_il' => now()->toDateTimeString(),
            'anagrafica'   => $patient->only([
                'first_name', 'last_name', 'codice_fiscale', 'date_of_birth', 'gender',
                'email', 'phone', 'address', 'city', 'cap',
                'emergency_contact_name', 'emergency_contact_phone', 'medico_base',
                'diagnosis', 'notes', 'status',
            ]),
            'consensi'      => $patient->consents->toArray(),
            'cartelle'      => $patient->records->toArray(),
            'note_cliniche' => $patient->clinicalNotes->map(fn ($n) => [
                'autore' => $n->author?->name, 'testo' => $n->body, 'data' => (string) $n->created_at,
            ]),
            'referti'       => $patient->reports->toArray(),
            'questionari'   => $patient->questionnaires->toArray(),
            'appuntamenti'  => $patient->appointments->toArray(),
            'fatture'       => $patient->invoices->toArray(),
            'documenti'     => $patient->documents->map->only(['title', 'file_name', 'category', 'created_at']),
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'sar').'.zip';
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('dati-paziente.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $disk = config('filesystems.default');
        foreach ($patient->documents as $doc) {
            if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($doc->file_path)) {
                $zip->addFromString('documenti/'.$doc->file_name, \App\Support\EncryptedFile::get($disk, $doc->file_path));
            }
        }
        $zip->close();

        \App\Models\AuditLog::record('exported', $patient);

        return response()->download($tmp, 'dati-paziente-'.$patient->id.'.zip')->deleteFileAfterSend();
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
