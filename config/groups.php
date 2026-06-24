<?php

// Categorie dei "Gruppi di aiuto e sostegno" — sorgente unica per UI e validazione.
// Le chiavi (key) sono salvate in DB; label/colore/descrizione guidano il frontend.

return [

    'categories' => [
        'ansia' => [
            'label'       => 'Gestione Ansia',
            'tone'        => 'sage',      // mappa ai colori Tailwind (sage|lavender|peach)
            'description' => 'Strumenti pratici per riconoscere e gestire ansia e stress.',
        ],
        'caregiver' => [
            'label'       => 'Supporto Caregiver',
            'tone'        => 'lavender',
            'description' => 'Spazio di condivisione e supporto per chi si prende cura di altri.',
        ],
        'alimentazione' => [
            'label'       => 'Alimentazione Consapevole',
            'tone'        => 'peach',
            'description' => 'Percorso di gruppo per sviluppare un rapporto sereno con il cibo.',
        ],
    ],

    // Valori ammessi per i campi a vocabolario chiuso.
    'cadences'   => ['settimanale', 'quindicinale', 'mensile'],
    'modalities' => ['presenza', 'online'],
    'statuses'   => ['attivo', 'in_partenza', 'concluso'],

    'participant_statuses' => ['confermata', 'in_attesa', 'pagato'],

    'enrollment_statuses'  => ['da_contattare', 'contattato', 'in_attesa_conferma', 'confermata', 'rifiutata'],

];
