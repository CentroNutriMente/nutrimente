<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patients: Array,
    users:    Array,
});

const form = useForm({
    title:           '',
    description:     '',
    patient_id:      '',
    scheduled_at:    '',
    status:          'draft',
    participant_ids: [],
});

function toggleUser(id) {
    const idx = form.participant_ids.indexOf(id);
    if (idx === -1) form.participant_ids.push(id);
    else form.participant_ids.splice(idx, 1);
}

function submit() {
    form.post(route('intervisioni.store'));
}
</script>

<template>
    <AppLayout title="Nuova Intervisione">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('intervisioni.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Nuova Intervisione</h1>
            </div>
        </template>

        <div class="max-w-2xl">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">

                <!-- Titolo -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Titolo *</label>
                    <input v-model="form.title" type="text"
                        placeholder="es. Caso clinico - Ansia e alimentazione"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Paziente -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Paziente (opzionale)</label>
                    <select v-model="form.patient_id"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                        <option value="">— nessun paziente —</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- Data + Stato -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Data programmata</label>
                        <input v-model="form.scheduled_at" type="datetime-local"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                        <select v-model="form.status"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="draft">Bozza</option>
                            <option value="scheduled">Programmata</option>
                        </select>
                    </div>
                </div>

                <!-- Descrizione -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione / Obiettivo</label>
                    <textarea v-model="form.description" rows="3"
                        placeholder="Motivo dell'intervisione, obiettivi da raggiungere..."
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                </div>

                <!-- Partecipanti -->
                <div v-if="users.length > 0">
                    <label class="block text-xs font-medium text-gray-500 mb-2">Partecipanti</label>
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
                    <p class="text-xs text-gray-400 mt-1.5">Sei già incluso come creatore.</p>
                </div>

                <!-- Azioni -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                    <Link :href="route('intervisioni.index')"
                        class="text-sm text-gray-500 hover:text-gray-700 px-5 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors text-center">
                        Annulla
                    </Link>
                    <button @click="submit" :disabled="form.processing"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors">
                        {{ form.processing ? 'Creazione…' : 'Crea intervisione' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
