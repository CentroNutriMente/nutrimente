<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient: Object,
    records: Array,
});

const form = useForm({
    category: 'psicologo',
    record_type: 'seduta',
    title: '',
    notes: '',
    treatment_plan: '',
    record_date: new Date().toISOString().slice(0, 10),
    is_shared_with_team: false,
});

function submit() {
    form.post(route('patients.records.store', props.patient.id), {
        onSuccess: () => form.reset('title', 'notes', 'treatment_plan'),
    });
}

const categories = [
    { value: 'psicologo', label: 'Psicologia' },
    { value: 'nutrizionista', label: 'Nutrizione' },
    { value: 'osteopata', label: 'Osteopatia' },
    { value: 'generale', label: 'Generale' },
];

const recordTypes = [
    { value: 'anamnesi', label: 'Anamnesi' },
    { value: 'seduta', label: 'Seduta' },
    { value: 'valutazione', label: 'Valutazione nutrizionale' },
    { value: 'follow-up', label: 'Follow-up' },
    { value: 'trattamento', label: 'Trattamento osteopatico' },
    { value: 'colloquio', label: 'Colloquio' },
    { value: 'test', label: 'Test / Assessment' },
];

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
</script>

<template>
    <AppLayout :title="`Cartella – ${patient.last_name} ${patient.first_name}`">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.show', patient.id)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Cartella clinica — {{ patient.last_name }} {{ patient.first_name }}
                </h1>
            </div>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Nuova scheda -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Nuova scheda clinica</h2>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                                <select v-model="form.category"
                                    class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo scheda</label>
                                <select v-model="form.record_type"
                                    class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                    <option v-for="t in recordTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Titolo *</label>
                                <input v-model="form.title" type="text" placeholder="es. Seduta n.3 – elaborazione trauma"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                                <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Data *</label>
                                <input v-model="form.record_date" type="date"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Note cliniche</label>
                            <textarea v-model="form.notes" rows="5"
                                placeholder="Annotazioni sulla seduta, stato del paziente, osservazioni cliniche..."
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Piano di trattamento</label>
                            <textarea v-model="form.treatment_plan" rows="3"
                                placeholder="Obiettivi, interventi programmati, homework..."
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="form.is_shared_with_team = !form.is_shared_with_team"
                                :class="form.is_shared_with_team ? 'bg-purple-600' : 'bg-gray-200'"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none shrink-0">
                                <span :class="form.is_shared_with_team ? 'translate-x-5' : 'translate-x-1'"
                                    class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform" />
                            </button>
                            <span class="text-sm text-gray-600">Condividi con tutto il team</span>
                        </div>

                        <div class="pt-2 text-xs text-amber-600 bg-amber-50 rounded-xl px-4 py-3">
                            Le note cliniche sono soggette al segreto professionale (art. 622 c.p.). La condivisione con il team richiede il consenso del paziente.
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Link :href="route('patients.show', patient.id)"
                                class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                                Annulla
                            </Link>
                            <button @click="submit" :disabled="form.processing"
                                class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2 rounded-xl transition-colors">
                                {{ form.processing ? 'Salvataggio…' : 'Salva scheda' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storico schede -->
            <div>
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Storico schede</h2>
                <div v-if="records.length === 0" class="text-sm text-gray-400 bg-white rounded-2xl border border-gray-100 p-6 text-center">
                    Nessuna scheda ancora.
                </div>
                <div v-else class="space-y-2">
                    <div v-for="rec in records" :key="rec.id"
                        class="bg-white rounded-xl border border-gray-100 p-4 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-medium text-gray-800 leading-tight">{{ rec.title }}</span>
                            <span class="text-xs text-gray-400 shrink-0">{{ fmt(rec.record_date) }}</span>
                        </div>
                        <div class="flex gap-1 mt-1.5">
                            <span class="text-xs bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">{{ rec.record_type }}</span>
                            <span class="text-xs bg-gray-50 text-gray-500 px-1.5 py-0.5 rounded">{{ rec.category }}</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">{{ rec.user?.name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
