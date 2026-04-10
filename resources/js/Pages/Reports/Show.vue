<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    report: Object,
});

function fmt(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('it-IT');
}

function destroy() {
    if (!confirm('Eliminare questo referto? L\'operazione è irreversibile.')) return;
    router.delete(route('reports.destroy', props.report.id));
}
</script>

<template>
    <AppLayout :title="report.title">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('reports.index')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-800">{{ report.title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('reports.pdf', report.id)" target="_blank"
                        class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Scarica PDF
                    </a>
                    <Link :href="route('reports.edit', report.id)"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                        Modifica
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">

            <!-- Referto principale -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Info generali -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <!-- Intestazione stile referto -->
                    <div class="flex items-start justify-between mb-4 pb-4 border-b border-gray-100">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">
                                {{ report.template?.header_title ?? report.title }}
                            </div>
                            <h2 class="text-lg font-semibold text-gray-800">{{ report.title }}</h2>
                        </div>
                        <span v-if="report.template"
                            class="text-xs bg-purple-50 text-purple-600 px-2.5 py-1 rounded-full font-medium shrink-0">
                            {{ report.template.name }}
                        </span>
                    </div>

                    <!-- Dati paziente -->
                    <div class="grid grid-cols-3 gap-4 text-sm mb-2">
                        <div>
                            <span class="text-xs text-gray-400 block">Paziente</span>
                            <span class="font-medium text-gray-800">
                                {{ report.patient?.last_name }} {{ report.patient?.first_name }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Data referto</span>
                            <span class="font-medium text-gray-800">{{ fmt(report.report_date) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Redatto da</span>
                            <span class="font-medium text-gray-800">{{ report.user?.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sezioni contenuto -->
                <div v-for="(sec, i) in report.sections_data" :key="i"
                    class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">{{ sec.label }}</h3>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                        {{ sec.content || '—' }}
                    </div>
                </div>

                <!-- Prossimo appuntamento -->
                <div v-if="report.next_appointment"
                    class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Prossimo Appuntamento</h3>
                    <p class="text-sm font-medium text-gray-800">{{ report.next_appointment }}</p>
                </div>

                <!-- Firma -->
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                    <p class="text-xs text-gray-400 mb-1">La/Il Professionista Refertante</p>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ report.user?.professional_profile?.title ? report.user.professional_profile.title + ' ' : '' }}{{ report.user?.name }}
                    </p>
                    <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                        <div v-if="report.user?.professional_profile?.albo_professionale">
                            {{ report.user.professional_profile.albo_professionale }}
                            {{ report.user.professional_profile.numero_albo ? 'n° ' + report.user.professional_profile.numero_albo : '' }}
                        </div>
                        <div v-if="report.user?.professional_profile?.partita_iva">
                            P.IVA {{ report.user.professional_profile.partita_iva }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">

                <!-- Meta -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-700 text-sm mb-3">Informazioni</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Creato il</span>
                            <span class="text-gray-700">{{ fmt(report.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Modello</span>
                            <span class="text-gray-700">{{ report.template?.name ?? 'Nessuno' }}</span>
                        </div>
                        <div v-if="report.notes" class="pt-2 border-t border-gray-100">
                            <span class="text-xs text-gray-400 block mb-1">Note interne</span>
                            <p class="text-xs text-gray-600 leading-relaxed">{{ report.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-2">
                    <a :href="route('reports.pdf', report.id)" target="_blank"
                        class="flex items-center gap-2 w-full px-4 py-2.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Scarica PDF
                    </a>
                    <Link :href="route('reports.edit', report.id)"
                        class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                        Modifica referto
                    </Link>
                    <button @click="destroy"
                        class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-red-200 text-red-500 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                        Elimina referto
                    </button>
                </div>

                <!-- Nuovo referto stesso paziente -->
                <Link :href="route('reports.create', { patient_id: report.patient_id })"
                    class="block text-center text-xs text-purple-600 hover:underline py-1">
                    + Nuovo referto per questo paziente
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
