<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ templates: Array });

const flash = usePage().props.flash;

function destroy(template) {
    if (!confirm(`Eliminare il modello "${template.name}"?`)) return;
    router.delete(route('report-templates.destroy', template.id));
}
</script>

<template>
    <AppLayout title="Modelli referto">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-xl font-semibold text-gray-800">Modelli di Referto</h1>
                <Link :href="route('report-templates.create')"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                    + Nuovo modello
                </Link>
            </div>
        </template>

        <div class="max-w-4xl space-y-4">

            <!-- Vuoto -->
            <div v-if="templates.length === 0"
                class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <div class="text-4xl mb-3">📄</div>
                <p class="text-gray-500 font-medium mb-1">Nessun modello ancora</p>
                <p class="text-sm text-gray-400 mb-5">Crea il tuo primo modello di referto personalizzato</p>
                <Link :href="route('report-templates.create')"
                    class="px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                    Crea modello
                </Link>
            </div>

            <!-- Lista -->
            <div v-for="tmpl in templates" :key="tmpl.id"
                class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4 hover:border-purple-200 transition-colors">

                <!-- Icona -->
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm">{{ tmpl.name }}</span>
                        <span v-if="tmpl.is_default"
                            class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">
                            Predefinito
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mb-1">{{ tmpl.header_title }}</p>
                    <p v-if="tmpl.description" class="text-xs text-gray-400">{{ tmpl.description }}</p>
                    <div class="flex gap-2 mt-2 flex-wrap">
                        <span v-for="sec in tmpl.sections.slice(0, 4)" :key="sec.id"
                            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                            {{ sec.label }}
                        </span>
                        <span v-if="tmpl.sections.length > 4" class="text-xs text-gray-400">
                            +{{ tmpl.sections.length - 4 }} sezioni
                        </span>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('reports.create', { template_id: tmpl.id })"
                        class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-medium hover:bg-purple-700 transition-colors">
                        Usa
                    </Link>
                    <Link :href="route('report-templates.edit', tmpl.id)"
                        class="px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                        Modifica
                    </Link>
                    <button @click="destroy(tmpl)"
                        class="p-1.5 text-gray-300 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Link a tutti i referti -->
            <div class="flex justify-end">
                <Link :href="route('reports.index')" class="text-sm text-purple-600 hover:underline">
                    Vai ai referti →
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
