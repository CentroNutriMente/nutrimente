<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /** Mesi in italiano, indicizzati 1-12. */
    private const MESI = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
        7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    public function index(Request $request): Response
    {
        // year = 'tutti' mostra l'intero archivio (utile per recuperare le fatture
        // emesse negli anni precedenti dalla scheda paziente).
        $year = $request->year === 'tutti' ? null : (int) ($request->year ?: now()->year);
        $status = $request->status;

        $invoices = $this->scoped($request)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($year, fn ($q) => $q->where('invoice_year', $year))
            ->orderByDesc('invoice_number')
            ->get();

        // Sottosezioni per mese di emissione: più recente in alto, all'interno
        // ordinate per numero di fattura decrescente.
        $months = $invoices
            ->groupBy(function (Invoice $i) {
                $date = $i->issued_at ?: $i->created_at;

                return $date->format('Y-m');
            })
            ->sortKeysDesc()
            ->map(function ($group, $key) {
                [$y, $m] = array_map('intval', explode('-', $key));

                return [
                    'month'    => $key,
                    'label'    => (self::MESI[$m] ?? (string) $m)." {$y}",
                    'count'    => $group->count(),
                    'total'    => round((float) $group->where('status', '!=', 'cancelled')->sum('total'), 2),
                    'invoices' => $group->sortByDesc('invoice_number')->values()->map(fn (Invoice $i) => $this->row($i)),
                ];
            })
            ->values();

        $validTotals = fn ($q) => $q->where('status', '!=', 'cancelled');

        return Inertia::render('Invoices/Index', [
            'months'  => $months,
            'stats'   => [
                'year'        => $year ?? now()->year,
                'year_total'  => round((float) $validTotals($this->scoped($request))->where('invoice_year', $year ?? now()->year)->sum('total'), 2),
                'total'       => round((float) $validTotals($this->scoped($request))->sum('total'), 2),
                'count'       => $invoices->count(),
                'count_all'   => $this->scoped($request)->count(),
            ],
            'years'   => $this->scoped($request)->distinct()->orderByDesc('invoice_year')->pluck('invoice_year')->all() ?: [now()->year],
            'filters' => ['status' => $status, 'year' => $year ?? 'tutti'],
        ]);
    }

    /** Fatture visibili all'utente: le proprie (l'admin vede tutto). */
    private function scoped(Request $request)
    {
        $user = $request->user();

        return Invoice::query()
            ->with('patient:id,first_name,last_name')
            ->unless($user->hasRole('admin'), fn ($q) => $q->where('user_id', $user->id));
    }

    /** Contenuto del PDF caricato, se la fattura ne ha uno ancora presente su disco. */
    private function storedPdf(Invoice $invoice): ?string
    {
        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            return null;
        }

        return \App\Support\EncryptedFile::get('local', $invoice->pdf_path);
    }

    /** Riga fattura per l'elenco. */
    private function row(Invoice $i): array
    {
        return [
            'id'             => $i->id,
            'invoice_code'   => $i->invoice_code,
            'invoice_number' => $i->invoice_number,
            'issued_at'      => $i->issued_at?->format('Y-m-d'),
            'client_name'    => $i->client_name ?: trim("{$i->patient?->first_name} {$i->patient?->last_name}"),
            'patient_id'     => $i->patient_id,
            'issuer_name'    => $i->issuer_name,
            'subtotal'       => (float) $i->subtotal,
            'marca_da_bollo' => (float) $i->marca_da_bollo,
            'total'          => (float) $i->total,
            'payment_method' => $i->payment_method,
            'status'         => $i->status,
            'sts_sent'       => (bool) $i->sts_sent,
            'uploaded'       => (bool) $i->pdf_path,
        ];
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->professionalProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['category' => 'professionista']
        );

        return Inertia::render('Invoices/Create', [
            'patients'   => Patient::visibleTo($request->user())->get(['id', 'first_name', 'last_name', 'codice_fiscale'])->sortBy(fn ($p) => mb_strtolower((string) $p->last_name))->values(),
            'profile'    => $profile,
            'issuerName' => $user->name,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'issued_at' => 'required|date',
        ]);

        $user = $request->user();
        $profile = $user->professionalProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['category' => 'professionista']
        );
        $patient = Patient::findOrFail($validated['patient_id']);

        if (! $user->hasRole('admin')
            && $patient->created_by !== null
            && (int) $patient->created_by !== $user->id) {
            abort(403, 'Solo il creatore del paziente può emettere fatture.');
        }

        $subtotal = collect($validated['lines'])->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
        $marcaDaBollo = Invoice::calculateMarcaDaBollo($subtotal);
        $total = $subtotal + $marcaDaBollo;

        try {
            DB::transaction(function () use ($user, $profile, $patient, $validated, $subtotal, $marcaDaBollo, $total) {
                $year = now()->year;
                $number = $profile->nextInvoiceNumber();

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'patient_id' => $patient->id,
                    'appointment_id' => $validated['appointment_id'] ?? null,
                    'invoice_number' => $number,
                    'invoice_year' => $year,
                    'invoice_code' => "{$number}/{$year}",
                    'issuer_name' => $user->name,
                    'issuer_partita_iva' => $profile?->partita_iva,
                    'issuer_codice_fiscale' => $profile?->codice_fiscale,
                    'issuer_address' => $profile?->address,
                    'issuer_regime_fiscale' => $profile?->regime_fiscale ?? 'ordinario',
                    'client_name' => $patient->full_name,
                    'client_codice_fiscale' => $patient->codice_fiscale,
                    'client_address' => trim(collect([$patient->address, $patient->cap, $patient->city])->filter()->implode(', ')),
                    'client_phone' => $patient->phone,
                    'client_email' => $patient->email,
                    'subtotal' => $subtotal,
                    'marca_da_bollo' => $marcaDaBollo,
                    'total' => $total,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'issued_at' => $validated['issued_at'],
                    'status' => 'issued',
                ]);

                foreach ($validated['lines'] as $line) {
                    $invoice->lines()->create([
                        'description' => $line['description'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'total' => $line['quantity'] * $line['unit_price'],
                    ]);
                }

                return $invoice;
            });
        } catch (\Throwable $e) {
            \Log::error('InvoiceController@store: ' . $e->getMessage());
            return back()->with('error', 'Errore durante la creazione della fattura. Riprova.');
        }

        return redirect()->route('invoices.index')->with('success', 'Fattura emessa.');
    }

    // ── Fattura già emessa altrove: caricamento del PDF ──────────────────────
    public function uploadForm(Request $request): Response
    {
        return Inertia::render('Invoices/Upload', [
            'patients' => Patient::visibleTo($request->user())
                ->get(['id', 'first_name', 'last_name'])
                ->sortBy(fn ($p) => mb_strtolower((string) $p->last_name.' '.(string) $p->first_name))
                ->values()
                ->map(fn ($p) => ['id' => $p->id, 'name' => trim("{$p->last_name} {$p->first_name}")]),
        ]);
    }

    /**
     * Legge il PDF e propone i campi della fattura (numero, data, importo,
     * metodo, paziente via codice fiscale). Nessuna scrittura: è solo un aiuto
     * alla compilazione, l'utente conferma o corregge.
     */
    public function analyzePdf(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:pdf', 'max:10240']]);

        $data = \App\Services\InvoicePdfExtractor::extract(
            (string) file_get_contents($request->file('file')->getRealPath())
        );

        // Il paziente proposto deve essere fra quelli visibili all'utente.
        if (! empty($data['patient_id'])
            && ! Patient::visibleTo($request->user())->whereKey($data['patient_id'])->exists()) {
            unset($data['patient_id']);
        }

        return response()->json($data);
    }

    /**
     * Registra una fattura emessa fuori dall'app allegandone il PDF: i dati servono
     * a collocarla nell'archivio (mese, numero, importo), il file resta la fonte.
     */
    public function uploadStore(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'file'           => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10 MB
            'patient_id'     => ['nullable', 'exists:patients,id'],
            'client_name'    => ['required_without:patient_id', 'nullable', 'string', 'max:160'],
            'invoice_code'   => [
                'required', 'string', 'max:60',
                Rule::unique('invoices', 'invoice_code')->where('user_id', $user->id)->whereNull('deleted_at'),
            ],
            'invoice_number' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'issued_at'      => ['required', 'date'],
            'total'          => ['required', 'numeric', 'min:0', 'max:999999'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'status'         => ['required', Rule::in(['issued', 'paid'])],
        ]);

        $profile = $user->professionalProfile()->firstOrCreate(['user_id' => $user->id], ['category' => 'professionista']);
        $patient = ! empty($validated['patient_id']) ? Patient::findOrFail($validated['patient_id']) : null;
        $issuedAt = \Illuminate\Support\Carbon::parse($validated['issued_at']);
        $year = (int) $issuedAt->year;

        // Numero: quello indicato, altrimenti le cifre iniziali del codice, altrimenti il progressivo.
        $number = $validated['invoice_number']
            ?? (preg_match('/\d+/', $validated['invoice_code'], $m) ? (int) $m[0] : null)
            ?? ((int) Invoice::where('user_id', $user->id)->where('invoice_year', $year)->max('invoice_number') + 1);

        $path = \App\Support\EncryptedFile::store($request->file('file'), "invoices/{$user->id}", 'local');

        $invoice = Invoice::create([
            'user_id'               => $user->id,
            'patient_id'            => $patient?->id,
            'invoice_number'        => $number,
            'invoice_year'          => $year,
            'invoice_code'          => $validated['invoice_code'],
            'issuer_name'           => $user->name,
            'issuer_partita_iva'    => $profile?->partita_iva,
            'issuer_codice_fiscale' => $profile?->codice_fiscale,
            'issuer_address'        => $profile?->address,
            'issuer_regime_fiscale' => $profile?->regime_fiscale ?? 'ordinario',
            'client_name'           => $patient?->full_name ?? $validated['client_name'],
            'client_codice_fiscale' => $patient?->codice_fiscale,
            'client_address'        => $patient ? trim(collect([$patient->address, $patient->cap, $patient->city])->filter()->implode(', ')) : null,
            'client_phone'          => $patient?->phone,
            'client_email'          => $patient?->email,
            'subtotal'              => $validated['total'],
            'marca_da_bollo'        => 0,
            'total'                 => $validated['total'],
            'payment_method'        => $validated['payment_method'] ?? null,
            'issued_at'             => $issuedAt->toDateString(),
            'paid_at'               => $validated['status'] === 'paid' ? $issuedAt->toDateString() : null,
            'status'                => $validated['status'],
            'pdf_path'              => $path,
        ]);

        \App\Models\AuditLog::record('created', $invoice, null, ['note' => 'fattura caricata da PDF']);

        return redirect()->route('invoices.index', ['year' => $year])
            ->with('success', 'Fattura caricata.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        // Solo l'emittente (o un admin) può vedere la fattura: contiene dati fiscali e PII del paziente.
        abort_unless($request->user()->hasRole('admin') || $invoice->user_id === $request->user()->id, 403);

        return Inertia::render('Invoices/Show', [
            'invoice'   => $invoice->load(['patient', 'lines', 'user.professionalProfile']),
            'canEdit'   => $invoice->user_id === $request->user()->id,
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);
        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice->load(['patient', 'lines']),
            'patients' => Patient::visibleTo($request->user())->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'codice_fiscale']),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->update($request->only(['payment_method', 'paid_at', 'status']));

        return redirect()->route('invoices.show', $invoice)->with('success', 'Fattura aggiornata.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);
        $invoice->update(['status' => 'cancelled']);
        \App\Models\AuditLog::record('deleted', $invoice, null, ['note' => 'annullata']);

        return redirect()->route('invoices.index')->with('success', 'Fattura annullata.');
    }

    public function downloadPdf(Request $request, Invoice $invoice): HttpResponse
    {
        abort_unless($request->user()->hasRole('admin') || $invoice->user_id === $request->user()->id, 403);

        \App\Models\AuditLog::record('exported', $invoice);

        $filename = 'fattura-' . str_replace('/', '-', $invoice->invoice_code) . '.pdf';

        // Fattura caricata: si restituisce il PDF originale, non quello generato.
        if ($file = $this->storedPdf($invoice)) {
            return new HttpResponse($file, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $invoice->load(['patient', 'lines', 'user.professionalProfile']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->download($filename);
    }

    /** Anteprima nel browser (inline) invece del download. */
    public function viewPdf(Request $request, Invoice $invoice): HttpResponse
    {
        abort_unless($request->user()->hasRole('admin') || $invoice->user_id === $request->user()->id, 403);

        \App\Models\AuditLog::record('viewed', $invoice);

        $filename = 'fattura-'.str_replace('/', '-', $invoice->invoice_code).'.pdf';

        if ($file = $this->storedPdf($invoice)) {
            return new HttpResponse($file, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        $invoice->load(['patient', 'lines', 'user.professionalProfile']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->stream($filename);
    }

    /**
     * Esporta in Excel tutti i dati della fattura (gli stessi del PDF) per ogni
     * paziente, rispettando i filtri anno/stato dell'elenco.
     */
    public function exportExcel(Request $request)
    {
        $year = ($request->year && $request->year !== 'tutti') ? (int) $request->year : null;

        $invoices = $this->scoped($request)
            ->with('lines')
            ->when($year, fn ($q) => $q->where('invoice_year', $year))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('invoice_year')
            ->orderBy('invoice_number')
            ->get();

        $headings = [
            'Codice fattura', 'Numero', 'Anno', 'Mese', 'Data emissione', 'Stato',
            'Paziente', 'Codice fiscale', 'Indirizzo', 'Email', 'Telefono',
            'Emessa da', 'P. IVA emittente', 'CF emittente', 'Regime fiscale',
            'Prestazioni', 'Imponibile', 'Marca da bollo', 'Totale',
            'Metodo di pagamento', 'Data pagamento', 'Inviata a STS',
        ];

        $statusLabels = ['draft' => 'Bozza', 'issued' => 'Emessa', 'paid' => 'Pagata', 'cancelled' => 'Annullata'];

        $rows = $invoices->map(function (Invoice $i) use ($statusLabels) {
            $month = (int) ($i->issued_at?->month ?: $i->created_at->month);

            $prestazioni = $i->lines
                ->map(fn ($l) => trim($l->description).' × '.$l->quantity.' = € '.number_format((float) $l->total, 2, ',', '.'))
                ->implode(' | ');

            return [
                $i->invoice_code,
                (int) $i->invoice_number,
                (int) $i->invoice_year,
                self::MESI[$month] ?? '',
                $i->issued_at?->format('d/m/Y') ?? '',
                $statusLabels[$i->status] ?? $i->status,
                $i->client_name,
                $i->client_codice_fiscale,
                $i->client_address,
                $i->client_email,
                $i->client_phone,
                $i->issuer_name,
                $i->issuer_partita_iva,
                $i->issuer_codice_fiscale,
                $i->issuer_regime_fiscale,
                $prestazioni,
                (float) $i->subtotal,
                (float) $i->marca_da_bollo,
                (float) $i->total,
                $i->payment_method,
                $i->paid_at?->format('d/m/Y') ?? '',
                $i->sts_sent ? 'Sì' : 'No',
            ];
        })->all();

        \App\Models\AuditLog::record('exported', null, null, [
            'note'  => 'export elenco fatture in Excel',
            'year'  => $year,
            'count' => count($rows),
        ]);

        $filename = 'fatture'.($year ? "-{$year}" : '');

        return SimpleXlsx::download($filename, $headings, $rows, 'Fatture'.($year ? " {$year}" : ''));
    }

    public function exportSts(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        // Qui andrà l'integrazione con il Sistema Tessera Sanitaria
        $invoice->update([
            'sts_sent' => true,
            'sts_sent_at' => now(),
        ]);

        return back()->with('success', 'Fattura inviata al STS.');
    }
}
