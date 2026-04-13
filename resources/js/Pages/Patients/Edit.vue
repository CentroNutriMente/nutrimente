<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient: Object,
    tags: Array,
});

const form = useForm({
    first_name: props.patient.first_name,
    last_name: props.patient.last_name,
    codice_fiscale: props.patient.codice_fiscale ?? '',
    date_of_birth: props.patient.date_of_birth?.slice(0, 10) ?? '',
    gender: props.patient.gender ?? '',
    email: props.patient.email ?? '',
    phone: props.patient.phone ?? '',
    address: props.patient.address ?? '',
    city: props.patient.city ?? '',
    cap: props.patient.cap ?? '',
    notes: props.patient.notes ?? '',
    tags: props.patient.tags?.map(t => t.id) ?? [],
});

function submit() {
    form.put(route('patients.update', props.patient.id));
}

function toggleTag(id) {
    const idx = form.tags.indexOf(id);
    if (idx === -1) form.tags.push(id);
    else form.tags.splice(idx, 1);
}
</script>

<template>
    <AppLayout :title="`Modifica – ${patient.last_name} ${patient.first_name}`">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.show', patient.id)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Modifica paziente</h1>
            </div>
        </template>

        <div class="max-w-3xl space-y-6">

            <!-- Anagrafica -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Dati anagrafici</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nome *</label>
                        <input v-model="form.first_name" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cognome *</label>
                        <input v-model="form.last_name" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Codice Fiscale</label>
                        <input v-model="form.codice_fiscale" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 uppercase" />
                        <p v-if="form.errors.codice_fiscale" class="text-xs text-red-500 mt-1">{{ form.errors.codice_fiscale }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Data di nascita</label>
                        <input v-model="form.date_of_birth" type="date"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Genere</label>
                        <select v-model="form.gender"
                            class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">— seleziona —</option>
                            <option value="M">Maschio</option>
                            <option value="F">Femmina</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contatti -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Contatti</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                        <input v-model="form.email" type="email"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Telefono</label>
                        <input v-model="form.phone" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Indirizzo</label>
                        <input v-model="form.address" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Città</label>
                        <input v-model="form.city" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">CAP</label>
                        <input v-model="form.cap" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                </div>
            </div>

            <!-- Tag -->
            <div v-if="tags.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Tag / Disturbi</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        @click="toggleTag(tag.id)"
                        :style="form.tags.includes(tag.id) ? { backgroundColor: tag.color + '33', borderColor: tag.color, color: tag.color } : {}"
                        :class="[form.tags.includes(tag.id) ? 'border-current' : 'border-gray-200 text-gray-500 hover:border-gray-300', 'text-xs px-3 py-1.5 rounded-full border font-medium transition-colors']"
                    >
                        {{ tag.name }}
                    </button>
                </div>
            </div>

            <!-- Note -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Note generali</h2>
                <textarea v-model="form.notes" rows="4"
                    placeholder="Note non cliniche (es. preferenze di contatto, informazioni logistiche...)"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
            </div>

            <div class="flex justify-end gap-3 pb-8">
                <Link :href="route('patients.show', patient.id)"
                    class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                    Annulla
                </Link>
                <button @click="submit" :disabled="form.processing"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors">
                    {{ form.processing ? 'Salvataggio…' : 'Salva modifiche' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
