<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function professional(): User
    {
        Role::findOrCreate('psicologo', 'web');
        Role::findOrCreate('system_admin', 'web');
        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('psicologo');

        return $user;
    }

    private function admin(): User
    {
        $user = $this->professional();
        $user->assignRole('admin');

        return $user;
    }

    public function test_il_professionista_vede_solo_le_cartelle_non_archiviate(): void
    {
        $pro = $this->professional();

        $attivo = Patient::create(['first_name' => 'Anna', 'last_name' => 'Rossi', 'created_by' => $pro->id]);
        $archiviato = Patient::create(['first_name' => 'Bruno', 'last_name' => 'Bianchi', 'created_by' => $pro->id]);
        $archiviato->delete();

        $this->actingAs($pro)->get(route('patients.index'))
            ->assertInertia(fn ($page) => $page->where('patients.total', 1)->where('patients.data.0.id', $attivo->id));

        // Niente archivio e nessun accesso diretto alla cartella archiviata.
        $this->actingAs($pro)->get(route('patients.archived'))->assertForbidden();
        $this->actingAs($pro)->get(route('patients.show', $archiviato->id))->assertNotFound();
        $this->actingAs($pro)->post(route('patients.restore', $archiviato->id))->assertForbidden();
        $this->assertNotNull($archiviato->fresh()->deleted_at);
    }

    public function test_le_cartelle_archiviate_escono_dall_elenco_ma_restano_consultabili(): void
    {
        $user = $this->admin();

        $attivo = Patient::create(['first_name' => 'Anna', 'last_name' => 'Rossi', 'created_by' => $user->id]);
        $archiviato = Patient::create(['first_name' => 'Bruno', 'last_name' => 'Bianchi', 'created_by' => $user->id]);
        $archiviato->delete();

        // Elenco generale: solo il paziente attivo.
        $this->actingAs($user)->get(route('patients.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Patients/Index')
                ->where('patients.data.0.id', $attivo->id)
                ->where('patients.total', 1));

        // Archivio: solo quello eliminato.
        $this->actingAs($user)->get(route('patients.archived'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Patients/Archived')
                ->has('patients', 1)
                ->where('patients.0.id', $archiviato->id));

        // La scheda resta consultabile e si dichiara archiviata.
        $this->actingAs($user)->get(route('patients.show', $archiviato->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Patients/Show')->where('archived', true));

        // Ripristino: torna nell'elenco generale.
        $this->actingAs($user)->post(route('patients.restore', $archiviato->id))->assertRedirect();
        $this->assertNull($archiviato->fresh()->deleted_at);
    }

    public function test_gestione_utenti_mostra_la_cartella_collegata_e_i_pazienti_senza_account(): void
    {
        $admin = $this->admin();
        $admin->assignRole('system_admin');

        $account = User::factory()->create(['email' => 'collegato@example.test']);
        $collegato = Patient::create(['first_name' => 'Dora', 'last_name' => 'Neri', 'email' => 'collegato@example.test', 'created_by' => $admin->id]);
        $senzaAccount = Patient::create(['first_name' => 'Elio', 'last_name' => 'Gialli', 'email' => 'orfano@example.test', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('utenti.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('showPatientLink', true)
                ->has('patientsWithoutAccount', 1)
                ->where('patientsWithoutAccount.0.id', $senzaAccount->id)
                ->where('users', fn ($users) => collect($users)
                    ->firstWhere('id', $account->id)['patient']['id'] === $collegato->id));
    }

    public function test_eliminare_un_account_archivia_la_cartella_collegata(): void
    {
        $admin = $this->professional();
        $admin->assignRole('system_admin');

        $account = User::factory()->create(['email' => 'paziente@example.test']);
        $patient = Patient::create([
            'first_name' => 'Carla',
            'last_name'  => 'Verdi',
            'email'      => 'paziente@example.test',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->delete(route('utenti.erase', $account->id));

        $this->assertNotNull($patient->fresh()->deleted_at, 'La cartella deve finire in archivio.');
        $this->assertDatabaseHas('patients', ['id' => $patient->id]); // i dati restano
    }
}
