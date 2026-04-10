<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $invoices = Invoice::with('patient')
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->year, fn ($q) => $q->where('invoice_year', $request->year))
            ->orderByDesc('invoice_number')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'year']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('Invoices/Create', [
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'codice_fiscale']),
            'profile' => $user->professionalProfile()->firstOrCreate(['user_id' => $user->id]),
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
        $profile = $user->professionalProfile()->firstOrCreate(['user_id' => $user->id]);
        $patient = Patient::findOrFail($validated['patient_id']);

        $subtotal = collect($validated['lines'])->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
        $marcaDaBollo = Invoice::calculateMarcaDaBollo($subtotal);
        $total = $subtotal + $marcaDaBollo;

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

        return redirect()->route('invoices.index')->with('success', 'Fattura emessa.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        // Tutti i professionisti del gestionale possono visualizzare; solo il proprietario può modificare
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
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'codice_fiscale']),
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

        return redirect()->route('invoices.index')->with('success', 'Fattura annullata.');
    }

    public function downloadPdf(Request $request, Invoice $invoice): HttpResponse
    {

        $invoice->load(['patient', 'lines', 'user.professionalProfile']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        $filename = 'fattura-' . str_replace('/', '-', $invoice->invoice_code) . '.pdf';
        return $pdf->download($filename);
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
