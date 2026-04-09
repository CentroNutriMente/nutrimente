<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="GDPR & Privacy">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">GDPR & Privacy</h1>
        </template>

        <div class="max-w-4xl space-y-6">

            <!-- Banner compliance -->
            <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6 flex items-start gap-4">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800 mb-1">Compliance attiva</h2>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Tutti i dati dei pazienti sono trattati in conformità al Regolamento UE 2016/679 (GDPR) e alle linee guida del
                        Consiglio Nazionale dell'Ordine degli Psicologi (CNOP) in materia di segreto professionale e trattamento
                        dei dati personali in ambito sanitario.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Basi giuridiche -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Basi giuridiche del trattamento
                    </h2>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mt-2 shrink-0"></span>
                            <div><span class="font-medium text-gray-700">Art. 9 GDPR</span> — Trattamento di dati particolari (sanitari) su base contrattuale per la prestazione di cura</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mt-2 shrink-0"></span>
                            <div><span class="font-medium text-gray-700">Consenso esplicito</span> — Acquisito per iscritto prima dell'inizio del trattamento</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mt-2 shrink-0"></span>
                            <div><span class="font-medium text-gray-700">Obblighi di legge</span> — Conservazione minima 10 anni (DM 14/02/1997)</div>
                        </li>
                    </ul>
                </div>

                <!-- Misure tecniche -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Misure tecniche e organizzative
                    </h2>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-2 shrink-0"></span>
                            <div>Accesso ai dati limitato per ruolo (RBAC)</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-2 shrink-0"></span>
                            <div>Autenticazione a due fattori disponibile</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-2 shrink-0"></span>
                            <div>Registro audit log di tutte le operazioni sensibili</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-2 shrink-0"></span>
                            <div>Soft-delete dei pazienti (nessuna cancellazione definitiva accidentale)</div>
                        </li>
                        <li class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-2 shrink-0"></span>
                            <div>Documenti sensibili su storage privato (non accessibile pubblicamente)</div>
                        </li>
                    </ul>
                </div>

                <!-- Diritti degli interessati -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Diritti degli interessati (pazienti)
                    </h2>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex gap-2 items-start">
                            <span class="font-medium text-gray-700 w-28 shrink-0">Art. 15</span>
                            <span>Diritto di accesso ai propri dati</span>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="font-medium text-gray-700 w-28 shrink-0">Art. 16</span>
                            <span>Diritto di rettifica</span>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="font-medium text-gray-700 w-28 shrink-0">Art. 17</span>
                            <span>Diritto alla cancellazione ("oblio")</span>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="font-medium text-gray-700 w-28 shrink-0">Art. 20</span>
                            <span>Portabilità dei dati</span>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="font-medium text-gray-700 w-28 shrink-0">Art. 21</span>
                            <span>Diritto di opposizione al trattamento</span>
                        </li>
                    </ul>
                </div>

                <!-- Titolare e DPO -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Titolare del trattamento
                    </h2>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><span class="font-medium text-gray-700">Studio:</span> Centro NutriMente — Studio Associato</p>
                        <p><span class="font-medium text-gray-700">Finalità:</span> Erogazione di prestazioni sanitarie psicologiche, nutrizionali e osteopatiche</p>
                        <p><span class="font-medium text-gray-700">Conservazione:</span> Per tutta la durata del rapporto terapeutico + 10 anni</p>
                        <p><span class="font-medium text-gray-700">Trasferimento a terzi:</span> Solo se richiesto da obblighi di legge (SSN, SST)</p>
                    </div>
                </div>
            </div>

            <!-- Segreto professionale -->
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-6">
                <h2 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Segreto professionale e condivisione interna
                </h2>
                <p class="text-sm text-gray-700 leading-relaxed">
                    I dati clinici dei pazienti sono soggetti al segreto professionale (art. 622 c.p. e norme deontologiche).
                    La condivisione interdisciplinare (intervisioni, cartelle condivise) è permessa <strong>solo con il consenso esplicito del paziente</strong>
                    e limitata alle informazioni strettamente necessarie per la continuità delle cure (principio di minimizzazione, art. 5 GDPR).
                </p>
            </div>
        </div>
    </AppLayout>
</template>
