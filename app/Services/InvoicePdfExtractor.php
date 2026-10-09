<?php

namespace App\Services;

use App\Models\Patient;

/**
 * Estrazione dei campi di una fattura da PDF, senza dipendenze esterne
 * (su Aruba non possiamo installare pacchetti): porting di App\Services\FatturaPdf
 * del progetto ibenefit-laravel, adattato allo schema NutriMente.
 *
 * Legge il testo dagli stream BT…ET (Tj e array TJ), applica un de-kerning
 * condizionale e poi un estrattore per template. Riconosce le fatture generate
 * da NutriMente ("IMPORTO TOTALE", "Data fattura:", "Nº:") e le fatture
 * elettroniche Aruba PEC ("FORNITORE … CLIENTE … PRODOTTI E SERVIZI").
 *
 * È un aiuto alla compilazione: i valori restituiti vanno sempre confermati
 * dall'utente prima del salvataggio.
 */
class InvoicePdfExtractor
{
    private const CF_RE = '[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]';

    /**
     * @return array{invoice_code?:string, issued_at?:string, total?:float,
     *               payment_method?:string, client_codice_fiscale?:string,
     *               client_name?:string, patient_id?:int, template:string, readable:bool}
     */
    public static function extract(string $raw): array
    {
        $t = self::deKern(preg_replace('/[^\\x20-\\x7E]+/', ' ', self::text($raw)) ?? '');

        if (trim($t) === '') {
            // PDF scansionato o con font senza mappa: nessun testo da leggere.
            return ['template' => 'nessuno', 'readable' => false];
        }

        $isArubaPEC = stripos($t, 'FORNITORE') !== false
            && (stripos($t, 'PRODOTTI E SERVIZI') !== false || stripos($t, 'PRODOTTIESERVIZI') !== false);

        $result = $isArubaPEC ? self::templateArubaPEC($t) : self::templateStandard($t);
        $result['template'] = $isArubaPEC ? 'Aruba PEC' : 'standard';
        $result['readable'] = true;

        // Risoluzione del paziente tramite blind index del codice fiscale: il CF
        // e' cifrato a riposo, quindi il confronto avviene sull'hash.
        if (! empty($result['client_codice_fiscale'])) {
            $patient = Patient::whereBlind('codice_fiscale', $result['client_codice_fiscale'])->first();
            if ($patient) {
                $result['patient_id'] = $patient->id;
                $result['client_name'] = trim("{$patient->last_name} {$patient->first_name}");
            }
        }

        return $result;
    }

    /** Testo grezzo estratto dal PDF (esposto per diagnosi e test). */
    public static function text(string $raw): string
    {
        $text = '';

        // Estrattore primario, se un giorno il pacchetto fosse disponibile.
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $tmp = tempnam(sys_get_temp_dir(), 'nmfat');
                file_put_contents($tmp, $raw);
                $text = (new \Smalot\PdfParser\Parser)->parseFile($tmp)->getText();
                @unlink($tmp);
            } catch (\Throwable) {
                $text = '';
            }
        }

        if (! trim($text)) {
            $objects = self::objects($raw);
            $fontMaps = self::fontMaps($objects);

            // Se i font non sono risolvibili (risorse in object stream, PDF
            // atipici) si ripiega su un'unica mappa costruita da tutti i CMap.
            $default = $fontMaps ? [] : self::toUnicodeMap(self::streams($raw));

            $text = self::textFromStreams(self::contentStreams($objects, $raw), $fontMaps, $default);
        }

        if (! trim($text)) {
            $text = self::readStreams($raw);
        }

        if (! trim($text)) {
            preg_match_all('/BT\s*(.*?)\s*ET/s', $raw, $m);
            foreach ($m[1] as $block) {
                preg_match_all('/\(([^)]*)\)\s*Tj/', $block, $tj);
                $text .= implode(' ', $tj[1]).' ';
            }
        }

        return $text;
    }

    /**
     * Oggetti indiretti del documento: numero → ['dict' => …, 'stream' => …].
     * Serve a legare ogni font alla propria /ToUnicode: un'unica mappa globale
     * mescolerebbe font diversi (etichette leggibili, valori illeggibili).
     */
    private static function objects(string $raw): array
    {
        $objects = [];

        if (! preg_match_all('/(\d+)\s+\d+\s+obj\b(.*?)\bendobj/s', $raw, $m, PREG_SET_ORDER)) {
            return $objects;
        }

        foreach ($m as $o) {
            $body = $o[2];
            $stream = null;
            $dict = $body;

            $sp = strpos($body, 'stream');
            if ($sp !== false) {
                $dict = substr($body, 0, $sp);
                $start = $sp + 6;
                if (($body[$start] ?? '') === "\r") {
                    $start++;
                }
                if (($body[$start] ?? '') === "\n") {
                    $start++;
                }
                $end = strpos($body, 'endstream', $start);
                if ($end !== false) {
                    $data = substr($body, $start, $end - $start);
                    $stream = @gzuncompress($data);
                    if ($stream === false) {
                        $stream = @gzinflate($data);
                    }
                    if ($stream === false) {
                        $stream = @gzdecode($data);
                    }
                    if ($stream === false) {
                        $stream = $data;
                    }
                }
            }

            $objects[(int) $o[1]] = ['dict' => $dict, 'stream' => $stream];
        }

        return $objects;
    }

    /** Stream che contengono blocchi di testo (BT…ET). */
    private static function contentStreams(array $objects, string $raw): array
    {
        $out = [];
        foreach ($objects as $o) {
            if ($o['stream'] !== null && str_contains($o['stream'], 'BT')) {
                $out[] = $o['stream'];
            }
        }

        return $out ?: self::streams($raw);
    }

    /**
     * Mappa dei font: nome risorsa (F1, F2, …) → CMap /ToUnicode del font.
     * Il testo va decodificato con la mappa del font attivo al momento.
     */
    private static function fontMaps(array $objects): array
    {
        $maps = [];

        foreach ($objects as $o) {
            if (! preg_match_all('/\/Font\s*<<(.*?)>>/s', $o['dict'], $blocks)) {
                continue;
            }

            foreach ($blocks[1] as $block) {
                if (! preg_match_all('/\/([A-Za-z0-9#]+)\s+(\d+)\s+\d+\s+R/', $block, $refs, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($refs as $ref) {
                    $name = $ref[1];
                    $fontObj = $objects[(int) $ref[2]] ?? null;
                    if (! $fontObj || isset($maps[$name])) {
                        continue;
                    }

                    if (! preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $fontObj['dict'], $tu)) {
                        continue;
                    }

                    $cmapStream = $objects[(int) $tu[1]]['stream'] ?? null;
                    if ($cmapStream) {
                        $map = self::parseCMap($cmapStream);
                        if ($map) {
                            $maps[$name] = $map;
                        }
                    }
                }
            }
        }

        return $maps;
    }

    /** Tutti gli stream del documento, decompressi quando possibile. */
    private static function streams(string $raw): array
    {
        $out = [];
        $pos = 0;
        $len = strlen($raw);

        while ($pos < $len) {
            $s = strpos($raw, 'stream', $pos);
            if ($s === false) {
                break;
            }

            $eol = $s + 6;
            if (($raw[$eol] ?? '') === "\r") {
                $eol++;
            }
            if (($raw[$eol] ?? '') !== "\n") {
                $pos = $s + 1;

                continue;
            }

            $dataStart = $eol + 1;
            $e = strpos($raw, 'endstream', $dataStart);
            if ($e === false) {
                break;
            }

            $dataEnd = $e;
            while ($dataEnd > $dataStart && ($raw[$dataEnd - 1] === "\n" || $raw[$dataEnd - 1] === "\r")) {
                $dataEnd--;
            }

            $data = substr($raw, $dataStart, $dataEnd - $dataStart);
            $decoded = @gzuncompress($data);
            if ($decoded === false) {
                $decoded = @gzinflate($data);
            }
            if ($decoded === false) {
                $decoded = @gzdecode($data);
            }

            $out[] = $decoded === false ? $data : $decoded;
            $pos = $e + 9;
        }

        return $out;
    }

    /**
     * Mappa globale codice → carattere ricavata dai CMap /ToUnicode del documento.
     * I font "subset" (tipici delle fatture generate da gestionali) codificano il
     * testo con codici propri: senza questa mappa si legge solo spazzatura.
     */
    private static function toUnicodeMap(array $streams): array
    {
        $map = [];
        foreach ($streams as $s) {
            $map += self::parseCMap($s);
        }

        return $map;
    }

    /** CMap /ToUnicode di un singolo font: codice esadecimale → carattere. */
    private static function parseCMap(string $s): array
    {
        $map = [];

        foreach ([$s] as $s) {
            if (! str_contains($s, 'beginbfchar') && ! str_contains($s, 'beginbfrange')) {
                continue;
            }

            // <src> <dst>
            if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $s, $blocks)) {
                foreach ($blocks[1] as $block) {
                    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $pairs, PREG_SET_ORDER)) {
                        foreach ($pairs as $p) {
                            $map[strtoupper($p[1])] = self::utf16ToUtf8($p[2]);
                        }
                    }
                }
            }

            // <lo> <hi> <dstStart>  |  <lo> <hi> [<d1> <d2> …]
            if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $s, $blocks)) {
                foreach ($blocks[1] as $block) {
                    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $ranges, PREG_SET_ORDER)) {
                        foreach ($ranges as $r) {
                            $lo = hexdec($r[1]);
                            $hi = hexdec($r[2]);
                            $dst = hexdec(substr($r[3], 0, 4));
                            $width = strlen($r[1]);
                            for ($c = $lo; $c <= $hi && $c - $lo < 512; $c++) {
                                $map[strtoupper(str_pad(dechex($c), $width, '0', STR_PAD_LEFT))] =
                                    mb_convert_encoding(pack('n', $dst + ($c - $lo)), 'UTF-8', 'UTF-16BE');
                            }
                        }
                    }
                    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*\[(.*?)\]/s', $block, $ranges, PREG_SET_ORDER)) {
                        foreach ($ranges as $r) {
                            $lo = hexdec($r[1]);
                            $width = strlen($r[1]);
                            preg_match_all('/<([0-9A-Fa-f]+)>/', $r[3], $dsts);
                            foreach ($dsts[1] as $k => $dst) {
                                $map[strtoupper(str_pad(dechex($lo + $k), $width, '0', STR_PAD_LEFT))] = self::utf16ToUtf8($dst);
                            }
                        }
                    }
                }
            }
        }

        return $map;
    }

    private static function utf16ToUtf8(string $hex): string
    {
        if (strlen($hex) % 4 !== 0) {
            $hex = str_pad($hex, (int) (ceil(strlen($hex) / 4) * 4), '0', STR_PAD_LEFT);
        }

        return (string) mb_convert_encoding((string) @hex2bin($hex), 'UTF-8', 'UTF-16BE');
    }

    /** Testo dei blocchi BT…ET gestendo stringhe letterali, esadecimali e kerning. */
    private static function textFromStreams(array $streams, array $fontMaps, array $default = []): string
    {
        $text = '';
        $widthOf = static function (array $map): int {
            foreach (array_keys($map) as $k) {
                return max(1, (int) (strlen($k) / 2));
            }

            return 2;
        };

        foreach ($streams as $stream) {
            if (! preg_match_all('/BT\s*(.*?)\s*ET/s', $stream, $blocks)) {
                continue;
            }

            foreach ($blocks[1] as $block) {
                $line = '';
                $i = 0;
                $n = strlen($block);

                // Font attivo: cambia con "/Fx <size> Tf" dentro il blocco.
                $cmap = $default;
                $codeWidth = $default ? $widthOf($default) : 2;
                $pendingFont = null;

                while ($i < $n) {
                    $c = $block[$i];

                    // Nome di risorsa: /F1, /TT2… seguito (prima o poi) da Tf.
                    if ($c === '/') {
                        $j = $i + 1;
                        while ($j < $n && preg_match('/[A-Za-z0-9#]/', $block[$j])) {
                            $j++;
                        }
                        $pendingFont = substr($block, $i + 1, $j - $i - 1);
                        $i = $j;

                        continue;
                    }

                    if ($c === 'T' && ($block[$i + 1] ?? '') === 'f') {
                        if ($pendingFont !== null && isset($fontMaps[$pendingFont])) {
                            $cmap = $fontMaps[$pendingFont];
                            $codeWidth = $widthOf($cmap);
                        }
                        $i += 2;

                        continue;
                    }

                    if ($c === '(') {
                        [$t, $i] = self::readLiteral($block, $i + 1);
                        $line .= $t;

                        continue;
                    }

                    if ($c === '<' && ($block[$i + 1] ?? '') !== '<') {
                        $j = strpos($block, '>', $i);
                        if ($j !== false) {
                            $line .= self::decodeHex(substr($block, $i + 1, $j - $i - 1), $cmap, $codeWidth);
                            $i = $j + 1;

                            continue;
                        }
                    }

                    if ($c === '[') {
                        [$t, $i] = self::readTjArray($block, $i + 1, $cmap, $codeWidth);
                        $line .= $t;

                        continue;
                    }

                    $i++;
                }

                $line = trim($line);
                if ($line !== '') {
                    $text .= $line."\n";
                }
            }
        }

        return $text;
    }

    /** Stringa esadecimale → testo, tramite CMap se disponibile. */
    private static function decodeHex(string $hex, array $cmap, int $codeWidth): string
    {
        $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $hex) ?? '');
        if ($hex === '') {
            return '';
        }

        if ($cmap) {
            $step = $codeWidth * 2;
            if (strlen($hex) % $step) {
                $hex = str_pad($hex, (int) (ceil(strlen($hex) / $step) * $step), '0');
            }
            $out = '';
            foreach (str_split($hex, $step) as $code) {
                $out .= $cmap[$code] ?? '';
            }

            return $out;
        }

        if (strlen($hex) % 2) {
            $hex .= '0';
        }

        return self::decodeBytes((string) @hex2bin($hex));
    }

    /** Array [(testo) kern …] TJ: il kerning molto negativo vale come spazio. */
    private static function readTjArray(string $b, int $i, array $cmap, int $codeWidth): array
    {
        $out = '';
        $num = '';
        $n = strlen($b);

        while ($i < $n) {
            $c = $b[$i];
            $isNum = ($c === '-' || $c === '.' || ($c >= '0' && $c <= '9'));

            if (! $isNum && $num !== '') {
                if ((float) $num <= -120) {
                    $out .= ' ';
                }
                $num = '';
            }

            if ($c === ']') {
                $i++;
                break;
            }
            if ($c === '(') {
                [$t, $i] = self::readLiteral($b, $i + 1);
                $out .= $t;

                continue;
            }
            if ($c === '<') {
                $j = strpos($b, '>', $i);
                if ($j !== false) {
                    $out .= self::decodeHex(substr($b, $i + 1, $j - $i - 1), $cmap, $codeWidth);
                    $i = $j + 1;

                    continue;
                }
            }
            if ($isNum) {
                $num .= $c;
                $i++;

                continue;
            }
            $i++;
        }

        return [$out, $i];
    }

    /** Legge una stringa letterale (…) gestendo parentesi annidate ed escape. */
    private static function readLiteral(string $b, int $i): array
    {
        $depth = 1;
        $buf = '';
        $n = strlen($b);

        while ($i < $n) {
            $c = $b[$i];
            if ($c === '\\') {
                $buf .= $c.($b[$i + 1] ?? '');
                $i += 2;

                continue;
            }
            if ($c === '(') {
                $depth++;
            }
            if ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    $i++;
                    break;
                }
            }
            $buf .= $c;
            $i++;
        }

        return [self::decodeLiteral($buf), $i];
    }

    /** Risolve gli escape di una stringa letterale PDF. */
    private static function decodeLiteral(string $s): string
    {
        $bytes = '';
        $n = strlen($s);

        for ($i = 0; $i < $n; $i++) {
            $c = $s[$i];
            if ($c !== '\\') {
                $bytes .= $c;

                continue;
            }

            $next = $s[$i + 1] ?? '';
            if ($next === '') {
                break;
            }

            if ($next >= '0' && $next <= '7') {
                $oct = $next;
                $i++;
                for ($k = 0; $k < 2; $k++) {
                    $nn = $s[$i + 1] ?? '';
                    if ($nn >= '0' && $nn <= '7') {
                        $oct .= $nn;
                        $i++;
                    } else {
                        break;
                    }
                }
                $bytes .= chr(octdec($oct) & 0xFF);

                continue;
            }

            $bytes .= match ($next) {
                'n'  => "\n",
                'r'  => "\r",
                't'  => "\t",
                'b'  => "\x08",
                'f'  => "\x0C",
                "\n" => '',
                default => $next,
            };
            $i++;
        }

        return self::decodeBytes($bytes);
    }

    /** Byte di una stringa PDF → testo (Latin-1 + legature). */
    private static function decodeBytes(string $bytes): string
    {
        static $lig = ["\x0B" => 'ff', "\x0C" => 'fi', "\x0D" => 'fl', "\x0E" => 'ffi', "\x0F" => 'ffl'];
        $out = '';

        for ($i = 0, $n = strlen($bytes); $i < $n; $i++) {
            $c = $bytes[$i];
            $o = ord($c);

            if (isset($lig[$c])) {
                $out .= $lig[$c];
            } elseif ($o === 9 || $o === 10 || $o === 13 || $o === 32) {
                $out .= ' ';
            } elseif ($o >= 32 && $o < 127) {
                $out .= $c;
            } elseif ($o >= 160) {
                $out .= mb_convert_encoding($c, 'UTF-8', 'ISO-8859-1');
            }
        }

        return $out;
    }

    /** Testo dagli stream BT…ET (Tj e array TJ), con inflate quando compressi. */
    private static function readStreams(string $raw): string
    {
        $text = '';
        $pos = 0;
        $len = strlen($raw);

        while ($pos < $len) {
            $s = strpos($raw, 'stream', $pos);
            if ($s === false) {
                break;
            }

            $eol = $s + 6;
            if (($raw[$eol] ?? '') === "\r") {
                $eol++;
            }
            if (($raw[$eol] ?? '') !== "\n") {
                $pos = $s + 1;

                continue;
            }

            $dataStart = $eol + 1;
            $e = strpos($raw, 'endstream', $dataStart);
            if ($e === false) {
                break;
            }

            $dataEnd = $e;
            if ($dataEnd > $dataStart && $raw[$dataEnd - 1] === "\n") {
                $dataEnd--;
            }
            if ($dataEnd > $dataStart && $raw[$dataEnd - 1] === "\r") {
                $dataEnd--;
            }

            $data = substr($raw, $dataStart, $dataEnd - $dataStart);
            $decoded = @gzuncompress($data);
            if ($decoded === false) {
                $decoded = @gzinflate($data);
            }
            if ($decoded === false) {
                $decoded = $data;
            }

            if (preg_match_all('/BT\s*(.*?)\s*ET/s', $decoded, $blocks)) {
                foreach ($blocks[1] as $block) {
                    preg_match_all('/\(([^)]*)\)\s*Tj/', $block, $tj);
                    if ($tj[1]) {
                        $text .= implode(' ', $tj[1]).' ';
                    }
                    preg_match_all('/\[([^\]]*)\]\s*TJ/s', $block, $tjArr);
                    foreach ($tjArr[1] as $arr) {
                        preg_match_all('/\(([^)]*)\)/', $arr, $strings);
                        $text .= implode('', $strings[1]).' ';
                    }
                }
            }

            $pos = $e + 9;
        }

        return $text;
    }

    /**
     * Rimuove gli spazi intra-carattere SOLO se il testo è davvero kernato
     * ("F A T T U R A"), altrimenti lo lascia intatto.
     */
    private static function deKern(string $text): string
    {
        $tokens = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $total = count($tokens);

        if ($total >= 8) {
            $single = 0;
            foreach ($tokens as $tok) {
                if (strlen($tok) === 1) {
                    $single++;
                }
            }
            if ($single / $total < 0.4) {
                return $text;
            }
        }

        $t = preg_replace('/  +/', "\x01", $text);
        $t = str_replace(' ', '', (string) $t);

        return str_replace("\x01", ' ', $t);
    }

    /** Cerca un codice fiscale in una porzione di testo, robusto al kerning. */
    private static function findCf(string $region): ?string
    {
        $up = strtoupper($region);
        $re = '[A-Z0-9]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]';

        if (preg_match('/(?:CODICE\s*FISCALE|C\.?\s*F\.?)\s*:?\s*('.$re.')/i', $up, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b('.$re.')\b/i', $up, $m)) {
            return strtoupper($m[1]);
        }
        $despaced = preg_replace('/\s+/', '', $up) ?? '';
        if (preg_match('/('.$re.')/', $despaced, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /** Importo "1.234,56" → 1234.56 */
    private static function amount(string $s): float
    {
        $n = preg_replace('/\.(?=\d{3}(?:[,\s]|$))/', '', $s) ?? $s;

        return (float) str_replace(',', '.', $n);
    }

    /** Fatture NutriMente e simili: "IMPORTO TOTALE", "Data fattura:", "Nº:". */
    private static function templateStandard(string $t): array
    {
        $result = [];

        if (preg_match('/IMPORTO TOTALE\s+([\d.,]+)\s*€?/ui', $t, $m)) {
            $result['total'] = self::amount($m[1]);
        } elseif (preg_match('/TOTALE(?:\s+DOCUMENTO|\s+FATTURA)?\s*:?\s*€?\s*([\d.,]+)/ui', $t, $m)) {
            $result['total'] = self::amount($m[1]);
        }

        if (preg_match('/Data\s*fattura\s*:?\s*(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/i', $t, $m)) {
            $result['issued_at'] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } elseif (preg_match('/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $t, $m)) {
            $result['issued_at'] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/N[º°o]?\s*[:.]?\s*(\d[\w\/\-]*)/u', $t, $m)) {
            $result['invoice_code'] = trim($m[1]);
        }

        // Dopo l'etichetta si prende un metodo noto; in mancanza, la prima parola.
        if (preg_match('/Metodo di pagamento\s*:?\s*([^\n]{0,60})/i', $t, $m)) {
            $coda = trim(preg_replace('/\s+/', ' ', $m[1]) ?? '');

            if (preg_match('/(carta di credito\s*\/\s*debito|carta di credito|carta di debito|bancomat|carta|contanti|bonifico bancario|bonifico|paypal|satispay|assegno|rimessa diretta|pos)/i', $coda, $mm)) {
                $result['payment_method'] = trim($mm[1]);
            } else {
                $prima = preg_split('/\s+/', $coda)[0] ?? '';
                if (preg_match('/^[A-Za-zÀ-ÿ\']{3,20}$/u', $prima)) {
                    $result['payment_method'] = $prima;
                }
            }
        }

        // Il primo CF del documento è dell'emittente, il secondo del cliente.
        $despaced = strtoupper(preg_replace('/\s+/', '', $t) ?? '');
        if (preg_match_all('/('.self::CF_RE.')/', $despaced, $m)) {
            $cfs = array_values(array_unique($m[1]));
            $result['client_codice_fiscale'] = strtoupper($cfs[count($cfs) >= 2 ? 1 : 0]);
        } elseif ($cf = self::findCf($t)) {
            $result['client_codice_fiscale'] = $cf;
        }

        return $result;
    }

    /** Fattura elettronica Aruba PEC. */
    private static function templateArubaPEC(string $t): array
    {
        $result = [];

        if (preg_match('/nr\.\s*([\w\/\-]+)/i', $t, $m)) {
            $result['invoice_code'] = trim($m[1]);
        }

        if (preg_match('/del\s+(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/i', $t, $m)) {
            $result['issued_at'] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/Netto a pagare\s+([\d.,]+)/i', $t, $m)) {
            $result['total'] = self::amount($m[1]);
        } elseif (preg_match('/Totale documento\s+([\d.,]+)/i', $t, $m)) {
            $result['total'] = self::amount($m[1]);
        }

        $fornitoreCf = null;
        if (preg_match('/FORNITORE(.+?)CLIENTE/is', $t, $sec)) {
            $fornitoreCf = self::findCf($sec[1]);
        }

        if (preg_match('/CLIENTE(.+?)PRODOTTI E SERVIZI/is', $t, $sec)) {
            $result['client_codice_fiscale'] = self::findCf($sec[1]);
        }

        // Fallback: cliente = ultimo CF del documento diverso dal fornitore.
        if (empty($result['client_codice_fiscale'])) {
            $despaced = strtoupper(preg_replace('/\s+/', '', $t) ?? '');
            if (preg_match_all('/'.self::CF_RE.'/', $despaced, $all)) {
                foreach (array_reverse($all[0]) as $cand) {
                    if (strtoupper($cand) !== $fornitoreCf) {
                        $result['client_codice_fiscale'] = strtoupper($cand);
                        break;
                    }
                }
            }
        }

        if (preg_match('/(Carta di pagamento|Bonifico bancario|Bonifico|Contanti|Rimessa diretta|PayPal|Satispay)/i', $t, $m)) {
            $result['payment_method'] = trim($m[0]);
        }

        return array_filter($result, fn ($v) => $v !== null);
    }
}
