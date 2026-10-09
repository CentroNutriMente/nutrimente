<?php

namespace App\Console\Commands;

use App\Support\BlindIndex;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Migrazione one-shot: cifra le righe esistenti (in chiaro) delle tabelle in ambito
 * e popola i blind index su patients. Idempotente: salta i valori già cifrati.
 *
 * Legge/scrive via query builder (DB::table) per NON passare dai cast del modello
 * (che altrimenti tenterebbero di decifrare testo in chiaro).
 */
class EncryptExistingData extends Command
{
    protected $signature = 'encrypt:existing {--dry-run : Mostra solo cosa verrebbe cifrato}';

    protected $description = 'Cifra a riposo i dati sensibili esistenti (GDPR) e popola i blind index';

    /** Tabella => colonne da cifrare (stringa o JSON: identico, si cifra il raw). */
    private array $map = [
        'patients'                  => ['first_name', 'last_name', 'codice_fiscale', 'date_of_birth', 'gender', 'email', 'phone', 'address', 'city', 'cap', 'emergency_contact_name', 'emergency_contact_phone', 'medico_base', 'notes', 'diagnosis'],
        'clinical_notes'            => ['body'],
        'reports'                   => ['title', 'next_appointment', 'notes', 'sections_data'],
        'questionnaires'            => ['answers', 'notes'],
        'patient_records'           => ['data', 'title', 'notes', 'treatment_plan'],
        'documents'                 => ['title', 'description', 'file_name'],
        'appointments'              => ['title', 'description', 'cancellation_reason'],
        'intervisioni'              => ['title', 'description', 'discussion_notes', 'conclusions'],
        'contact_requests'          => ['how_found', 'contact_method', 'availability', 'name', 'surname', 'phone', 'email', 'notes'],
        'booking_requests'          => ['patient_name', 'patient_surname', 'patient_email', 'patient_phone', 'notes'],
        'group_participants'        => ['name', 'email', 'phone'],
        'group_enrollment_requests' => ['name', 'email', 'phone', 'codice_fiscale'],
        'invoices'                  => ['client_name', 'client_codice_fiscale', 'client_address', 'client_phone', 'client_email'],
        'messages'                  => ['body', 'attachment_name'],
    ];

    /** Tabella => [attributo in chiaro => colonna hash]. */
    private array $blindIndexes = [
        'patients' => ['email' => 'email_hash', 'codice_fiscale' => 'codice_fiscale_hash'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $totalRows = 0;

        foreach ($this->map as $table => $columns) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $touched = 0;

            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns, $dry, &$touched) {
                foreach ($rows as $row) {
                    $update = [];

                    foreach ($columns as $col) {
                        $raw = $row->{$col} ?? null;
                        if ($raw === null || $raw === '' || $this->isEncrypted($raw)) {
                            continue; // null/vuoto o già cifrato
                        }
                        $update[$col] = Crypt::encryptString((string) $raw);
                    }

                    foreach ($this->blindIndexes[$table] ?? [] as $attr => $hashCol) {
                        $plain = $this->plaintext($row->{$attr} ?? null);
                        $update[$hashCol] = BlindIndex::hash($plain);
                    }

                    if ($update) {
                        $touched++;
                        if (! $dry) {
                            DB::table($table)->where('id', $row->id)->update($update);
                        }
                    }
                }
            });

            $this->line(sprintf('%-28s %d righe %s', $table, $touched, $dry ? '(da cifrare)' : 'cifrate'));
            $totalRows += $touched;
        }

        $this->info(($dry ? '[dry-run] ' : '').'Completato. Righe interessate: '.$totalRows);

        return self::SUCCESS;
    }

    private function isEncrypted(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    /** Ritorna il valore in chiaro, decifrando se già cifrato. */
    private function plaintext(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return (string) $value;
        }
    }
}
