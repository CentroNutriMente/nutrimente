<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    todayAppointments: Array,
    recentActivity: Array,
    userName: String,
});

const now = new Date();
const greeting = now.getHours() < 12 ? 'Buongiorno' : now.getHours() < 18 ? 'Buon pomeriggio' : 'Buonasera';

const todayLabel = now.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const statCards = [
    {
        label: 'Appuntamenti Oggi',
        value: props.stats.appointments_today,
        sub: 'programmati',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: 'Pazienti Attivi',
        value: props.stats.active_patients,
        sub: 'in carico',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        label: 'Fatture Mese',
        value: props.stats.invoices_month,
        sub: now.toLocaleDateString('it-IT', { month: 'long' }),
        icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        label: 'Fatturato Mese',
        value: `€ ${Number(props.stats.revenue_month).toLocaleString('it-IT', { minimumFractionDigits: 0 })}`,
        sub: 'lordo',
        icon: 'M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];

const formatTime = (d) => new Date(d).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
const formatDateTime = (d) => new Date(d).toLocaleString('it-IT', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }).replace(',', ' ·');

const typeLabel = { session: 'Seduta', intervision: 'Intervisione', personal: 'Personale', blocked: 'Bloccato' };
</script>

<template>
    <AppLayout title="Dashboard">

        <!-- Intestazione -->
        <div class="mb-7">
            <h1 class="text-3xl font-bold text-gray-900">{{ greeting }}</h1>
            <p class="text-gray-400 mt-0.5 capitalize">{{ todayLabel }}</p>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-start gap-4"
            >
                <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-medium mb-0.5">{{ card.label }}</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ card.value }}</p>
                    <p v-if="card.sub" class="text-xs text-gray-400 mt-0.5">{{ card.sub }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Appuntamenti di oggi -->
            <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Appuntamenti di Oggi</h2>
                    <span
                        v-if="todayAppointments.length"
                        class="text-xs bg-purple-100 text-purple-700 font-medium px-2.5 py-1 rounded-full"
                    >{{ todayAppointments.length }} {{ todayAppointments.length === 1 ? 'seduta' : 'sedute' }}</span>
                </div>

                <div class="p-4">
                    <div v-if="todayAppointments.length === 0" class="py-10 text-center text-gray-400 text-sm">
                        Nessun appuntamento per oggi.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="apt in todayAppointments"
                            :key="apt.id"
                            class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:border-purple-100 hover:bg-purple-50/30 transition-colors cursor-pointer"
                        >
                            <!-- Orario -->
                            <div class="w-16 shrink-0 text-center">
                                <div class="text-sm font-semibold text-gray-800">{{ formatTime(apt.start_at) }}</div>
                                <div class="text-xs text-gray-400">{{ formatTime(apt.end_at) }}</div>
                            </div>

                            <!-- Divisore -->
                            <div class="w-px h-8 bg-gray-200 shrink-0"></div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-800">
                                    {{ apt.patient ? `${apt.patient.first_name} ${apt.patient.last_name}` : apt.title }}
                                </div>
                                <div v-if="apt.patient" class="text-xs text-gray-400">
                                    {{ apt.patient.first_name }} {{ apt.patient.last_name }}
                                </div>
                            </div>

                            <!-- Badge tipo -->
                            <span class="text-xs bg-purple-100 text-purple-700 font-medium px-2.5 py-1 rounded-full shrink-0">
                                {{ typeLabel[apt.type] ?? apt.type }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-4">
                    <Link :href="route('calendar')" class="text-sm text-purple-600 hover:underline font-medium">
                        Vai al calendario →
                    </Link>
                </div>
            </div>

            <!-- Colonna destra -->
            <div class="space-y-4">
                <!-- Attività recente -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Attività Recente</h2>
                    </div>
                    <div class="p-4">
                        <div v-if="recentActivity.length === 0" class="py-4 text-center text-gray-400 text-sm">
                            Nessuna attività oggi.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="act in recentActivity" :key="act.id" class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-purple-500 mt-1.5 shrink-0"></div>
                                <div>
                                    <div class="text-sm font-medium text-gray-800">
                                        {{ act.patient ? `${act.patient.first_name} ${act.patient.last_name}` : act.title }}
                                    </div>
                                    <div class="text-xs text-gray-400">{{ formatDateTime(act.start_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance GDPR -->
                <div class="bg-purple-50 rounded-2xl border border-purple-100 p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="font-semibold text-gray-800 text-sm">Compliance GDPR</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Tutti i dati sono protetti secondo il regolamento GDPR e le linee guida CNOP per il segreto professionale.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
