<?php

namespace App\Services;

use App\Mail\PatientWelcomeMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PatientRegistrar
{
    /**
     * Trova un paziente esistente (per codice fiscale, poi per email) oppure lo crea
     * con tanto di account portale (ruolo "patient") + email di benvenuto — stessa
     * logica di PatientController@store. Usato dai flussi di iscrizione ai gruppi.
     *
     * @param  array  $data  first_name, last_name, email, phone, codice_fiscale
     * @param  int|null  $createdBy  professionista che "possiede" la scheda
     * @param  array  $professionalIds  professionisti da associare (pivot)
     */
    public function findOrCreate(array $data, ?int $createdBy = null, array $professionalIds = []): Patient
    {
        $cf    = ! empty($data['codice_fiscale']) ? strtoupper(trim($data['codice_fiscale'])) : null;
        $email = ! empty($data['email']) ? trim($data['email']) : null;

        $patient = null;
        if ($cf) {
            $patient = Patient::where('codice_fiscale', $cf)->first();
        }
        if (! $patient && $email) {
            $patient = Patient::where('email', $email)->first();
        }

        // Già in anagrafica: associa eventuali professionisti e ritorna (niente duplicati).
        if ($patient) {
            if ($professionalIds) {
                $patient->professionals()->syncWithoutDetaching($professionalIds);
            }

            return $patient;
        }

        $patient = Patient::create([
            'first_name'     => $data['first_name'] ?? '',
            'last_name'      => $data['last_name'] ?? '',
            'codice_fiscale' => $cf,
            'email'          => $email,
            'phone'          => $data['phone'] ?? null,
            'is_active'      => true,
            'created_by'     => $createdBy,
        ]);

        if ($professionalIds) {
            $patient->professionals()->sync($professionalIds);
        }

        // Account portale + benvenuto (come PatientController@store)
        if ($patient->email && ! User::where('email', $patient->email)->exists()) {
            $tempPassword = Str::random(10);
            $user = User::create([
                'name'              => trim("{$patient->first_name} {$patient->last_name}"),
                'email'             => $patient->email,
                'password'          => Hash::make($tempPassword),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('patient');

            try {
                Mail::to($patient->email)->send(new PatientWelcomeMail($patient, $tempPassword));
            } catch (\Exception $e) {
                Log::error('PatientWelcomeMail (gruppo) failed: '.$e->getMessage());
            }
        }

        return $patient;
    }

    /**
     * Divide un nome completo in [nome, cognome] (prima parola = nome, resto = cognome).
     */
    public static function splitName(string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', $full));
        if ($full === '') {
            return ['', ''];
        }
        $parts = explode(' ', $full);
        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }
}
