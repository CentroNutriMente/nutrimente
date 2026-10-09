<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceUploadTest extends TestCase
{
    use RefreshDatabase;

    private function professionista(): User
    {
        Role::findOrCreate('psicologo', 'web');
        $user = User::factory()->create();
        $user->assignRole('psicologo');

        return $user;
    }

    public function test_carica_una_fattura_pdf_e_la_ritrova_nell_archivio(): void
    {
        Storage::fake('local');

        $user = $this->professionista();
        $patient = Patient::create(['first_name' => 'Danilo', 'last_name' => 'Muru', 'created_by' => $user->id]);

        $contenuto = '%PDF-1.4 fattura originale';
        $file = UploadedFile::fake()->createWithContent('fattura.pdf', $contenuto);

        $this->actingAs($user)->post(route('invoices.upload.store'), [
            'file'           => $file,
            'patient_id'     => $patient->id,
            'invoice_code'   => '22/4-PS7/2026',
            'issued_at'      => '2026-07-23',
            'total'          => 56.10,
            'payment_method' => 'carta',
            'status'         => 'issued',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $invoice = Invoice::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(22, $invoice->invoice_number, 'Il numero si ricava dalle cifre del codice.');
        $this->assertSame(2026, $invoice->invoice_year);
        $this->assertSame('Danilo Muru', $invoice->client_name);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);

        // Il file su disco è cifrato, non il PDF in chiaro.
        $this->assertNotSame($contenuto, Storage::disk('local')->get($invoice->pdf_path));

        // Compare nell'archivio, nel mese di emissione.
        $this->actingAs($user)->get(route('invoices.index', ['year' => 2026]))
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/Index')
                ->where('months.0.label', 'Luglio 2026')
                ->where('months.0.invoices.0.invoice_code', '22/4-PS7/2026')
                ->where('months.0.invoices.0.uploaded', true));

        // Download e anteprima restituiscono il PDF originale, non quello generato.
        $download = $this->actingAs($user)->get(route('invoices.pdf', $invoice->id));
        $download->assertOk();
        $this->assertSame($contenuto, $download->getContent());

        $view = $this->actingAs($user)->get(route('invoices.pdf.view', $invoice->id));
        $view->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $view->headers->get('content-disposition'));
    }

    public function test_il_codice_fattura_non_puo_essere_duplicato(): void
    {
        Storage::fake('local');
        $user = $this->professionista();

        $payload = fn () => [
            'file'         => UploadedFile::fake()->createWithContent('f.pdf', '%PDF-1.4'),
            'client_name'  => 'Cliente Esterno',
            'invoice_code' => '1/2026',
            'issued_at'    => '2026-03-01',
            'total'        => 100,
            'status'       => 'paid',
        ];

        $this->actingAs($user)->post(route('invoices.upload.store'), $payload())->assertRedirect();
        $this->actingAs($user)->post(route('invoices.upload.store'), $payload())
            ->assertSessionHasErrors('invoice_code');

        $this->assertSame(1, Invoice::where('user_id', $user->id)->count());
    }
}
