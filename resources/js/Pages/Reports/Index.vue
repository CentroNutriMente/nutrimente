<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reports:  Object, // paginated
    patients: Array,
    filters:  Object,
});

const patientFilter = ref(props.filters?.patient_id ?? '');

function applyFilter() {
    router.get(route('reports.index'), { patient_id: patientFilter.value || undefined }, {
        preserveState: true, replace: true,
    });
}

function fmt(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('it-IT');
}

function destroy(report) {
    if (!confirm('Eliminare questo referto?')) return;
    router.delete(route('reports.destroy', report.id));
}
</script>

<template>
    <AppLayout title="Referti">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-xl font-semibold text-gray-800">Referti</h1>
                <div class="flex items-center gap-3">
                    <Link :href="route('report-templates.index')"
                        class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                        Modelli
                    </Link>
                    <Link :href="route('reports.create')"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                        + Nuovo referto
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-4xl space-y-5">

            <!-- Filtro paziente -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <label class="text-sm text-gray-500 shrink-0">Filtra per paziente:</label>
                <select v-model="patientFilter" @change="applyFilter"
                    class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Tutti i pazienti</option>
                    <option v-for="p in patients" :key="p.id" :value="p.id">
                        {{ p.last_name }} {{ p.first_name }}
                    </option>
                </select>
            </div>

            <!-- Vuoto -->
            <div v-if="reports.data.length === 0"
                class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <div class="text-4xl mb-3">📋</div>
                <p class="text-gray-500 font-medium mb-1">Nessun referto</p>
                <p class="text-sm text-gray-400 mb-5">
                    {{ patientFilter ? 'Nessun referto per questo paziente.' : 'Crea il tuo primo referto.' }}
                </p>
                <Link :href="route('reports.create')"
                    class="px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                    Nuovo referto
                </Link>
            </div>

            <!-- Lista referti -->
            <div v-else class="space-y-2">
                <Link v-for="report in reports.data" :key="report.id"
                    :href="route('reports.show', report.id)"
                    class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-purple-200 hover:shadow-sm transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-800 text-sm">{{ report.title }}</span>
                                <span v-if="report.template"
                                    class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">
                                    {{ report.template.name }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ report.patient?.last_name }} {{ report.patient?.first_name }}
                                <span class="text-gray-300 mx-1">·</span>
                                {{ fmt(report.report_date) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a :href="route('reports.pdf', report.id)" target="_blank"
                                @click.stop
                                class="px-2.5 py-1 border border-gray-200 text-gray-500 rounded-lg text-xs hover:bg-gray-50 transition-colors">
                                PDF
                            </a>
                            <button @click.prevent="destroy(report)"
                                class="p-1.5 text-gray-300 hover:text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Paginazione -->
            <div v-if="reports.last_page > 1" class="flex justify-center gap-2">
                <Link v-for="link in reports.links" :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-sm transition-colors',
                        link.active ? 'bg-purple-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                    v-html="link.label" />
            </div>
        </div>
    </AppLayout>
</template>
