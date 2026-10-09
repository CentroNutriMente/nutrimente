<?php

namespace Tests\Feature;

use App\Services\InvoicePdfExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Molte fatture prodotte dai gestionali usano font "subset": il testo è scritto
 * come stringhe esadecimali con codici propri, decifrabili solo tramite il CMap
 * /ToUnicode del documento. Qui costruiamo un PDF di quel tipo.
 */
class InvoicePdfHexFontTest extends TestCase
{
    use RefreshDatabase;

    /** PDF minimale con testo in esadecimale + CMap ToUnicode. */
    private function pdfConFontSubset(string $testo): string
    {
        // Codice arbitrario per ogni carattere: 0x0100 + indice.
        $chars = array_values(array_unique(preg_split('//u', $testo, -1, PREG_SPLIT_NO_EMPTY)));
        $codeOf = [];
        $bfchar = '';
        foreach ($chars as $i => $ch) {
            $code = sprintf('%04X', 0x0100 + $i);
            $codeOf[$ch] = $code;
            $bfchar .= "<{$code}> <".sprintf('%04X', mb_ord($ch, 'UTF-8')).">\n";
        }

        $hex = '';
        foreach (preg_split('//u', $testo, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $hex .= $codeOf[$ch];
        }

        $content = "BT /F1 12 Tf 50 700 Td <{$hex}> Tj ET";
        $cmap = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n"
            .count($chars)." beginbfchar\n{$bfchar}endbfchar\nendcmap\nend\nend";

        // Struttura realistica: la pagina dichiara /Font << /F1 6 0 R >>, il font
        // punta al proprio /ToUnicode. È così che l'estrattore associa la mappa.
        return "%PDF-1.4\n"
            ."1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            ."2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            ."3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n"
            ."4 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream\nendobj\n"
            ."5 0 obj\n<< /Length ".strlen($cmap)." >>\nstream\n{$cmap}\nendstream\nendobj\n"
            ."6 0 obj\n<< /Type /Font /Subtype /Type0 /BaseFont /AAAAAA+Helvetica /ToUnicode 5 0 R >>\nendobj\n"
            ."trailer\n<< /Root 1 0 R >>\n%%EOF";
    }

    public function test_legge_una_fattura_con_font_subset_ed_esadecimali(): void
    {
        $raw = $this->pdfConFontSubset(
            'Fattura N: 12/2026 Data fattura: 14/05/2026 '
            .'Codice Fiscale: MRUDNL85A01H501W '
            .'Metodo di pagamento: Bonifico IMPORTO TOTALE 1.234,56 EUR'
        );

        $testo = InvoicePdfExtractor::text($raw);
        $this->assertStringContainsString('IMPORTO TOTALE', $testo, 'Il CMap deve rendere il testo leggibile.');

        $campi = InvoicePdfExtractor::extract($raw);

        $this->assertTrue($campi['readable']);
        $this->assertSame('2026-05-14', $campi['issued_at'] ?? null);
        $this->assertSame(1234.56, $campi['total'] ?? null);
        $this->assertSame('MRUDNL85A01H501W', $campi['client_codice_fiscale'] ?? null);
        $this->assertSame('Bonifico', $campi['payment_method'] ?? null);
    }
}
