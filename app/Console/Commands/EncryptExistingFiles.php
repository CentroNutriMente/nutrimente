<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\GroupMaterial;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Cifra a riposo i file già caricati (documenti paziente, materiali gruppi).
 * Idempotente: salta i file già cifrati.
 */
class EncryptExistingFiles extends Command
{
    protected $signature = 'encrypt:files {--dry-run}';

    protected $description = 'Cifra i file allegati esistenti su disco (GDPR)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $total = 0;

        $targets = [
            ['model' => Document::class,      'disk' => config('filesystems.default'), 'col' => 'file_path'],
            ['model' => GroupMaterial::class, 'disk' => 'local',                        'col' => 'path'],
        ];

        foreach ($targets as $t) {
            $done = 0;
            foreach ($t['model']::query()->cursor() as $row) {
                $path = $row->{$t['col']};
                $disk = Storage::disk($t['disk']);

                if (! $path || ! $disk->exists($path)) {
                    continue;
                }

                $raw = $disk->get($path);
                if ($this->isEncrypted($raw)) {
                    continue; // già cifrato
                }

                if (! $dry) {
                    $disk->put($path, Crypt::encryptString($raw));
                }
                $done++;
            }
            $this->line(sprintf('%-16s %d file %s', class_basename($t['model']), $done, $dry ? '(da cifrare)' : 'cifrati'));
            $total += $done;
        }

        $this->info(($dry ? '[dry-run] ' : '').'Completato. File interessati: '.$total);

        return self::SUCCESS;
    }

    private function isEncrypted(string $raw): bool
    {
        try {
            Crypt::decryptString($raw);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
