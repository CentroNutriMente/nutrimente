<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

/**
 * Ripristina un backup cifrato creato da `backup:files`: decifra l'archivio .enc
 * (richiede la stessa APP_KEY) e lo scompatta in una cartella di destinazione.
 */
class RestoreFiles extends Command
{
    protected $signature = 'backup:restore-files {file : Path del file .zip.enc} {--to= : Cartella di destinazione}';

    protected $description = 'Ripristina un backup cifrato degli allegati';

    public function handle(): int
    {
        $enc = $this->argument('file');
        if (! File::exists($enc)) {
            $this->error('File non trovato: '.$enc);

            return self::FAILURE;
        }

        $to = $this->option('to') ?: storage_path('app/restored_'.now()->format('Ymd_His'));
        File::ensureDirectoryExists($to);

        $tmpZip = tempnam(sys_get_temp_dir(), 'rst').'.zip';
        File::put($tmpZip, Crypt::decryptString(File::get($enc)));

        $zip = new \ZipArchive;
        if ($zip->open($tmpZip) !== true) {
            $this->error('Archivio non valido (o APP_KEY errata).');

            return self::FAILURE;
        }
        $zip->extractTo($to);
        $zip->close();
        File::delete($tmpZip);

        $this->info('Ripristinato in: '.$to);

        return self::SUCCESS;
    }
}
