<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({ templates: Array, authUserId: Number });

function destroy(template) {
    if (!confirm(`Eliminare il modello "${template.name}"?`)) return;
    router.delete(route('questionnaire-templates.destroy', template.id));
}
</script>

<template>
    <AppLayout title="Gestione Questionari">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Gestione Questionari</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Crea e gestisci i modelli di questionario. I questionari per i pazienti si compilano dalla loro scheda.</p>
                </div>
                <Link :href="route('questionnaire-templates.create')"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                    + Nuovo modello
                </Link>
            </div>
        </template>

        <div class="max-w-4xl space-y-4">

            <div v-if="templates.length === 0"
                class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <p class="text-gray-500 font-medium mb-1">Nessun modello ancora</p>
                <p class="text-sm text-gray-400 mb-5">Crea il tuo primo modello di questionario personalizzato</p>
                <Link :href="route('questionnaire-templates.create')"
                    class="px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                    Crea modello
                </Link>
            </div>

            <div v-for="tmpl in templates" :key="tmpl.id"
                class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4 hover:border-purple-200 transition-colors">

                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-800 text-sm mb-0.5">{{ tmpl.name }}</div>
                    <p v-if="tmpl.description" class="text-xs text-gray-400 mb-2">{{ tmpl.description }}</p>
                    <span class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-medium">
                        {{ tmpl.questions?.length ?? 0 }} domande
                    </span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span v-if="tmpl.user_id !== authUserId"
                        class="text-xs text-gray-400 italic mr-1">
                        Creato da un collega
                    </span>
                    <template v-if="tmpl.user_id === authUserId">
                        <Link :href="route('questionnaire-templates.edit', tmpl.id)"
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
                    </template>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
