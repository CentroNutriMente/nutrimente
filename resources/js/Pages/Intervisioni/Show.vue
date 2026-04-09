<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    intervisione: Object,
    patients: Array,
});

const form = useForm({
    title: props.intervisione.title,
    description: props.intervisione.description ?? '',
    discussion_notes: props.intervisione.discussion_notes ?? '',
    conclusions: props.intervisione.conclusions ?? '',
    patient_id: props.intervisione.patient_id ?? '',
    scheduled_at: props.intervisione.scheduled_at
        ? new Date(props.intervisione.scheduled_at).toISOString().slice(0, 16)
        : '',
    status: props.intervisione.status,
});

const successMsg = ref('');

function save() {
    form.put(route('intervisioni.update', props.intervisione.id), {
        onSuccess: () => {
            successMsg.value = 'Intervisione aggiornata.';
            setTimeout(() => (successMsg.value = ''), 3000);
        },
    });
}

function deleteRecord() {
    if (confirm('Eliminare questa intervisione?')) {
        router.delete(route('intervisioni.destroy', props.intervisione.id));
    }
}

const statusLabel = { draft: 'Bozza', scheduled: 'Programmata', completed: 'Completata' };
const statusColor = {
    draft: 'bg-gray-100 text-gray-500',
    scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
};
</script>

<template>
    <AppLayout :title="`Intervisione – ${intervisione.title}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold text-gray-800">{{ intervisione.title }}</h1>
                    <span :class="[statusColor[intervisione.status], 'text-xs px-2 py-0.5 rounded-full font-medium']">
                        {{ statusLabel[intervisione.status] }}
                    </span>
                </div>
                <button @click="deleteRecord" class="text-xs text-red-400 hover:text-red-600 hover:underline">Elimina</button>
            </div>
        </template>

        <div class="max-w-3xl space-y-6">
            <div v-if="successMsg" class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                {{ successMsg }}
            </div>

            <!-- Dettagli -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Dettagli</h2>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Titolo</label>
                    <input v-model="form.title" type="text"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Paziente</label>
                        <select v-model="form.patient_id"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">— nessuno —</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Data programmata</label>
                        <input v-model="form.scheduled_at" type="datetime-local"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                        <select v-model="form.status"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="draft">Bozza</option>
                            <option value="scheduled">Programmata</option>
                            <option value="completed">Completata</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione / Obiettivo</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                </div>
            </div>

            <!-- Note di discussione -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Note di discussione</h2>
                <textarea v-model="form.discussion_notes" rows="6"
                    placeholder="Annotazioni durante l'intervisione, contributi dei professionisti..."
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
            </div>

            <!-- Conclusioni -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Conclusioni</h2>
                <textarea v-model="form.conclusions" rows="4"
                    placeholder="Decisioni prese, piano d'azione concordato..."
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
            </div>

            <div class="flex justify-end pb-8">
                <button @click="save" :disabled="form.processing"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors">
                    {{ form.processing ? 'Salvataggio…' : 'Salva modifiche' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
