<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ tags: Array });

const form = useForm({
    first_name: '',
    last_name: '',
    codice_fiscale: '',
    date_of_birth: '',
    gender: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    cap: '',
    medico_base: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    notes: '',
    tags: [],
});

const submit = () => form.post(route('patients.store'));

const toggleTag = (id) => {
    const idx = form.tags.indexOf(id);
    idx === -1 ? form.tags.push(id) : form.tags.splice(idx, 1);
};

const tagColor = (tag) => {
    const selected = form.tags.includes(tag.id);
    return selected
        ? { backgroundColor: tag.color, color: '#fff', borderColor: tag.color }
        : { backgroundColor: tag.color + '22', color: tag.color, borderColor: tag.color + '44' };
};
</script>

<template>
    <AppLayout title="Nuovo paziente">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Nuovo paziente</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <!-- Dati anagrafici -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-700 mb-4">Dati anagrafici</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input v-model="form.first_name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.first_name" class="text-red-500 text-xs mt-1">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cognome *</label>
                        <input v-model="form.last_name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.last_name" class="text-red-500 text-xs mt-1">{{ form.errors.last_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                        <input v-model="form.codice_fiscale" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-purple-500" maxlength="16" />
                        <p v-if="form.errors.codice_fiscale" class="text-red-500 text-xs mt-1">{{ form.errors.codice_fiscale }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data di nascita</label>
                        <input v-model="form.date_of_birth" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Genere</label>
                        <select v-model="form.gender" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">—</option>
                            <option value="M">Maschio</option>
                            <option value="F">Femmina</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Medico di base</label>
                        <input v-model="form.medico_base" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                </div>
            </div>

            <!-- Contatti -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-700 mb-4">Contatti</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                        <input v-model="form.phone" type="tel" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                        <input v-model="form.address" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                        <input v-model="form.city" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                        <input v-model="form.cap" type="text" maxlength="5" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contatto emergenza (nome)</label>
                        <input v-model="form.emergency_contact_name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contatto emergenza (tel)</label>
                        <input v-model="form.emergency_contact_phone" type="tel" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                </div>
            </div>

            <!-- Tag -->
            <div v-if="tags.length" class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-700 mb-4">Tag / Disturbi</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        @click="toggleTag(tag.id)"
                        :style="tagColor(tag)"
                        class="text-sm px-3 py-1 rounded-full border font-medium transition-all"
                    >{{ tag.name }}</button>
                </div>
            </div>

            <!-- Note -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-700 mb-4">Note</h2>
                <textarea v-model="form.notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Note interne sul paziente..."></textarea>
            </div>

            <!-- Disclaimer GDPR -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800">
                I dati inseriti sono protetti da segreto professionale ai sensi degli artt. 9 e 10 del GDPR (Reg. EU 679/2016).
                Il trattamento è finalizzato esclusivamente alla gestione della relazione terapeutica.
                Questo sistema non sostituisce i servizi di emergenza (tel. 118).
            </div>

            <!-- Azioni -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors"
                >
                    {{ form.processing ? 'Salvataggio...' : 'Salva paziente' }}
                </button>
                <Link :href="route('patients.index')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Annulla
                </Link>
            </div>
        </form>
    </AppLayout>
</template>
