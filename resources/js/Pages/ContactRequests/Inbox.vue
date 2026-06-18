<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';

const props = defineProps({
    requests: Array,
});

const HOW_FOUND_LABELS = {
    passaparola: 'Passaparola / Biglietti',
    social:      'Social',
    miodottore:  'MioDottore',
};
const CONTACT_LABELS = {
    whatsapp: 'WhatsApp',
    telefono: 'Telefono',
};
const DAY_LABELS = { lun: 'Lun', mar: 'Mar', mer: 'Mer', gio: 'Gio', ven: 'Ven', sab: 'Sab' };
const DAY_ISO    = { lun: 1, mar: 2, mer: 3, gio: 4, ven: 5, sab: 6 };

const STATUS = {
    pending:  { label: 'Da gestire', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
    accepted: { label: 'Accettata',  cls: 'bg-green-50 text-green-700 border-green-200' },
    rejected: { label: 'Rifiutata',  cls: 'bg-gray-100 text-gray-500 border-gray-200' },
};

function labelHowFound(c) {
    return (c.how_found || []).map(v => HOW_FOUND_LABELS[v] ?? v).join(', ') || '—';
}
function labelContact(c) {
    return (c.contact_method || []).map(v => CONTACT_LABELS[v] ?? v).join(', ') || '—';
}
function availabilityByDay(c) {
    const map = {};
    (c.availability || []).forEach(slot => {
        const [d, h] = slot.split('|');
        (map[d] ??= []).push(h);
    });
    return Object.entries(map).map(([d, hours]) => ({
        day: DAY_LABELS[d] ?? d,
        hours: hours.sort().join(', '),
    }));
}

function reject(c) {
    router.post(route('contact-requests.reject', c.id), {}, { preserveScroll: true });
}

// ── Accept modal ──────────────────────────────────────────────────────────────
const acceptingFor = ref(null); // the request being accepted
const acceptForm = useForm({ date: '', time: '' });

const timeOptions = computed(() => {
    const c = acceptingFor.value;
    if (!c) return [];
    if (!acceptForm.date || !(c.availability || []).length) {
        return Array.from({ length: 12 }, (_, i) => String(8 + i).padStart(2, '0') + ':00');
    }
    // Restrict to hours the client marked for that weekday
    const iso = new Date(acceptForm.date + 'T00:00:00').getDay(); // 0=Sun..6=Sat
    const isoMon = iso === 0 ? 7 : iso;
    const dayCode = Object.keys(DAY_ISO).find(k => DAY_ISO[k] === isoMon);
    return (c.availability || [])
        .filter(s => s.startsWith(dayCode + '|'))
        .map(s => s.split('|')[1])
        .sort();
});

function openAccept(c) {
    acceptingFor.value = c;
    acceptForm.reset();
    acceptForm.clearErrors();
}
function submitAccept() {
    acceptForm.post(route('contact-requests.accept', acceptingFor.value.id), {
        preserveScroll: true,
        onSuccess: () => { acceptingFor.value = null; },
    });
}
</script>

<template>
    <AppLayout title="Richieste">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Richieste di primo contatto</h1>
        </template>

        <div class="max-w-3xl space-y-4">
            <p v-if="!requests.length" class="text-gray-400 text-sm py-10 text-center">
                Nessuna richiesta al momento.
            </p>

            <div v-for="c in requests" :key="c.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-gray-800">{{ c.surname }} {{ c.name }}</h2>
                        <p class="text-sm text-gray-500">{{ c.email }}<span v-if="c.phone"> · {{ c.phone }}</span></p>
                    </div>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full border whitespace-nowrap"
                        :class="STATUS[c.status]?.cls">{{ STATUS[c.status]?.label ?? c.status }}</span>
                </div>

                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1 mt-3 text-sm">
                    <p><span class="text-gray-400">Come ci ha trovato:</span> {{ labelHowFound(c) }}</p>
                    <p><span class="text-gray-400">Ricontatto preferito:</span> {{ labelContact(c) }}</p>
                </div>

                <div class="mt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Disponibilità</p>
                    <div v-if="availabilityByDay(c).length" class="flex flex-wrap gap-1.5">
                        <span v-for="row in availabilityByDay(c)" :key="row.day"
                            class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                            <strong>{{ row.day }}</strong> {{ row.hours }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-400">Nessuna fascia indicata</p>
                </div>

                <p v-if="c.notes" class="mt-3 text-sm text-gray-600 bg-gray-50 rounded-xl px-3 py-2">{{ c.notes }}</p>

                <p class="mt-3 text-xs text-gray-400">Ricevuta {{ c.created_at }}</p>

                <!-- Actions -->
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4"
                    v-if="c.status === 'pending'">
                    <button @click="openAccept(c)"
                        class="text-sm font-semibold text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-xl transition-colors">
                        Accetta e fissa
                    </button>
                    <button @click="reject(c)"
                        class="text-sm font-medium text-gray-600 hover:text-red-600 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Rifiuta
                    </button>
                </div>

                <Link v-else-if="c.status === 'accepted' && c.patient_id" :href="route('patients.show', c.patient_id)"
                    class="mt-4 inline-block text-sm font-medium text-purple-600 hover:text-purple-700">
                    Apri scheda paziente →
                </Link>
            </div>
        </div>

        <!-- Accept modal -->
        <div v-if="acceptingFor" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
            @click.self="acceptingFor = null">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Fissa il primo colloquio</h3>
                <p class="text-sm text-gray-500 mb-4">{{ acceptingFor.surname }} {{ acceptingFor.name }}</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Data</label>
                        <input v-model="acceptForm.date" type="date"
                            class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                        <p v-if="acceptForm.errors.date" class="text-xs text-red-500 mt-1">{{ acceptForm.errors.date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Orario</label>
                        <select v-model="acceptForm.time"
                            class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400">
                            <option value="" disabled>Seleziona…</option>
                            <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
                        </select>
                        <p v-if="acceptForm.errors.time" class="text-xs text-red-500 mt-1">{{ acceptForm.errors.time }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="acceptingFor = null"
                        class="text-sm font-medium text-gray-600 px-4 py-2 rounded-xl hover:bg-gray-100">Annulla</button>
                    <button @click="submitAccept" :disabled="acceptForm.processing || !acceptForm.date || !acceptForm.time"
                        class="text-sm font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-60 px-4 py-2 rounded-xl">
                        Conferma appuntamento
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
