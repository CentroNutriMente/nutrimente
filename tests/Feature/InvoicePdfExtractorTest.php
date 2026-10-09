<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Services\InvoicePdfExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePdfExtractorTest extends TestCase
{
    use RefreshDatabase;

    private function professionista(): User
    {
        Role::findOrCreate('psicologo', 'web');
        $user = User::factory()->create(['name' => 'Sara Alessandri']);
        $user->assignRole('psicologo');

        return $user;
    }

    /** Genera un PDF fattura reale con lo stesso template dell'app. */
    private function pdfFattura(User $user, Patient $patient): string
    {
        $invoice = Invoice::create([
            'user_id'               => $user->id,
            'patient_id'            => $patient->id,
            'invoice_number'        => 7,
            'invoice_year'          => 2026,
            'invoice_code'          => '7/2026',
            'issuer_name'           => $user->name,
            'issuer_codice_fiscale' => 'LSSSRA85A41H501K',
            'issuer_regime_fiscale' => 'forfettario',
            'client_name'           => trim("{$patient->first_name} {$patient->last_name}"),
            'client_codice_fiscale' => $patient->codice_fiscale,
            'subtotal'              => 56.10,
            'marca_da_bollo'        => 0,
            'total'                 => 56.10,
            'iva_exempt'            => true,
            'payment_method'        => 'carta',
            'issued_at'             => '2026-05-14',
            'status'                => 'issued',
        ]);
        $invoice->lines()->create(['description' => 'Seduta psicologica', 'quantity' => 1, 'unit_price' => 56.10, 'total' => 56.10]);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice->load(['patient', 'lines', 'user.professionalProfile'])]);
        $raw = $pdf->output();

        $invoice->forceDelete(); // resta solo il PDF: simuliamo un file esterno

        return $raw;
    }

    public function test_estrae_i_campi_da_una_fattura_pdf(): void
    {
        $user = $this->professionista();
        $patient = Patient::create([
            'first_name'     => 'Danilo',
            'last_name'      => 'Muru',
            'codice_fiscale' => 'MRUDNL85A01H501W',
            'created_by'     => $user->id,
        ]);

        $campi = InvoicePdfExtractor::extract($this->pdfFattura($user, $patient));

        $this->assertTrue($campi['readable'], 'Il testo del PDF deve essere leggibile.');
        $this->assertSame('2026-05-14', $campi['issued_at'] ?? null);
        $this->assertSame(56.10, $campi['total'] ?? null);
        $this->assertSame('MRUDNL85A01H501W', $campi['client_codice_fiscale'] ?? null);
        $this->assertSame($patient->id, $campi['patient_id'] ?? null, 'Il paziente si risolve dal codice fiscale.');
        $this->assertSame('Muru Danilo', $campi['client_name'] ?? null);
        $this->assertSame('07', $campi['invoice_code'] ?? null);
    }

    public function test_l_endpoint_di_analisi_propone_i_campi_senza_salvare(): void
    {
        Storage::fake('local');
        $user = $this->professionista();
        $patient = Patient::create([
            'first_name'     => 'Danilo',
            'last_name'      => 'Muru',
            'codice_fiscale' => 'MRUDNL85A01H501W',
            'created_by'     => $user->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('fattura.pdf', $this->pdfFattura($user, $patient));

        $this->actingAs($user)
            ->post(route('invoices.upload.analyze'), ['file' => $file])
            ->assertOk()
            ->assertJson([
                'issued_at'  => '2026-05-14',
                'total'      => 56.10,
                'patient_id' => $patient->id,
                'readable'   => true,
            ]);

        $this->assertSame(0, Invoice::count(), 'L\'analisi non deve creare fatture.');
    }

    public function test_un_pdf_senza_testo_non_blocca_il_caricamento(): void
    {
        $campi = InvoicePdfExtractor::extract('%PDF-1.4 scansione senza testo');

        $this->assertFalse($campi['readable']);
        $this->assertArrayNotHasKey('total', $campi);
    }
}
