<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    intervisione: Object,
    patients: Array,
    users: Array,
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
    participant_ids: (props.intervisione.participants ?? []).map(p => p.id),
});

function toggleUser(id) {
    const idx = form.participant_ids.indexOf(id);
    if (idx === -1) form.participant_ids.push(id);
    else form.participant_ids.splice(idx, 1);
}

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
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
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
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
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

            <!-- Partecipanti -->
            <div v-if="users && users.length > 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Partecipanti</h2>
                <div class="space-y-2">
                    <label
                        v-for="u in users"
                        :key="u.id"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl border cursor-pointer transition-colors"
                        :class="form.participant_ids.includes(u.id)
                            ? 'border-purple-400 bg-purple-50'
                            : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input
                            type="checkbox"
                            :value="u.id"
                            :checked="form.participant_ids.includes(u.id)"
                            @change="toggleUser(u.id)"
                            class="w-4 h-4 rounded text-purple-600 border-gray-300 focus:ring-purple-500"
                        />
                        <span class="text-sm text-gray-700">{{ u.name }}</span>
                    </label>
                </div>
                <p class="text-xs text-gray-400">Il creatore è sempre incluso.</p>
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
