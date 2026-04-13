<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ professional: Object });

const profile = props.professional.professional_profile ?? {};

const form = useForm({
    title: profile.title ?? '',
    category: profile.category ?? '',
    bio: profile.bio ?? '',
    curriculum: profile.curriculum ?? '',
    phone: profile.phone ?? '',
    website: profile.website ?? '',
    partita_iva: profile.partita_iva ?? '',
    codice_fiscale: profile.codice_fiscale ?? '',
    regime_fiscale: profile.regime_fiscale ?? '',
    cassa_previdenziale: profile.cassa_previdenziale ?? '',
    albo_professionale: profile.albo_professionale ?? '',
    numero_albo: profile.numero_albo ?? '',
    is_bookable: profile.is_bookable ?? false,
    session_duration_minutes: profile.session_duration_minutes ?? 50,
    session_price: profile.session_price ?? '',
});

const successMsg = ref('');

function save() {
    form.put(route('professionals.update', props.professional.id), {
        onSuccess: () => {
            successMsg.value = 'Profilo aggiornato con successo.';
            setTimeout(() => (successMsg.value = ''), 3000);
        },
    });
}

const roleLabel = {
    admin: 'Admin',
    psicologo: 'Psicologo',
    nutrizionista: 'Nutrizionista',
    osteopata: 'Osteopata',
    collaboratore: 'Collaboratore',
};

const roleColor = {
    admin: 'bg-purple-100 text-purple-700',
    psicologo: 'bg-blue-100 text-blue-700',
    nutrizionista: 'bg-green-100 text-green-700',
    osteopata: 'bg-amber-100 text-amber-700',
    collaboratore: 'bg-gray-100 text-gray-600',
};

const role = props.professional.roles?.[0]?.name ?? '';
</script>

<template>
    <AppLayout :title="`Profilo – ${professional.name}`">
        <template #header>
            <div class="flex items-center gap-3">
                <img :src="professional.profile_photo_url" :alt="professional.name" class="w-10 h-10 rounded-full object-cover" />
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">{{ professional.name }}</h1>
                    <span :class="[roleColor[role] ?? 'bg-gray-100 text-gray-600', 'text-xs px-2 py-0.5 rounded-full font-medium']">
                        {{ roleLabel[role] ?? role }}
                    </span>
                </div>
            </div>
        </template>

        <div class="max-w-3xl space-y-6">

            <!-- Success banner -->
            <div v-if="successMsg" class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                {{ successMsg }}
            </div>

            <!-- Profilo pubblico -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Profilo pubblico</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Titolo (es. Dott., Dott.ssa)</label>
                        <input v-model="form.title" type="text" placeholder="Dott.ssa"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Categoria / Specializzazione</label>
                        <input v-model="form.category" type="text" placeholder="es. Psicologo clinico"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Telefono</label>
                        <input v-model="form.phone" type="text" placeholder="+39 333 000 0000"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sito web</label>
                        <input v-model="form.website" type="text" placeholder="https://"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Bio</label>
                        <textarea v-model="form.bio" rows="3" placeholder="Breve presentazione professionale..."
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Curriculum / Formazione</label>
                        <textarea v-model="form.curriculum" rows="4" placeholder="Studi, specializzazioni, corsi..."
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                    </div>
                </div>
            </div>

            <!-- Dati fiscali -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Dati fiscali</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Partita IVA</label>
                        <input v-model="form.partita_iva" type="text" placeholder="IT00000000000"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Codice fiscale</label>
                        <input v-model="form.codice_fiscale" type="text" placeholder="RSSMRA85M01H501Z"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Regime fiscale</label>
                        <select v-model="form.regime_fiscale"
                            class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">— seleziona —</option>
                            <option value="forfettario">Forfettario</option>
                            <option value="ordinario">Ordinario</option>
                            <option value="minimo">Regime dei minimi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cassa previdenziale</label>
                        <input v-model="form.cassa_previdenziale" type="text" placeholder="es. ENPAP, ENPAM..."
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Albo professionale</label>
                        <input v-model="form.albo_professionale" type="text" placeholder="es. Ordine Psicologi Lombardia"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Numero iscrizione albo</label>
                        <input v-model="form.numero_albo" type="text" placeholder="es. 12345"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                </div>
            </div>

            <!-- Prenotazioni -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Impostazioni prenotazione</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <button
                            type="button"
                            @click="form.is_bookable = !form.is_bookable"
                            :class="form.is_bookable ? 'bg-purple-600' : 'bg-gray-200'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                        >
                            <span
                                :class="form.is_bookable ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                            />
                        </button>
                        <span class="text-sm text-gray-700">Prenotazione online attiva</span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Durata seduta (minuti)</label>
                        <input v-model.number="form.session_duration_minutes" type="number" min="15" step="5"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tariffa seduta (€)</label>
                        <input v-model="form.session_price" type="number" min="0" step="0.01" placeholder="0.00"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                </div>
            </div>

            <!-- Save button -->
            <div class="flex items-center justify-end gap-3 pb-8">
                <span v-if="form.errors && Object.keys(form.errors).length" class="text-xs text-red-500">
                    Correggi gli errori prima di salvare.
                </span>
                <button
                    @click="save"
                    :disabled="form.processing"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors"
                >
                    {{ form.processing ? 'Salvataggio…' : 'Salva modifiche' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
