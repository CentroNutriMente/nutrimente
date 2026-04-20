<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = [
            'sara@mail.it' => json_encode([
                'formazione' => [
                    ['titolo' => 'Laurea Magistrale in Psicologia Cognitiva Applicata', 'ente' => 'Alma Mater Studiorum – Università di Bologna', 'voto' => '110/110 con lode', 'anno' => '2025'],
                    ['titolo' => 'Laurea Triennale in Scienze e Tecniche Psicologiche', 'ente' => 'Alma Mater Studiorum – Università di Bologna', 'anno' => '2023'],
                    ['titolo' => 'Scuola di Psicoterapia Biosistemica', 'nota' => 'In formazione dal 2026'],
                    ['titolo' => 'Albo A – Ordine Psicologi Emilia-Romagna', 'nota' => 'n°12648, iscritta dal nov. 2025'],
                ],
                'esperienze' => [
                    [
                        'ruolo'    => 'Tirocinio in Psicologia Oncologica',
                        'ente'     => 'U.O. Psicologia Oncologica, Osp. Sant\'Orsola-Malpighi – Bologna',
                        'periodo'  => 'Gen. 2026 – in corso',
                        'attivita' => [
                            'Osservazione clinica dei colloqui di supporto a pazienti oncologici e caregiver',
                            'Apprendimento delle procedure di refertazione clinica',
                            'Partecipazione al progetto Hospice per pazienti in degenza',
                        ],
                    ],
                    [
                        'ruolo'    => 'Tirocinio Pratico Valutativo – 500 ore',
                        'ente'     => 'AUSL Bologna, Dip. Salute Mentale – Distretto Porto-Saragozza',
                        'periodo'  => 'Mar. – Set. 2025',
                        'attivita' => [
                            'Colloqui clinici e anamnestici per l\'inquadramento diagnostico dei pazienti',
                            'Valutazione dei Disturbi Emotivi Comuni (DEC) tramite test standardizzati',
                            'Partecipazione a riunioni d\'équipe multidisciplinari e discussione di casi clinici',
                        ],
                    ],
                ],
                'aree' => [
                    'Specializzanda in Psicoterapia Biosistemica',
                    'Psicologia oncologica',
                    'Supporto nei percorsi di malattia',
                    'Valutazione dei Disturbi Emotivi Comuni (DEC)',
                    'Difficoltà emotive e supporto psicologico',
                ],
            ], JSON_UNESCAPED_UNICODE),

            'samuele.morara@nutrimente.it' => json_encode([
                'formazione' => [
                    ['titolo' => 'Laurea Magistrale in Psicologia Cognitiva Applicata', 'ente' => 'Alma Mater Studiorum – Università di Bologna', 'voto' => '110/110', 'anno' => '2026'],
                    ['titolo' => 'Laurea Triennale in Scienze e Tecniche Psicologiche', 'ente' => 'Alma Mater Studiorum – Università di Bologna', 'voto' => '104/110', 'anno' => '2023'],
                ],
                'esperienze' => [
                    [
                        'ruolo'    => 'Assistente Psicologo',
                        'ente'     => 'AUSL di Bologna',
                        'periodo'  => 'Mar. – Ott. 2025',
                        'attivita' => [
                            'Assistenza nei colloqui di supporto per pazienti oncologici e familiari',
                            'Valutazioni di idoneità psicologica per pazienti bariatrici',
                            'Somministrazione e scoring di test psicodiagnostici',
                            'Assistenza nei gruppi di supporto per pazienti oncologici',
                            'Gestione del progetto Pet Therapy durante la somministrazione dei chemioterapici',
                        ],
                    ],
                ],
                'aree' => [
                    'Psicologia oncologica',
                    'Valutazione psicologica',
                    'Supporto a pazienti bariatrici',
                    'Gruppi di supporto',
                    'Costruzione ipotesi psicodiagnostiche',
                ],
            ], JSON_UNESCAPED_UNICODE),

            'giorgia.clo@nutrimente.it' => json_encode([
                'formazione' => [
                    ['titolo' => 'Laurea Magistrale in Psicologia Cognitiva Applicata', 'ente' => 'Alma Mater Studiorum – Università di Bologna', 'voto' => '110 e lode', 'anno' => '2026'],
                    ['titolo' => 'Master I livello in Criminologia e Scienze Strategiche', 'ente' => 'Università La Sapienza – Roma', 'voto' => '110 e lode', 'anno' => '2023'],
                    ['titolo' => 'Laurea Triennale in Psicologia Sociale e del Lavoro', 'ente' => 'Università degli Studi di Padova', 'anno' => '2021'],
                ],
                'esperienze' => [
                    [
                        'ruolo'    => 'Tirocinio Pratico Valutativo – 500 ore',
                        'ente'     => 'Cooperativa Cadiai – CRA San Biagio, Casalecchio di Reno',
                        'periodo'  => 'Mar. – Ott. 2025',
                        'attivita' => [
                            'Valutazione cognitiva dell\'anziano con grave compromissione cognitiva e/o funzionale',
                            'Prevenzione e trattamento non farmacologico dei BPSD associati alla demenza',
                            'Stimolazione cognitiva in situazioni di deficit moderato o severo',
                            'Sostegno psicologico ai familiari-caregiver e interventi Caffè Alzheimer',
                            'Compilazione del Piano Assistenziale Individualizzato e partecipazione a équipe multidisciplinari',
                        ],
                    ],
                ],
                'aree' => [
                    'Psicologia dell\'anziano',
                    'Decadimento cognitivo e demenze',
                    'Disturbi del comportamento (BPSD)',
                    'Stimolazione cognitiva',
                    'Supporto ai caregiver',
                    'Criminologia',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];

        foreach ($data as $email => $json) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                DB::table('professional_profiles')
                    ->where('user_id', $user->id)
                    ->update(['curriculum' => $json, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void {}
};
