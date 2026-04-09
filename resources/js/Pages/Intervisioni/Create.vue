<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ patients: Array });

const form = useForm({
    title: '',
    description: '',
    patient_id: '',
    scheduled_at: '',
    status: 'draft',
});

function submit() {
    form.post(route('intervisioni.store'));
}
</script>

<template>
    <AppLayout title="Nuova Intervisione">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Nuova Intervisione</h1>
        </template>

        <div class="max-w-2xl">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Titolo *</label>
                    <input v-model="form.title" type="text" placeholder="es. Caso clinico - Ansia e alimentazione"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Paziente (opzionale)</label>
                    <select v-model="form.patient_id"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                        <option value="">— nessun paziente —</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione / Obiettivo</label>
                    <textarea v-model="form.description" rows="3"
                        placeholder="Motivo dell'intervisione, obiettivi da raggiungere..."
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('intervisioni.index')" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Annulla
                    </a>
                    <button @click="submit" :disabled="form.processing"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2 rounded-xl transition-colors">
                        {{ form.processing ? 'Creazione…' : 'Crea intervisione' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
