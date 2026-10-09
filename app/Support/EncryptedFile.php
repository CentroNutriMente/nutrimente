<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Salvataggio/lettura di file cifrati a riposo (GDPR). Il contenuto è cifrato con
 * la APP_KEY prima di essere scritto su disco; nome/tipo/dimensione reali restano
 * nel DB (cifrati dai cast del modello). Tollerante con file legacy in chiaro.
 */
class EncryptedFile
{
    /** Cifra e salva un file caricato; ritorna il path relativo sul disco. */
    public static function store(UploadedFile $file, string $dir, string $disk): string
    {
        $path = trim($dir, '/').'/'.Str::random(40).'.enc';
        Storage::disk($disk)->put($path, Crypt::encryptString(file_get_contents($file->getRealPath())));

        return $path;
    }

    /** Ritorna il contenuto in chiaro del file (decifra; se legacy in chiaro lo ritorna tale). */
    public static function get(string $disk, string $path): string
    {
        $raw = Storage::disk($disk)->get($path);

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return $raw; // file legacy non ancora cifrato
        }
    }
}
