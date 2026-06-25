<?php

// Configurazione "Gruppi di aiuto e sostegno".
// Le categorie fisse sono state rimosse: il titolo (ed eventuale edizione) li
// assegna la professionista in fase di creazione del gruppo.

return [

    // Valori ammessi per i campi a vocabolario chiuso.
    'cadences'   => ['settimanale', 'quindicinale', 'mensile'],
    'modalities' => ['presenza', 'online'],
    'statuses'   => ['attivo', 'in_partenza', 'concluso'],

    'participant_statuses' => ['confermata', 'in_attesa', 'pagato'],

    'enrollment_statuses'  => ['da_contattare', 'contattato', 'in_attesa_conferma', 'confermata', 'rifiutata'],

];
