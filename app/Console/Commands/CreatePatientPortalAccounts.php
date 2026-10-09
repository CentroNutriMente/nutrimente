<?php

namespace App\Console\Commands;

use App\Mail\PatientWelcomeMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Crea in blocco gli account del portale per i pazienti che hanno un'email
 * ma non hanno ancora un account (creati/importati prima dell'auto-creazione,
 * oppure con email aggiunta in modifica). Password temporanea casuale.
 *
 *   php artisan patients:create-accounts            # crea e invia la mail di benvenuto
 *   php artisan patients:create-accounts --no-mail  # crea senza inviare email
 *   php artisan patients:create-accounts --dry-run  # simula, non scrive nulla
 */
class CreatePatientPortalAccounts extends Command
{
    protected $signature = 'patients:create-accounts {--no-mail : Non inviare la mail di benvenuto} {--dry-run : Simula senza creare né inviare}';

    protected $description = 'Crea gli account portale per i pazienti con email ma senza account (password temporanea).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sendMail = ! $this->option('no-mail');

        $created = 0;
        $skipped = 0;
        $rows = [];

        // Solo i pazienti con email valorizzata (chi non ce l'ha ha NULL in colonna).
        Patient::whereNotNull('email')->chunkById(100, function ($patients) use (&$created, &$skipped, &$rows, $dryRun, $sendMail) {
            foreach ($patients as $patient) {
                $email = trim((string) $patient->email);

                if ($email === '' || User::where('email', $email)->exists()) {
                    $skipped++;
                    continue;
                }

                $tempPassword = Str::random(10);

                if (! $dryRun) {
                    $user = User::create([
                        'name'              => "{$patient->first_name} {$patient->last_name}",
                        'email'             => $email,
                        'password'          => Hash::make($tempPassword),
                        'email_verified_at' => now(),
                    ]);
                    $user->assignRole('patient');
                }

                $mailStatus = '—';
                if (! $dryRun && $sendMail) {
                    try {
                        Mail::to($email)->send(new PatientWelcomeMail($patient, $tempPassword));
                        $mailStatus = 'inviata';
                    } catch (\Exception $e) {
                        \Log::error('PatientWelcomeMail (bulk) failed: ' . $e->getMessage());
                        $mailStatus = 'ERRORE';
                    }
                }

                $created++;
                $rows[] = [
                    $patient->id,
                    "{$patient->last_name} {$patient->first_name}",
                    $email,
                    $tempPassword,
                    $sendMail ? $mailStatus : 'non inviata',
                ];
            }
        });

        if ($rows) {
            $this->table(['ID', 'Paziente', 'Email', 'Password temp.', 'Mail'], $rows);
        }

        $verb = $dryRun ? 'Da creare' : 'Creati';
        $this->info("{$verb}: {$created} account · Saltati (già esistenti o senza email): {$skipped}");

        if ($dryRun) {
            $this->warn('Modalità --dry-run: nessuna modifica salvata.');
        } else {
            $this->warn('Annota le password temporanee: NON sono recuperabili in seguito.');
        }

        return self::SUCCESS;
    }
}
