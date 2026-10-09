<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient:       Object,
    tags:          Array,
    professionals: Array,
    authId:        Number,
});

const form = useForm({
    first_name:     props.patient.first_name,
    last_name:      props.patient.last_name,
    codice_fiscale: props.patient.codice_fiscale ?? '',
    date_of_birth:  props.patient.date_of_birth?.slice(0, 10) ?? '',
    gender:         props.patient.gender ?? '',
    email:          props.patient.email ?? '',
    phone:          props.patient.phone ?? '',
    address:        props.patient.address ?? '',
    city:           props.patient.city ?? '',
    cap:            props.patient.cap ?? '',
    medico_base:             props.patient.medico_base ?? '',
    emergency_contact_name:  props.patient.emergency_contact_name ?? '',
    emergency_contact_phone: props.patient.emergency_contact_phone ?? '',
    stato_civile:   props.patient.stato_civile ?? '',
    educazione:     props.patient.educazione ?? '',
    professione:    props.patient.professione ?? '',
    relatives:      (props.patient.relatives ?? []).map(r => ({
        name: r.name ?? '', relationship: r.relationship ?? '',
        phone: r.phone ?? '', email: r.email ?? '',
        can_access_data: !!r.can_access_data, is_legal_guardian: !!r.is_legal_guardian,
    })),
    notes:          props.patient.notes ?? '',
    diagnosis:      props.patient.diagnosis ?? '',
    tags:           props.patient.tags?.map(t => t.id) ?? [],
    professionals:  props.patient.professionals?.map(p => p.id) ?? [],
});

const statoCivileOptions = ['Celibe/Nubile', 'Coniugato/a', 'Convivente', 'Separato/a', 'Divorziato/a', 'Vedovo/a'];
const educazioneOptions = ['Licenza elementare', 'Licenza media', 'Diploma di scuola superiore', 'Laurea triennale', 'Laurea magistrale', 'Dottorato / Post-laurea', 'Altro'];

const addRelative = () => form.relatives.push({
    name: '', relationship: '', phone: '', email: '',
    can_access_data: false, is_legal_guardian: false,
});
const removeRelative = (idx) => form.relatives.splice(idx, 1);

function submit() {
    form.put(route('patients.update', props.patient.id));
}

function toggleTag(id) {
    const idx = form.tags.indexOf(id);
    if (idx === -1) form.tags.push(id);
    else form.tags.splice(idx, 1);
}

function toggleProfessional(id) {
    const idx = form.professionals.indexOf(id);
    if (idx === -1) form.professionals.push(id);
    else form.professionals.splice(idx, 1);
}

// Professionals selectable = everyone except the creator
const creatorId = props.patient.created_by;
const otherProfessionals = props.professionals.filter(p => p.id !== creatorId);
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
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Medico di base</label>
                        <input v-model="form.medico_base" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
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
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Contatto emergenza (nome)</label>
                        <input v-model="form.emergency_contact_name" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Contatto emergenza (tel)</label>
                        <input v-model="form.emergency_contact_phone" type="tel"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                </div>
            </div>

            <!-- Parenti / Tutori legali -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Parenti / Tutori legali</h2>
                    <button type="button" @click="addRelative" class="flex items-center gap-1 text-sm text-purple-600 hover:text-purple-700 font-medium normal-case">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Aggiungi un nominativo
                    </button>
                </div>
                <p class="text-xs text-gray-400 mb-4">I familiari possono essere selezionati come persone autorizzate o tutori legali di questo paziente.</p>

                <p v-if="!form.relatives.length" class="text-sm text-gray-400">Nessun nominativo aggiunto.</p>

                <div v-for="(rel, idx) in form.relatives" :key="idx" class="border border-gray-200 rounded-xl p-4 mb-3 relative">
                    <button type="button" @click="removeRelative(idx)" class="absolute top-3 right-3 text-gray-300 hover:text-red-500" title="Rimuovi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nome e cognome</label>
                            <input v-model="rel.name" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Relazione / Parentela</label>
                            <input v-model="rel.relationship" type="text" placeholder="Es. Genitore, Coniuge, Tutore…" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Telefono</label>
                            <input v-model="rel.phone" type="tel" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                            <input v-model="rel.email" type="email" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                    </div>
                    <div class="mt-3 space-y-2">
                        <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                            <input v-model="rel.can_access_data" type="checkbox" class="mt-0.5 rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                            <span>Il paziente autorizza questa persona ad accedere ai suoi dati medici</span>
                        </label>
                        <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                            <input v-model="rel.is_legal_guardian" type="checkbox" class="mt-0.5 rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                            <span>Tutore legale (il paziente è un minore o non ha capacità giuridica)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Dati aggiuntivi -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Dati aggiuntivi</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Stato civile</label>
                        <select v-model="form.stato_civile" class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">Seleziona un'opzione</option>
                            <option v-for="opt in statoCivileOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Educazione</label>
                        <select v-model="form.educazione" class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">Seleziona un'opzione</option>
                            <option v-for="opt in educazioneOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Professione</label>
                        <input v-model="form.professione" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                </div>
            </div>

            <!-- Accesso professionisti -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Accesso professionisti</h2>
                <p class="text-xs text-gray-400 mb-4">Solo i professionisti selezionati possono vedere e gestire questo paziente.</p>

                <!-- Creator badge -->
                <div class="flex flex-wrap gap-2">
                    <span v-if="patient.creator" class="flex items-center gap-1.5 text-xs font-medium bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        {{ patient.creator.name }}
                        <span class="text-purple-400 font-normal">(creatore)</span>
                    </span>

                    <button
                        v-for="pro in otherProfessionals"
                        :key="pro.id"
                        type="button"
                        @click="toggleProfessional(pro.id)"
                        :class="form.professionals.includes(pro.id)
                            ? 'bg-purple-600 text-white border-purple-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-purple-300'"
                        class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-full border font-medium transition-all"
                    >
                        <span v-if="form.professionals.includes(pro.id)" class="text-xs">✓</span>
                        {{ pro.name }}
                    </button>

                    <p v-if="!otherProfessionals.length" class="text-sm text-gray-400">Nessun altro professionista disponibile.</p>
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

            <!-- Diagnosi -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Diagnosi</h2>
                <p class="text-xs text-gray-400 mb-3">Verrà mostrata come etichetta sul profilo del paziente.</p>
                <input v-model="form.diagnosis" type="text" maxlength="500"
                    placeholder="Es. Disturbo d'ansia generalizzato, Obesità di I grado…"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
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
