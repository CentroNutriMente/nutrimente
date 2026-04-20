<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = [
            'sara@mail.it' => [
                'curriculum' => implode("\n", [
                    'FORMAZIONE',
                    'Laurea Magistrale in Psicologia Cognitiva Applicata — Alma Mater Studiorum, Univ. Bologna (110/110 con lode, 2025)',
                    'Laurea Triennale in Scienze e Tecniche Psicologiche — Alma Mater Studiorum, Univ. Bologna (2023)',
                    'Scuola di Psicoterapia Biosistemica — in formazione dal 2026',
                    'Iscritta Albo A Ordine Psicologi Emilia-Romagna n°12648',
                    '',
                    'ESPERIENZE CLINICHE',
                    'Tirocinio in Psicologia Oncologica — U.O. Psicologia Oncologica, Osp. Sant\'Orsola-Malpighi, Bologna (gen. 2026 – in corso)',
                    'Tirocinio Pratico Valutativo (500h) — AUSL Bologna, Dip. Salute Mentale, Distretto Porto-Saragozza (mar.–set. 2025)',
                    '',
                    'AREE DI INTERVENTO',
                    'Specializzanda in Psicoterapia Biosistemica',
                    'Psicologia oncologica e supporto nei percorsi di malattia',
                    'Valutazione dei Disturbi Emotivi Comuni (DEC)',
                    'Difficoltà emotive e supporto psicologico',
                ]),
            ],
            'samuele.morara@nutrimente.it' => [
                'curriculum' => implode("\n", [
                    'FORMAZIONE',
                    'Laurea Magistrale in Psicologia Cognitiva Applicata — Alma Mater Studiorum, Univ. Bologna (110/110, 2026)',
                    'Laurea Triennale in Scienze e Tecniche Psicologiche — Alma Mater Studiorum, Univ. Bologna (104/110, 2023)',
                    '',
                    'ESPERIENZE CLINICHE',
                    'Assistente Psicologo — AUSL di Bologna (mar.–ott. 2025)',
                    'Colloqui di supporto per pazienti oncologici e familiari',
                    'Valutazioni di idoneità psicologica per pazienti bariatrici',
                    'Somministrazione e scoring test psicodiagnostici',
                    'Gruppi di supporto per pazienti oncologici',
                    'Progetto Pet Therapy durante la somministrazione dei chemioterapici',
                    '',
                    'AREE DI INTERVENTO',
                    'Psicologia oncologica',
                    'Valutazione psicologica e supporto a pazienti bariatrici',
                    'Gruppi di supporto psicologico',
                    'Costruzione di ipotesi psicodiagnostiche',
                ]),
            ],
            'giorgia.clo@nutrimente.it' => [
                'curriculum' => implode("\n", [
                    'FORMAZIONE',
                    'Laurea Magistrale in Psicologia Cognitiva Applicata — Alma Mater Studiorum, Univ. Bologna (110 e lode, 2026)',
                    'Master di I livello in Criminologia e Scienze Strategiche — Università La Sapienza, Roma (110 e lode, 2023)',
                    'Laurea Triennale in Psicologia Sociale e del Lavoro — Università degli Studi di Padova (2021)',
                    '',
                    'ESPERIENZE CLINICHE',
                    'Tirocinio Pratico Valutativo (500h) — Cooperativa Cadiai, CRA San Biagio, Casalecchio di Reno (mar.–ott. 2025)',
                    'Valutazione cognitiva dell\'anziano con compromissione cognitiva/funzionale grave',
                    'Prevenzione e trattamento non farmacologico dei BPSD associati alla demenza',
                    'Stimolazione cognitiva in situazioni di deficit moderato o severo',
                    'Sostegno psicologico ai familiari-caregiver (Caffè Alzheimer)',
                    'Compilazione Piano Assistenziale Individualizzato e partecipazione a équipe multidisciplinari',
                    '',
                    'AREE DI INTERVENTO',
                    'Psicologia dell\'anziano e decadimento cognitivo',
                    'Demenze e disturbi del comportamento (BPSD)',
                    'Stimolazione cognitiva',
                    'Supporto ai caregiver',
                    'Criminologia',
                ]),
            ],
        ];

        foreach ($data as $email => $fields) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                DB::table('professional_profiles')
                    ->where('user_id', $user->id)
                    ->update(['curriculum' => $fields['curriculum'], 'updated_at' => now()]);
            }
        }
    }

    public function down(): void {}
};
