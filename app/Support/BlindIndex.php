<?php

namespace App\Support;

/**
 * Blind index: hash deterministico (HMAC-SHA256) per consentire lookup esatti e
 * vincoli di unicità su colonne cifrate (es. email, codice fiscale). Il valore in
 * chiaro resta cifrato; accanto si memorizza solo l'hash, su cui si effettuano
 * WHERE e indici UNIQUE.
 */
class BlindIndex
{
    public static function hash(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', mb_strtolower(trim($value)), (string) config('app.blind_index_key'));
    }
}
