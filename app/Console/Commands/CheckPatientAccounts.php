<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Diagnostica lo scostamento fra "Gestione utenti" (tabella users) e "Pazienti"
 * (tabella patients): le due sono collegate solo dalla corrispondenza dell'email,
 * quindi possono divergere. Non modifica nulla.
 *
 *   php artisan patients:check-accounts
 *   php artisan patients:check-accounts --emails   # stampa anche gli elenchi
 */
class CheckPatientAccounts extends Command
{
    protected $signature = 'patients:check-accounts {--emails : Elenca nel dettaglio le righe non appaiate}';

    protected $description = 'Confronta account utente e cartelle pazienti, elencando le corrispondenze mancanti.';

    public function handle(): int
    {
        $norm = fn (?string $e) => $e ? mb_strtolower(trim($e)) : null;

        $users = User::with('roles:id,name')->get();
        $userEmails = $users->map(fn (User $u) => $norm($u->email))->filter()->flip();

        $patients = Patient::withTrashed()->get();

        $patientsNoEmail        = $patients->filter(fn ($p) => ! $norm($p->email));
        $patientsNoAccount      = $patients->filter(fn ($p) => $norm($p->email) && ! $userEmails->has($norm($p->email)));
        $patientEmails          = $patients->map(fn ($p) => $norm($p->email))->filter()->flip();
        $accountsNoPatient      = $users->filter(fn (User $u) => ! $patientEmails->has($norm($u->email)));
        $erased                 = $users->filter(fn (User $u) => str_ends_with((string) $u->email, '@invalid.local'));
        $archived               = $patients->filter(fn ($p) => $p->trashed());

        $this->newLine();
        $this->line('<options=bold>Riepilogo</>');
        $this->table(['Voce', 'N.'], [
            ['Account utente totali', $users->count()],
            ['  di cui anonimizzati (eliminati)', $erased->count()],
            ['Cartelle paziente totali', $patients->count()],
            ['  di cui archiviate', $archived->count()],
            ['Cartelle senza email (mai avranno un account)', $patientsNoEmail->count()],
            ['Cartelle con email ma senza account', $patientsNoAccount->count()],
            ['Account senza cartella (professionisti, staff, email diverse)', $accountsNoPatient->count()],
        ]);

        if ($this->option('emails')) {
            if ($patientsNoAccount->isNotEmpty()) {
                $this->newLine();
                $this->line('<options=bold>Cartelle con email ma senza account portale</>');
                $this->table(['ID', 'Paziente', 'Email', 'Stato'], $patientsNoAccount
                    ->map(fn ($p) => [
                        $p->id,
                        trim("{$p->last_name} {$p->first_name}"),
                        $p->email,
                        $p->trashed() ? 'archiviata' : 'attiva',
                    ])->all());
            }

            if ($accountsNoPatient->isNotEmpty()) {
                $this->newLine();
                $this->line('<options=bold>Account senza cartella clinica</>');
                $this->table(['ID', 'Nome', 'Email', 'Ruoli'], $accountsNoPatient
                    ->map(fn (User $u) => [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->roles->pluck('name')->implode(', ') ?: '—',
                    ])->all());
            }
        } else {
            $this->line('Usa <options=bold>--emails</> per vedere le righe non appaiate.');
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
