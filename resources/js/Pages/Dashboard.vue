<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    upcomingAppointments: Array,
});

const statCards = [
    {
        label: 'Pazienti totali',
        value: props.stats.total_patients,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        color: 'bg-blue-50 text-blue-600',
    },
    {
        label: 'Appuntamenti oggi',
        value: props.stats.appointments_today,
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        color: 'bg-purple-50 text-purple-600',
    },
    {
        label: 'Questa settimana',
        value: props.stats.appointments_week,
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        color: 'bg-violet-50 text-violet-600',
    },
    {
        label: 'Fatture da incassare',
        value: props.stats.invoices_unpaid,
        icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        color: 'bg-amber-50 text-amber-600',
    },
    {
        label: 'Incassato questo mese',
        value: `€ ${Number(props.stats.revenue_month).toLocaleString('it-IT', { minimumFractionDigits: 2 })}`,
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        color: 'bg-green-50 text-green-600',
    },
];

const formatDate = (d) => new Date(d).toLocaleDateString('it-IT', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

const statusColor = {
    scheduled: 'bg-blue-100 text-blue-700',
    confirmed: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-700',
    completed: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4"
            >
                <div :class="[card.color, 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0']">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900">{{ card.value }}</div>
                    <div class="text-xs text-gray-500">{{ card.label }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Prossimi appuntamenti -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Prossimi appuntamenti</h2>
                    <Link :href="route('calendar')" class="text-sm text-purple-600 hover:underline">Vai al calendario</Link>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-if="upcomingAppointments.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                        Nessun appuntamento in programma.
                    </div>
                    <div
                        v-for="apt in upcomingAppointments"
                        :key="apt.id"
                        class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition-colors"
                    >
                        <div class="text-xs text-gray-500 w-36 shrink-0">{{ formatDate(apt.start_at) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800 truncate">{{ apt.title }}</div>
                            <div v-if="apt.patient" class="text-xs text-gray-400 truncate">
                                {{ apt.patient.first_name }} {{ apt.patient.last_name }}
                            </div>
                        </div>
                        <span :class="[statusColor[apt.status], 'text-xs px-2 py-1 rounded-full font-medium shrink-0']">
                            {{ apt.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Azioni rapide -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Azioni rapide</h2>
                </div>
                <div class="p-4 space-y-2">
                    <Link
                        :href="route('patients.create')"
                        class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors text-sm font-medium text-gray-700"
                    >
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Nuovo paziente
                    </Link>
                    <Link
                        :href="route('appointments.create')"
                        class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors text-sm font-medium text-gray-700"
                    >
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Nuovo appuntamento
                    </Link>
                    <Link
                        :href="route('invoices.create')"
                        class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-amber-300 hover:bg-amber-50 transition-colors text-sm font-medium text-gray-700"
                    >
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Nuova fattura
                    </Link>
                    <Link
                        :href="route('intervisioni.store')"
                        class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-violet-300 hover:bg-violet-50 transition-colors text-sm font-medium text-gray-700"
                    >
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                        Nuova intervisione
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
