<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    patient:       Object,  // null if no patient record found for this user
    professionals: Array,
});

const user = usePage().props.auth.user;

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT', { day:'2-digit', month:'2-digit', year:'numeric' }) : '—';
const fmtDatetime = (d) => d ? new Date(d).toLocaleString('it-IT', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';

const tabs = ['Appuntamenti', 'Referti', 'Fatture'];
const activeTab = ref('Appuntamenti');

const upcomingAppointments = computed(() =>
    props.patient?.appointments?.filter(a => new Date(a.start_at) >= new Date()) ?? []
);
const pastAppointments = computed(() =>
    props.patient?.appointments?.filter(a => new Date(a.start_at) < new Date()) ?? []
);

const invoiceStatusLabel = { draft:'Bozza', issued:'Emessa', paid:'Pagata', cancelled:'Annullata' };
const invoiceStatusClass = {
    draft: 'bg-gray-100 text-gray-600',
    issued: 'bg-amber-100 text-amber-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-600',
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Header -->
        <header class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-purple-600">NutriMente</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ user.name }}</span>
                    <a href="/logout" method="post"
                        class="text-xs text-gray-400 hover:text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                        Esci
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-10 space-y-8">

            <!-- No patient record warning -->
            <div v-if="!patient" class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                <div class="text-4xl mb-3">👋</div>
                <h2 class="text-lg font-semibold text-amber-800 mb-2">Benvenuto/a, {{ user.name }}!</h2>
                <p class="text-sm text-amber-700 mb-6">
                    Il tuo account non è ancora collegato ad una cartella clinica.<br>
                    Prenota il tuo primo appuntamento per iniziare.
                </p>
                <a href="/prenota"
                    class="inline-block bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                    Prenota un appuntamento
                </a>
            </div>

            <template v-else>
                <!-- Welcome + quick stats -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Ciao, {{ patient.first_name }}!</h1>
                            <p class="text-sm text-gray-400 mt-0.5">La tua area personale NutriMente</p>
                        </div>
                        <a href="/prenota"
                            class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
                            + Nuovo appuntamento
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-2xl font-bold text-purple-600">{{ patient.appointments?.length ?? 0 }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Appuntamenti</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-2xl font-bold text-teal-600">{{ patient.reports?.length ?? 0 }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Referti</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-2xl font-bold text-amber-600">{{ patient.invoices?.length ?? 0 }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Fatture</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 bg-white rounded-xl border border-gray-100 shadow-sm p-1 w-fit">
                    <button v-for="tab in tabs" :key="tab"
                        @click="activeTab = tab"
                        :class="activeTab === tab
                            ? 'bg-purple-600 text-white'
                            : 'text-gray-500 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        {{ tab }}
                    </button>
                </div>

                <!-- Appuntamenti -->
                <div v-if="activeTab === 'Appuntamenti'" class="space-y-6">

                    <!-- Upcoming -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3">Prossimi appuntamenti</h3>
                        <div v-if="!upcomingAppointments.length"
                            class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
                            Nessun appuntamento in programma.
                            <a href="/prenota" class="text-purple-600 hover:underline ml-1">Prenota ora →</a>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="apt in upcomingAppointments" :key="apt.id"
                                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">{{ apt.title }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        {{ fmtDatetime(apt.start_at) }}
                                        <span v-if="apt.user" class="ml-1">· {{ apt.user.name }}</span>
                                    </div>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{ apt.status }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Past -->
                    <div v-if="pastAppointments.length">
                        <h3 class="text-sm font-semibold text-gray-600 mb-3">Appuntamenti precedenti</h3>
                        <div class="space-y-2">
                            <div v-for="apt in pastAppointments" :key="apt.id"
                                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3 opacity-70">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-700 text-sm">{{ apt.title }}</div>
                                    <div class="text-xs text-gray-400">{{ fmtDatetime(apt.start_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referti -->
                <div v-if="activeTab === 'Referti'">
                    <div v-if="!patient.reports?.length"
                        class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
                        Nessun referto disponibile.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="rep in patient.reports" :key="rep.id"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-9 h-9 bg-teal-50 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-800 text-sm">{{ rep.title }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ fmt(rep.report_date) }} · {{ rep.user?.name }}</div>
                            </div>
                            <a :href="`/reports/${rep.id}/pdf`" target="_blank"
                                class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50">PDF</a>
                        </div>
                    </div>
                </div>

                <!-- Fatture -->
                <div v-if="activeTab === 'Fatture'">
                    <div v-if="!patient.invoices?.length"
                        class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
                        Nessuna fattura disponibile.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="inv in patient.invoices" :key="inv.id"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs text-gray-500">{{ inv.invoice_code }}</span>
                                    <span :class="[invoiceStatusClass[inv.status], 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                        {{ invoiceStatusLabel[inv.status] }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ fmt(inv.issued_at) }}</div>
                            </div>
                            <div class="font-semibold text-gray-800">
                                € {{ Number(inv.total).toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}
                            </div>
                            <a :href="`/invoices/${inv.id}/pdf`" target="_blank"
                                class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50">PDF</a>
                        </div>
                    </div>
                </div>

            </template>
        </main>

        <footer class="border-t border-gray-100 mt-16">
            <div class="max-w-4xl mx-auto px-4 py-6 text-xs text-gray-400 text-center">
                © {{ new Date().getFullYear() }} NutriMente
            </div>
        </footer>
    </div>
</template>
