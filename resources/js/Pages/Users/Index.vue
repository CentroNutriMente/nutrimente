<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    roles: Array,
    showPatientLink: { type: Boolean, default: false },
    patientsWithoutAccount: { type: Array, default: () => [] },
});

const showOrphans = ref(false);

// "Elimina" archivia la cartella: i dati restano, escono dall'elenco pazienti.
const archivePatient = (p) => {
    if (confirm(`Eliminare la cartella di ${p.name}?\n\nI dati non vengono cancellati: la cartella finisce nell'archivio, da cui puoi consultarla e ripristinarla.`)) {
        router.delete(route('patients.destroy', p.id), { preserveScroll: true });
    }
};

const roleLabel = {
    admin: 'Amministratore (clinico)',
    system_admin: 'Amministratore di sistema',
    psicologo: 'Psicologo',
    nutrizionista: 'Nutrizionista',
    osteopata: 'Osteopata',
    collaboratore: 'Collaboratore',
    patient: 'Paziente',
};

const showForm = ref(false);
const form = useForm({ name: '', email: '', password: '', role: 'psicologo' });

const submit = () => form.post(route('utenti.store'), {
    preserveScroll: true,
    onSuccess: () => { form.reset(); showForm.value = false; },
});

const erase = (u) => {
    if (confirm(`Eliminare l'account di ${u.name}?\n\nL'operazione anonimizza i dati (diritto all'oblio) e revoca l'accesso. Non è reversibile.`)) {
        router.delete(route('utenti.erase', u.id), { preserveScroll: true });
    }
};

const inputCls = 'w-full px-4 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink focus:border-sage focus:ring-sageLight';
</script>

<template>
    <AppLayout title="Gestione utenti">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-serif text-2xl text-ink">Gestione utenti</h1>
                    <p class="text-sm text-inkSoft mt-1">Creazione ed eliminazione degli account del sistema.</p>
                </div>
                <button @click="showForm = !showForm" class="px-4 py-2 rounded-ctrl bg-sage hover:bg-sageDeep text-white text-sm font-medium shadow-btn">
                    + Nuovo utente
                </button>
            </div>
        </template>

        <!-- Form nuovo utente -->
        <Card v-if="showForm" class="mb-5">
            <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-inkSoft mb-1">Nome e cognome</label>
                    <input v-model="form.name" type="text" :class="inputCls" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-inkSoft mb-1">Email</label>
                    <input v-model="form.email" type="email" :class="inputCls" />
                    <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-inkSoft mb-1">Ruolo</label>
                    <select v-model="form.role" :class="inputCls">
                        <option v-for="r in roles" :key="r" :value="r">{{ roleLabel[r] || r }}</option>
                    </select>
                    <p v-if="form.errors.role" class="text-xs text-red-500 mt-1">{{ form.errors.role }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-inkSoft mb-1">Password</label>
                    <input v-model="form.password" type="text" autocomplete="new-password" :class="inputCls" placeholder="Min 12: maiuscole, minuscole, numeri, simboli" />
                    <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 rounded-ctrl border border-line text-sm text-inkSoft hover:bg-cream">Annulla</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-ctrl bg-sage hover:bg-sageDeep text-white text-sm font-medium disabled:opacity-60">Crea utente</button>
                </div>
            </form>
        </Card>

        <!-- Elenco utenti -->
        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-inkSoft border-b border-line">
                            <th class="font-medium px-5 py-3">Nome</th>
                            <th class="font-medium px-5 py-3">Email</th>
                            <th class="font-medium px-5 py-3">Ruoli</th>
                            <th v-if="showPatientLink" class="font-medium px-5 py-3">Cartella clinica</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-cream" :class="u.is_erased ? 'opacity-50' : ''">
                            <td class="px-5 py-3 font-medium text-ink">{{ u.name }}</td>
                            <td class="px-5 py-3 text-inkSoft">{{ u.email }}</td>
                            <td class="px-5 py-3">
                                <span v-for="r in u.roles" :key="r" class="inline-block mr-1 mb-1 px-2.5 py-0.5 rounded-full text-xs bg-lavenderLight text-lavender">{{ roleLabel[r] || r }}</span>
                                <span v-if="!u.roles.length" class="text-inkSoft text-xs">—</span>
                            </td>
                            <td v-if="showPatientLink" class="px-5 py-3">
                                <template v-if="u.patient">
                                    <Link :href="route('patients.show', u.patient.id)" class="text-ink hover:text-sage hover:underline">{{ u.patient.name }}</Link>
                                    <span v-if="u.patient.archived" class="ml-2 px-2 py-0.5 rounded-full text-[11px] bg-blush text-blushDeep">archiviata</span>
                                </template>
                                <span v-else class="text-inkSoft text-xs">nessuna</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button v-if="!u.is_erased" @click="erase(u)" class="text-xs px-3 py-1.5 rounded-full border border-line text-inkSoft hover:bg-red-50 hover:text-red-500">
                                    Elimina
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Scostamento inverso: cartelle con email ma senza account portale -->
        <Card v-if="showPatientLink" :padded="false" class="mt-5">
            <button @click="showOrphans = !showOrphans" class="w-full flex items-center gap-3 px-5 py-4 text-left">
                <svg class="w-4 h-4 text-inkMuted transition-transform" :class="showOrphans ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
                <div>
                    <h2 class="font-serif text-lg text-ink">Pazienti senza account portale</h2>
                    <p class="text-xs text-inkSoft mt-0.5">Cartelle con email che non corrispondono a nessun utente: sono in «Pazienti» ma non qui.</p>
                </div>
                <span class="ml-auto text-sm font-medium text-ink">{{ patientsWithoutAccount.length }}</span>
            </button>
            <div v-show="showOrphans" class="border-t border-line divide-y divide-line">
                <div v-for="p in patientsWithoutAccount" :key="p.id" class="flex items-center justify-between gap-4 px-5 py-3">
                    <Link :href="route('patients.show', p.id)" class="text-sm font-medium text-ink hover:text-sage hover:underline">{{ p.name }}</Link>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-inkSoft">{{ p.email }}</span>
                        <button @click="archivePatient(p)" title="Sposta la cartella nell'archivio (recuperabile)"
                                class="text-xs px-3 py-1.5 rounded-full border border-line text-inkSoft hover:bg-red-50 hover:text-red-500">
                            Elimina
                        </button>
                    </div>
                </div>
                <div v-if="patientsWithoutAccount.length === 0" class="px-5 py-8 text-center text-sm text-inkSoft">
                    Ogni cartella con email ha il suo account.
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
