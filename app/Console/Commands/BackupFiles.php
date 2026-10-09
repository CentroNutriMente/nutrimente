<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

/**
 * Crea un backup CIFRATO degli allegati (storage/app). I file sono già cifrati a
 * riposo; l'archivio viene ulteriormente cifrato con APP_KEY, quindi senza la
 * chiave è illeggibile. Il DB va esportato a parte (phpMyAdmin / mysqldump);
 * le sue colonne sensibili sono comunque cifrate. Vedi deploy/aruba/BACKUP.md.
 */
class BackupFiles extends Command
{
    protected $signature = 'backup:files';

    protected $description = 'Crea un archivio cifrato degli allegati (storage/app)';

    public function handle(): int
    {
        $source  = storage_path('app');
        $backups = storage_path('backups');
        File::ensureDirectoryExists($backups);

        $stamp   = now()->format('Ymd_His');
        $zipPath = $backups.'/files_'.$stamp.'.zip';
        $encPath = $backups.'/files_'.$stamp.'.zip.enc';

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->error('Impossibile creare l\'archivio.');

            return self::FAILURE;
        }

        $files = File::allFiles($source);
        foreach ($files as $file) {
            // Salta la cartella dei backup stessi.
            if (str_starts_with($file->getPathname(), $backups)) {
                continue;
            }
            $zip->addFile($file->getPathname(), $file->getRelativePathname());
        }
        $zip->close();

        // Cifratura dell'intero archivio con APP_KEY.
        File::put($encPath, Crypt::encryptString(File::get($zipPath)));
        File::delete($zipPath);

        $this->info('Backup cifrato creato: '.$encPath.' ('.count($files).' file)');
        $this->line('Scaricalo via FTP e conservane una copia OFFLINE. Ripristino: `backup:restore-files`.');

        return self::SUCCESS;
    }
}
