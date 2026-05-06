<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient:       Object,
    gdprAvailable: Array,
});

const user = usePage().props.auth.user;
const activeTab = ref('appointments');

// ── Dates ──────────────────────────────────────────────────────────────────
const fmt     = (d) => d ? new Date(d).toLocaleDateString('it-IT', { day:'2-digit', month:'2-digit', year:'numeric' }) : '—';
const fmtTime = (d) => d ? new Date(d).toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' }) : '';
const fmtFull = (d) => d ? new Date(d).toLocaleString('it-IT', { weekday:'long', day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';

const MONTHS_IT = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                   'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
const DAYS_IT   = ['Lu','Ma','Me','Gi','Ve','Sa','Do'];

// ── Mini-calendar ──────────────────────────────────────────────────────────
const today = new Date();
const calMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1));

function prevMonth() { calMonth.value = new Date(calMonth.value.getFullYear(), calMonth.value.getMonth() - 1, 1); }
function nextMonth() { calMonth.value = new Date(calMonth.value.getFullYear(), calMonth.value.getMonth() + 1, 1); }

const calendarCells = computed(() => {
    const y = calMonth.value.getFullYear();
    const m = calMonth.value.getMonth();
    const firstDay = new Date(y, m, 1);
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const startPad = (firstDay.getDay() + 6) % 7; // Monday-first
    const cells = [];
    for (let i = 0; i < startPad; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) cells.push(new Date(y, m, d));
    return cells;
});

const appointmentDayKeys = computed(() => {
    const keys = new Set();
    (props.patient?.appointments ?? []).forEach(a => {
        const d = new Date(a.start_at);
        if (d >= today) keys.add(`${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`);
    });
    return keys;
});

function dayKey(date) {
    return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
}
function isToday(date) {
    return date && dayKey(date) === dayKey(today);
}
function hasAppointment(date) {
    return date && appointmentDayKeys.value.has(dayKey(date));
}

// ── Appointments ───────────────────────────────────────────────────────────
const upcomingAppointments = computed(() =>
    (props.patient?.appointments ?? [])
        .filter(a => new Date(a.start_at) >= today && a.status !== 'cancelled')
        .sort((a, b) => new Date(a.start_at) - new Date(b.start_at))
);
const pastAppointments = computed(() =>
    (props.patient?.appointments ?? [])
        .filter(a => new Date(a.start_at) < today)
        .sort((a, b) => new Date(b.start_at) - new Date(a.start_at))
        .slice(0, 5)
);

const cancelForm = useForm({});
function cancelAppointment(id) {
    if (!confirm('Vuoi davvero disdire questo appuntamento?')) return;
    cancelForm.post(route('patient.appointment.cancel', id));
}

const statusLabel = { scheduled:'Confermato', confirmed:'Confermato', cancelled:'Cancellato', completed:'Completato' };
const statusClass = {
    scheduled:  'bg-blue-100 text-blue-700',
    confirmed:  'bg-green-100 text-green-700',
    cancelled:  'bg-red-100 text-red-500',
    completed:  'bg-gray-100 text-gray-500',
};

// ── Invoices ───────────────────────────────────────────────────────────────
const invoiceStatusLabel = { draft:'Bozza', issued:'Da pagare', paid:'Pagata', cancelled:'Annullata' };
const invoiceStatusClass  = {
    draft:     'bg-gray-100 text-gray-500',
    issued:    'bg-amber-100 text-amber-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-500',
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Header -->
        <header class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-10">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/prenota" class="flex items-center gap-2">
                    <img src="/logo.jpeg" alt="NutriMente" class="h-10 w-auto" />
                </a>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500 hidden sm:block">{{ user.name }}</span>
                    <a href="/prenota"
                        class="text-sm text-purple-600 hover:text-purple-700 font-medium hidden sm:block">
                        + Prenota
                    </a>
                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                        <button type="submit"
                            class="text-xs text-gray-400 hover:text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                            Esci
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-8 space-y-6">

            <!-- No patient record -->
            <div v-if="!patient" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Benvenuto/a, {{ user.name }}!</h2>
                <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                    Il tuo account non è ancora collegato ad una cartella clinica.
                    Prenota il tuo primo appuntamento per iniziare.
                </p>
                <a href="/prenota"
                    class="inline-block bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                    Prenota un appuntamento
                </a>
            </div>

            <template v-else>

                <!-- Welcome bar -->
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Ciao, {{ patient.first_name }}!</h1>
                        <p class="text-sm text-gray-400 mt-0.5">La tua area personale NutriMente</p>
                    </div>
                    <a href="/prenota"
                        class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
                        + Nuovo appuntamento
                    </a>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 bg-white rounded-xl border border-gray-100 shadow-sm p-1 w-fit">
                    <button @click="activeTab = 'appointments'"
                        :class="activeTab === 'appointments' ? 'bg-purple-600 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Appuntamenti
                    </button>
                    <button @click="activeTab = 'invoices'"
                        :class="activeTab === 'invoices' ? 'bg-purple-600 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Fatture
                    </button>
                    <button @click="activeTab = 'consents'"
                        :class="activeTab === 'consents' ? 'bg-purple-600 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Consensi
                    </button>
                </div>

                <!-- ── TAB: APPUNTAMENTI ──────────────────────────────────── -->
                <div v-if="activeTab === 'appointments'" class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                    <!-- Mini calendar -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-fit">
                        <div class="flex items-center justify-between mb-4">
                            <button @click="prevMonth" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <span class="text-sm font-semibold text-gray-700">
                                {{ MONTHS_IT[calMonth.getMonth()] }} {{ calMonth.getFullYear() }}
                            </span>
                            <button @click="nextMonth" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Day headers -->
                        <div class="grid grid-cols-7 mb-1">
                            <div v-for="d in DAYS_IT" :key="d"
                                class="text-center text-xs font-medium text-gray-400 py-1">{{ d }}</div>
                        </div>

                        <!-- Day cells -->
                        <div class="grid grid-cols-7 gap-y-1">
                            <div v-for="(cell, i) in calendarCells" :key="i"
                                class="flex flex-col items-center justify-center h-9">
                                <div v-if="cell" class="relative w-8 h-8 flex items-center justify-center rounded-full text-sm transition-colors"
                                    :class="{
                                        'bg-purple-600 text-white font-bold': isToday(cell),
                                        'text-gray-700': !isToday(cell),
                                    }">
                                    {{ cell.getDate() }}
                                    <span v-if="hasAppointment(cell)"
                                        class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full"
                                        :class="isToday(cell) ? 'bg-white' : 'bg-purple-500'">
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                            Appuntamento programmato
                        </div>
                    </div>

                    <!-- Appointment list -->
                    <div class="lg:col-span-3 space-y-4">

                        <!-- Prossimi -->
                        <div>
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Prossimi appuntamenti</h3>
                            <div v-if="!upcomingAppointments.length"
                                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                                <p class="text-sm text-gray-400 mb-4">Nessun appuntamento in programma.</p>
                                <a href="/prenota"
                                    class="text-sm text-purple-600 font-medium hover:underline">
                                    Prenota ora →
                                </a>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="apt in upcomingAppointments" :key="apt.id"
                                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                                    <div class="flex items-start gap-4">
                                        <!-- Date badge -->
                                        <div class="shrink-0 bg-purple-50 rounded-xl px-3 py-2 text-center min-w-[56px]">
                                            <div class="text-lg font-bold text-purple-700 leading-none">
                                                {{ new Date(apt.start_at).getDate() }}
                                            </div>
                                            <div class="text-xs text-purple-500 mt-0.5 uppercase">
                                                {{ MONTHS_IT[new Date(apt.start_at).getMonth()].slice(0,3) }}
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-800 text-sm">{{ apt.title }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ fmtTime(apt.start_at) }}
                                                <span v-if="apt.user" class="ml-1">· {{ apt.user.name }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2 shrink-0">
                                            <span :class="[statusClass[apt.status] ?? 'bg-gray-100 text-gray-500', 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                                {{ statusLabel[apt.status] ?? apt.status }}
                                            </span>
                                            <button
                                                @click="cancelAppointment(apt.id)"
                                                :disabled="cancelForm.processing"
                                                class="text-xs text-red-400 hover:text-red-600 border border-red-200 hover:border-red-300 px-2.5 py-1 rounded-lg transition-colors disabled:opacity-50">
                                                Disdici
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passati (collapsed) -->
                        <div v-if="pastAppointments.length">
                            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Appuntamenti precedenti</h3>
                            <div class="space-y-2">
                                <div v-for="apt in pastAppointments" :key="apt.id"
                                    class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 opacity-60">
                                    <div class="shrink-0 bg-gray-50 rounded-lg px-2 py-1 text-center min-w-[44px]">
                                        <div class="text-sm font-bold text-gray-500 leading-none">
                                            {{ new Date(apt.start_at).getDate() }}
                                        </div>
                                        <div class="text-xs text-gray-400 uppercase">
                                            {{ MONTHS_IT[new Date(apt.start_at).getMonth()].slice(0,3) }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-600">{{ apt.title }}</div>
                                        <div class="text-xs text-gray-400">{{ fmtTime(apt.start_at) }}<span v-if="apt.user"> · {{ apt.user.name }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: FATTURE ────────────────────────────────────────── -->
                <div v-if="activeTab === 'invoices'">
                    <div v-if="!patient.invoices?.length"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                        <p class="text-sm text-gray-400">Nessuna fattura disponibile.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="inv in patient.invoices" :key="inv.id"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span v-if="inv.invoice_code" class="font-mono text-xs text-gray-500">{{ inv.invoice_code }}</span>
                                    <span :class="[invoiceStatusClass[inv.status], 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                        {{ invoiceStatusLabel[inv.status] ?? inv.status }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ fmt(inv.issued_at) }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-gray-800">
                                    € {{ Number(inv.total).toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}
                                </div>
                                <div class="flex gap-2 mt-1.5 justify-end">
                                    <a :href="route('invoices.pdf', inv.id)" target="_blank"
                                        class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                                        Visualizza
                                    </a>
                                    <a :href="route('invoices.pdf', inv.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        Scarica PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: CONSENSI ──────────────────────────────────────── -->
                <div v-if="activeTab === 'consents'" class="space-y-8">

                    <!-- Normative GDPR -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Informative e normative</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Privacy Policy -->
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 text-sm mb-1">Privacy Policy</div>
                                    <p class="text-xs text-gray-400 mb-3">Informativa sul trattamento dei dati personali ai sensi del GDPR.</p>
                                    <a v-if="gdprAvailable.some(d => d.slug === 'privacy-policy')"
                                        :href="route('gdpr.download', 'privacy-policy')"
                                        class="inline-flex items-center gap-1 text-xs text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Scarica PDF
                                    </a>
                                    <span v-else class="text-xs text-gray-300 italic">Non disponibile</span>
                                </div>
                            </div>

                            <!-- Consenso informato -->
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 text-sm mb-1">Consenso Informato</div>
                                    <p class="text-xs text-gray-400 mb-3">Modulo di consenso al trattamento clinico e ai servizi del centro.</p>
                                    <a v-if="gdprAvailable.some(d => d.slug === 'consenso-informato')"
                                        :href="route('gdpr.download', 'consenso-informato')"
                                        class="inline-flex items-center gap-1 text-xs text-purple-600 border border-purple-200 px-3 py-1.5 rounded-lg hover:bg-purple-50 transition-colors font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Scarica PDF
                                    </a>
                                    <span v-else class="text-xs text-gray-300 italic">Non disponibile</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consensi registrati -->
                    <div v-if="patient.consents?.length">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Consensi firmati</h3>
                        <div class="space-y-2">
                            <div v-for="c in patient.consents" :key="c.id"
                                class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                                <div :class="c.accepted ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'"
                                    class="w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            :d="c.accepted ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">{{ c.type }}</div>
                                    <div class="text-xs text-gray-400">{{ c.method }} · {{ fmt(c.accepted_at) }}</div>
                                </div>
                                <span :class="c.accepted ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0">
                                    {{ c.accepted ? 'Accettato' : 'Rifiutato' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Documenti firmati allegati -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Documenti allegati</h3>
                        <div v-if="!patient.consent_documents?.length"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                            <p class="text-sm text-gray-400">Nessun documento allegato dal tuo professionista.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="doc in patient.consent_documents" :key="doc.id"
                                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-800 text-sm truncate">{{ doc.title }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ fmt(doc.created_at) }}</div>
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <a :href="route('documents.view', doc.id)" target="_blank"
                                        class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                                        Visualizza
                                    </a>
                                    <a :href="route('documents.download', doc.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        Scarica
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </template>
        </main>

        <footer class="border-t border-gray-100 mt-12">
            <div class="max-w-5xl mx-auto px-4 py-6 text-xs text-gray-400 text-center">
                © {{ new Date().getFullYear() }} Centro NutriMente — Area riservata pazienti
            </div>
        </footer>
    </div>
</template>
