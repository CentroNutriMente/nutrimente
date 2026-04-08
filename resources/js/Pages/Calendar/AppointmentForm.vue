<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    appointment: Object,
    patients: Array,
    professionals: Array,
    prefill: Object,
});

const isEdit = !!props.appointment;

const form = useForm({
    user_id: props.appointment?.user_id ?? '',
    patient_id: props.appointment?.patient_id ?? props.prefill?.patient_id ?? '',
    type: props.appointment?.type ?? 'session',
    title: props.appointment?.title ?? '',
    description: props.appointment?.description ?? '',
    start_at: props.appointment?.start_at?.slice(0, 16) ?? props.prefill?.start_at?.slice(0, 16) ?? '',
    end_at: props.appointment?.end_at?.slice(0, 16) ?? '',
    room: props.appointment?.room ?? '',
    color: props.appointment?.color ?? '',
    is_shared: props.appointment?.is_shared ?? false,
});

const submit = () => {
    if (isEdit) {
        form.put(route('appointments.update', props.appointment.id));
    } else {
        form.post(route('appointments.store'));
    }
};

const typeOptions = [
    { value: 'session', label: 'Seduta' },
    { value: 'intervision', label: 'Intervisione' },
    { value: 'personal', label: 'Personale' },
    { value: 'blocked', label: 'Slot bloccato' },
];
</script>

<template>
    <AppLayout :title="isEdit ? 'Modifica appuntamento' : 'Nuovo appuntamento'">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('calendar')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ isEdit ? 'Modifica appuntamento' : 'Nuovo appuntamento' }}
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-2xl space-y-5">
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select v-model="form.type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Professionista *</label>
                        <select v-model="form.user_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Seleziona...</option>
                            <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <p v-if="form.errors.user_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                    </div>
                </div>

                <div v-if="form.type === 'session'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paziente</label>
                    <select v-model="form.patient_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Nessun paziente (evento generico)</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titolo *</label>
                    <input v-model="form.title" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Es. Seduta con Rossi Mario" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inizio *</label>
                        <input v-model="form.start_at" type="datetime-local" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.start_at" class="text-red-500 text-xs mt-1">{{ form.errors.start_at }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fine *</label>
                        <input v-model="form.end_at" type="datetime-local" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.end_at" class="text-red-500 text-xs mt-1">{{ form.errors.end_at }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stanza / Studio</label>
                    <input v-model="form.room" type="text" placeholder="Es. Studio 1, Sala riunioni..." class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea v-model="form.description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input v-model="form.is_shared" type="checkbox" id="is_shared" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                    <label for="is_shared" class="text-sm text-gray-700">Visibile a tutto il team</label>
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors"
                >
                    {{ form.processing ? 'Salvataggio...' : (isEdit ? 'Aggiorna' : 'Crea appuntamento') }}
                </button>
                <Link :href="route('calendar')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Annulla
                </Link>
            </div>
        </form>
    </AppLayout>
</template>
