<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Generatore XLSX minimale (nessuna dipendenza esterna: su Aruba non possiamo
 * installare pacchetti). Scrive un singolo foglio con intestazione in grassetto
 * e celle stringa/numero. Se ext-zip non è disponibile ripiega su un CSV
 * compatibile con Excel italiano (BOM UTF-8 + separatore ';').
 */
class SimpleXlsx
{
    /**
     * @param  list<string>        $headings
     * @param  list<list<mixed>>   $rows   valori scalari: i numeric diventano celle numeriche
     */
    public static function download(string $filename, array $headings, array $rows, string $sheetName = 'Foglio1'): Response
    {
        if (! class_exists(\ZipArchive::class)) {
            return self::csvFallback($filename, $headings, $rows);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();

        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);

            return self::csvFallback($filename, $headings, $rows);
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($headings, $rows));
        $zip->close();

        $content = (string) file_get_contents($tmp);
        @unlink($tmp);

        return new Response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'.xlsx"',
        ]);
    }

    private static function csvFallback(string $filename, array $headings, array $rows): Response
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel legge gli accenti correttamente
        fputcsv($out, $headings, ';');
        foreach ($rows as $row) {
            fputcsv($out, array_map(fn ($v) => is_float($v) || is_int($v) ? str_replace('.', ',', (string) $v) : (string) $v, $row), ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'.csv"',
        ]);
    }

    /** Indice colonna (0-based) → lettera: 0=A, 26=AA. */
    private static function column(int $i): string
    {
        $letters = '';
        $i++;
        while ($i > 0) {
            $mod = ($i - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $i = (int) (($i - $mod) / 26);
        }

        return $letters;
    }

    private static function cell(string $ref, mixed $value, int $style = 0): string
    {
        $s = $style ? " s=\"{$style}\"" : '';

        if (is_int($value) || is_float($value)) {
            return "<c r=\"{$ref}\"{$s}><v>".self::num($value).'</v></c>';
        }

        $text = (string) $value;
        if ($text === '') {
            return "<c r=\"{$ref}\"{$s}/>";
        }

        // XML 1.0 non ammette caratteri di controllo.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';

        return "<c r=\"{$ref}\"{$s} t=\"inlineStr\"><is><t xml:space=\"preserve\">".htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8').'</t></is></c>';
    }

    private static function num(int|float $v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }

        $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        return $s === '' || $s === '-' ? '0' : $s;
    }

    private static function sheet(array $headings, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= '<row r="1">';
        foreach (array_values($headings) as $i => $h) {
            $xml .= self::cell(self::column($i).'1', $h, 1);
        }
        $xml .= '</row>';

        $r = 1;
        foreach ($rows as $row) {
            $r++;
            $xml .= "<row r=\"{$r}\">";
            foreach (array_values($row) as $i => $v) {
                $xml .= self::cell(self::column($i).$r, $v);
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = htmlspecialchars(mb_substr($sheetName, 0, 31), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<sheets><sheet name=\"{$name}\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>";
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
