<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast di cifratura "tollerante" per stringhe sensibili (GDPR, dati a riposo).
 *
 * In lettura prova a decifrare; se il valore non è cifrato (riga legacy in chiaro,
 * durante la migrazione one-shot) restituisce il valore grezzo. In scrittura cifra
 * sempre. Questo consente un rollout senza downtime su dati già esistenti.
 */
class Encrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // riga legacy ancora in chiaro
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
