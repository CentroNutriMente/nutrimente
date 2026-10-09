<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Gestione utenti riservata all'amministratore di sistema (ruolo system_admin):
 * creazione account ed "eliminazione" nel rispetto del diritto all'oblio (art. 17
 * GDPR) tramite anonimizzazione. Nessun accesso ai dati clinici.
 */
class UserManagementController extends Controller
{
    use PasswordValidationRules;

    /** Ruoli assegnabili dalla gestione utenti. */
    private const ASSIGNABLE_ROLES = [
        'psicologo', 'nutrizionista', 'osteopata', 'collaboratore', 'system_admin', 'admin',
    ];

    private function ensureSystemAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('system_admin'), 403, 'Area riservata all\'amministratore di sistema.');
    }

    public function index(): Response
    {
        $this->ensureSystemAdmin();

        // La corrispondenza account ↔ cartella clinica passa solo dall'email
        // (blind index). È un dato clinico, quindi la mostriamo al solo admin:
        // il system_admin "puro" resta senza accesso ai dati dei pazienti.
        $isAdmin = auth()->user()->hasRole('admin');

        $users = User::with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($isAdmin) {
                $row = [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'roles'      => $u->roles->pluck('name'),
                    'is_erased'  => str_ends_with((string) $u->email, '@invalid.local'),
                    'created_at' => $u->created_at?->format('d/m/Y'),
                ];

                if ($isAdmin) {
                    $patient = $u->email
                        ? \App\Models\Patient::withTrashed()->whereBlind('email', $u->email)->first()
                        : null;

                    $row['patient'] = $patient ? [
                        'id'       => $patient->id,
                        'name'     => trim("{$patient->last_name} {$patient->first_name}"),
                        'archived' => $patient->trashed(),
                    ] : null;
                }

                return $row;
            });

        $patientsWithoutAccount = $isAdmin ? $this->patientsWithoutAccount() : null;

        return Inertia::render('Users/Index', [
            'users'                  => $users,
            'roles'                  => self::ASSIGNABLE_ROLES,
            'showPatientLink'        => $isAdmin,
            'patientsWithoutAccount' => $patientsWithoutAccount,
        ]);
    }

    /** Cartelle attive con email ma senza account portale: l'altra metà dello scostamento. */
    private function patientsWithoutAccount(): array
    {
        $emails = User::pluck('email')
            ->filter()
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->flip();

        return \App\Models\Patient::whereNotNull('email')->get()
            ->filter(fn ($p) => $p->email && ! $emails->has(mb_strtolower(trim((string) $p->email))))
            ->sortBy(fn ($p) => mb_strtolower((string) $p->last_name))
            ->values()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => trim("{$p->last_name} {$p->first_name}"),
                'email' => $p->email,
            ])
            ->all();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSystemAdmin();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'role'     => ['required', 'string', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']));

        // Profilo professionista + slug per i ruoli clinici (necessario per booking/agenda).
        if (in_array($validated['role'], ['psicologo', 'nutrizionista', 'osteopata', 'collaboratore'], true)) {
            $base = Str::slug($user->name) ?: 'utente';
            $slug = $base; $i = 2;
            while (\App\Models\ProfessionalProfile::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }
            $user->professionalProfile()->create(['slug' => $slug]);
        }

        AuditLog::record('created', $user);

        return back()->with('success', 'Utente creato.');
    }

    /**
     * Diritto all'oblio (art. 17): anonimizza l'account e revoca l'accesso,
     * preservando l'integrità dei record collegati (soggetti a retention).
     */
    public function erase(User $user): RedirectResponse
    {
        $this->ensureSystemAdmin();

        abort_if($user->id === auth()->id(), 422, 'Non puoi eliminare il tuo stesso account.');

        // Protezione: non anonimizzare l'unico founder.
        if ($user->professionalProfile?->is_founder
            && \App\Models\ProfessionalProfile::where('is_founder', true)->count() <= 1) {
            abort(422, 'Impossibile eliminare l\'unico account fondatore.');
        }

        AuditLog::record('deleted', $user);

        // La cartella clinica è collegata all'account solo dall'email: va archiviata
        // *prima* di offuscarla, altrimenti resterebbe nell'elenco generale dei
        // pazienti. Resta consultabile in "Pazienti → Archivio" (obbligo di
        // conservazione), fuori dall'elenco corrente.
        if ($user->email) {
            foreach (\App\Models\Patient::whereBlind('email', $user->email)->get() as $patient) {
                AuditLog::record('deleted', $patient, null, ['note' => 'archiviato con l\'account utente']);
                $patient->delete();
            }
        }

        $user->syncRoles([]);
        $user->forceFill([
            'name'              => 'Utente eliminato #'.$user->id,
            'email'             => 'deleted+'.$user->id.'@invalid.local',
            'password'          => Hash::make(Str::random(40)),
            'remember_token'    => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        // Rimuove il profilo professionista e le disponibilità: senza questo l'utente
        // anonimizzato continuerebbe a comparire in tutti gli elenchi professionisti
        // (filtrati per whereHas('professionalProfile')) e come slot prenotabili.
        $user->professionalProfile()->delete();
        $user->availabilitySlots()->delete();

        // Invalida eventuali sessioni residue dell'utente.
        \DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('success', 'Account anonimizzato (diritto all\'oblio).');
    }
}
