<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $professionals = [
            [
                'name'     => 'Sara Alessandri',
                'email'    => 'sara@mail.it', // existing user – match by email
                'role'     => 'psicologo',
                'title'    => 'Dott.ssa',
                'category' => 'Psicologa',
                'bio'      => 'La Dott.ssa Alessandri lavora accanto a persone che stanno attraversando momenti di fragilità emotiva o legati alla malattia, offrendo uno spazio accogliente e protetto in cui sentirsi compresi e sostenuti. Il suo intervento si distingue per la capacità di integrare ascolto autentico e competenze cliniche, accompagnando la persona in un percorso concreto di maggiore consapevolezza e benessere. È una scelta indicata per chi desidera un supporto attento, rispettoso e profondamente umano.',
                'curriculum' => "Aree di intervento:\n• Specializzanda in Psicoterapia Biosistemica\n• Tirocinio di specializzazione in Psicologia oncologica\n• Difficoltà emotive comuni\n• Supporto nei percorsi di malattia",
            ],
            [
                'name'     => 'Samuele Morara',
                'email'    => 'samuele.morara@nutrimente.it',
                'role'     => 'psicologo',
                'title'    => 'Dott.',
                'category' => 'Psicologo',
                'bio'      => 'Il Dott. Morara lavora con pazienti e famiglie offrendo un supporto psicologico concreto, costruito sull\'ascolto e orientato al cambiamento. Grazie all\'esperienza maturata anche in ambito ospedaliero, accompagna le persone con uno stile diretto ma empatico, aiutandole a sviluppare strumenti utili per affrontare le difficoltà quotidiane. È particolarmente indicato per chi cerca un professionista affidabile, capace di lavorare sia a livello individuale sia di gruppo, con un approccio pratico e collaborativo.',
                'curriculum' => "Aree di intervento:\n• Psicologia oncologica\n• Valutazione psicologica\n• Supporto a pazienti bariatrici\n• Gruppi di supporto",
            ],
            [
                'name'     => 'Giorgia Clò',
                'email'    => 'giorgia.clo@nutrimente.it',
                'role'     => 'psicologo',
                'title'    => 'Dott.ssa',
                'category' => 'Psicologa',
                'bio'      => 'La Dott.ssa Clò lavora con anziani e famiglie che si confrontano con il decadimento cognitivo, offrendo un sostegno competente e continuativo nei momenti più delicati. Il suo approccio è orientato a fornire strumenti concreti per la gestione quotidiana, affiancando caregiver e familiari con sensibilità e chiarezza. È una scelta indicata per chi cerca una figura professionale solida, capace di coniugare attenzione alla persona e interventi mirati in un contesto complesso.',
                'curriculum' => "Aree di intervento:\n• Psicologia dell'anziano\n• Demenze e disturbi del comportamento (BPSD)\n• Supporto ai caregiver\n• Stimolazione cognitiva\n• Criminologia",
            ],
        ];

        $role = Role::firstOrCreate(['name' => 'psicologo', 'guard_name' => 'web']);

        foreach ($professionals as $data) {
            // Find or create user
            $user = \App\Models\User::where('email', $data['email'])->first();

            if (! $user) {
                $user = \App\Models\User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make(Str::random(24)),
                ]);
                $user->assignRole($role);
            }

            // Generate slug if missing
            $existing = \App\Models\ProfessionalProfile::where('user_id', $user->id)->first();
            $slug = $existing?->slug;
            if (! $slug) {
                $base = Str::slug($user->name);
                $slug = $base;
                $i = 2;
                while (\App\Models\ProfessionalProfile::where('slug', $slug)->where('user_id', '!=', $user->id)->exists()) {
                    $slug = $base . '-' . $i++;
                }
            }

            DB::table('professional_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'title'       => $data['title'],
                    'category'    => $data['category'],
                    'bio'         => $data['bio'],
                    'curriculum'  => $data['curriculum'],
                    'slug'        => $slug,
                    'is_bookable' => true,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Non-destructive – leave users and profiles in place
    }
};
