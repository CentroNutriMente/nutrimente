<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara lo schema per la cifratura a riposo (GDPR):
 * - allarga a TEXT le colonne che ospiteranno blob cifrati (varchar e json);
 * - aggiunge i blind index su patients (email_hash, codice_fiscale_hash) spostando
 *   lì il vincolo di unicità del codice fiscale.
 *
 * NB: i dati esistenti restano in chiaro finché non viene eseguita la migrazione
 * one-shot (comando encrypt:existing). Il cast tollerante li gestisce nel frattempo.
 */
return new class extends Migration
{
    /** Colonne varchar/date → text. */
    private array $toText = [
        'patients'                  => ['first_name', 'last_name', 'codice_fiscale', 'gender', 'email', 'phone', 'city', 'cap', 'emergency_contact_name', 'emergency_contact_phone', 'medico_base', 'diagnosis', 'date_of_birth'],
        'reports'                   => ['title', 'next_appointment'],
        'patient_records'           => ['title'],
        'documents'                 => ['title', 'file_name'],
        'appointments'              => ['title'],
        'intervisioni'              => ['title'],
        'contact_requests'          => ['name', 'surname', 'phone', 'email'],
        'booking_requests'          => ['patient_name', 'patient_surname', 'patient_email', 'patient_phone'],
        'group_participants'        => ['name', 'email', 'phone'],
        'group_enrollment_requests' => ['name', 'email', 'phone', 'codice_fiscale'],
        'invoices'                  => ['client_name', 'client_codice_fiscale', 'client_address', 'client_phone', 'client_email'],
        'messages'                  => ['attachment_name'],
    ];

    /** Colonne json → text (il blob cifrato non è JSON valido). */
    private array $jsonToText = [
        'reports'          => ['sections_data'],
        'questionnaires'   => ['answers'],
        'patient_records'  => ['data'],
        'contact_requests' => ['how_found', 'contact_method', 'availability'],
    ];

    public function up(): void
    {
        // Il CF è unique: rimuovere l'indice prima di cambiare tipo.
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique('patients_codice_fiscale_unique');
        });

        foreach ($this->toText as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $col) {
                    $t->text($col)->nullable()->change();
                }
            });
        }

        foreach ($this->jsonToText as $table => $columns) {
            foreach ($columns as $col) {
                $this->jsonColumnToText($table, $col);
            }
        }

        // VARCHAR(64): l'hash HMAC-SHA256 è esadecimale a lunghezza fissa. Necessario
        // su MySQL (Aruba) perché TEXT non è indicizzabile UNIQUE senza prefisso.
        Schema::table('patients', function (Blueprint $table) {
            $table->string('email_hash', 64)->nullable()->after('email');
            $table->string('codice_fiscale_hash', 64)->nullable()->after('codice_fiscale');
            $table->index('email_hash');
            $table->unique('codice_fiscale_hash');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['codice_fiscale_hash']);
            $table->dropIndex(['email_hash']);
            $table->dropColumn(['email_hash', 'codice_fiscale_hash']);
            $table->unique('codice_fiscale');
        });
        // I cambi di tipo a TEXT non vengono revertiti: i dati potrebbero essere
        // cifrati e non rientrerebbero nei tipi originali.
    }

    /** Cambio di tipo json → text portabile fra Postgres e MySQL. */
    private function jsonColumnToText(string $table, string $column): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE text USING {$column}::text");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TEXT NULL");
        } else { // sqlite e altri: il tipo è dinamico, sufficiente per i test
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->text($column)->nullable()->change();
            });
        }
    }
};
