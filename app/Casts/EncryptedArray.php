<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Variante array/JSON del cast {@see Encrypted}: il contenuto viene serializzato in
 * JSON e cifrato. Tollerante con righe legacy in chiaro (JSON non cifrato).
 */
class EncryptedArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = Crypt::decryptString($value);
        } catch (DecryptException) {
            $decoded = $value; // riga legacy: JSON ancora in chiaro
        }

        return json_decode($decoded, true);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value));
    }
}
