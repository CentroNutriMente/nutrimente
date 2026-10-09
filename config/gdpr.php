<?php

// Politica di conservazione dei dati (GDPR art. 5.1.e — limitazione della conservazione).
//
// Tempi definiti dalla titolare del trattamento (psicologa):
//   - Dati anagrafici / contatto / pagamento  → 10 anni (obblighi fiscali/amministrativi)
//   - Dati sanitari (stato di salute)          → minimo 5 anni
// Poiché la scheda paziente contiene entrambe le categorie, l'intera scheda non
// viene cancellata prima di 10 anni (prevale l'obbligo più lungo). I dati clinici
// non vengono MAI cancellati automaticamente: oltre soglia sono solo SEGNALATI per
// riesame/anonimizzazione, a valutazione della professionista.
return [
    'retention' => [
        // Documentazione dei periodi (in anni) per categoria di dato.
        'administrative_data_years' => (int) env('RETENTION_ADMIN_YEARS', 10), // anagrafica, contatto, pagamento
        'health_data_years'         => (int) env('RETENTION_HEALTH_YEARS', 5),  // stato di salute (minimo)

        // Soglia (anni) oltre la quale i pazienti "conclusi" vengono segnalati per
        // riesame/anonimizzazione. = periodo amministrativo (10 anni). Nessuna
        // cancellazione automatica dei dati clinici.
        'concluded_patient_years' => (int) env('RETENTION_CONCLUDED_YEARS', 10),

        // Cancellazione DEFINITIVA dei record già cestinati (soft-deleted) dopo N giorni.
        'trashed_grace_days' => (int) env('RETENTION_TRASHED_GRACE_DAYS', 30),

        // Conservazione del registro operazioni (audit log) in mesi.
        'audit_log_months' => (int) env('RETENTION_AUDIT_MONTHS', 24),
    ],
];
