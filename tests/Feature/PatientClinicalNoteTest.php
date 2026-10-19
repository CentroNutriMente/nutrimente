<?php

namespace Tests\Feature;

use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientClinicalNoteTest extends TestCase
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

    public function test_le_note_cliniche_sono_un_unico_testo_modificabile(): void
    {
        $pro = $this->professional();
        $patient = Patient::create(['first_name' => 'Anna', 'last_name' => 'Rossi', 'created_by' => $pro->id]);

        $this->actingAs($pro)->put(route('patients.notes.update', $patient->id), ['body' => 'Prima stesura'])->assertRedirect();
        $this->actingAs($pro)->put(route('patients.notes.update', $patient->id), ['body' => 'Testo aggiornato'])->assertRedirect();

        $this->assertSame(1, ClinicalNote::where('patient_id', $patient->id)->count());
        $this->assertSame('Testo aggiornato', ClinicalNote::where('patient_id', $patient->id)->first()->body);

        // Campo svuotato: la nota va nel cestino.
        $this->actingAs($pro)->put(route('patients.notes.update', $patient->id), ['body' => ''])->assertRedirect();
        $this->assertSame(0, ClinicalNote::where('patient_id', $patient->id)->count());
    }

    public function test_le_note_del_vecchio_elenco_confluiscono_in_una_sola(): void
    {
        $pro = $this->professional();
        $altro = $this->professional();
        $patient = Patient::create(['first_name' => 'Anna', 'last_name' => 'Rossi', 'created_by' => $pro->id]);

        $patient->clinicalNotes()->create(['user_id' => $pro->id, 'body' => 'Uno', 'is_private' => false]);
        $patient->clinicalNotes()->create(['user_id' => $pro->id, 'body' => 'Due', 'is_private' => true]);
        $altrui = $patient->clinicalNotes()->create(['user_id' => $altro->id, 'body' => 'Riservata altrui', 'is_private' => true]);

        $this->actingAs($pro)->put(route('patients.notes.update', $patient->id), ['body' => "Uno\n\nDue"])->assertRedirect();

        $rimaste = ClinicalNote::where('patient_id', $patient->id)->get();
        $this->assertCount(2, $rimaste);
        $this->assertTrue($rimaste->contains('id', $altrui->id));
        $unita = $rimaste->firstWhere('user_id', $pro->id);
        $this->assertSame("Uno\n\nDue", $unita->body);
        $this->assertTrue($unita->is_private);
    }
}
