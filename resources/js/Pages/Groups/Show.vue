<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import IconCircle from '@/Components/ui/IconCircle.vue';
import PillBadge from '@/Components/ui/PillBadge.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    group: Object,
    participants: Array,
    requests: Array,
    patientsOptions: Array,
    categories: Array,
    leaders: Array,
    cadences: Array,
    modalities: Array,
    statuses: Array,
    participantStatuses: Array,
});

const leafIcon = 'M12 3c1.5 2 4 3.5 4 7a4 4 0 1 1-8 0c0-3.5 2.5-5 4-7Z';
const tab = ref('partecipanti');
const tabs = [
    { key: 'partecipanti', label: 'Partecipanti' },
    { key: 'incontri', label: 'Incontri' },
    { key: 'materiali', label: 'Materiali' },
    { key: 'impostazioni', label: 'Impostazioni' },
];

const statusLabel = { attivo: 'Attivo', in_partenza: 'In partenza', concluso: 'Concluso' };
const pStatusTone = { confermata: 'sage', in_attesa: 'peach', pagato: 'lavender' };
const pStatusLabel = { confermata: 'Confermata', in_attesa: 'In attesa', pagato: 'Pagato' };

const fmtDate = (iso) => iso ? new Date(iso).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';

// ── Filtro partecipanti ──
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.participants;
    return props.participants.filter(p => (p.name + ' ' + (p.email || '')).toLowerCase().includes(q));
});

// ── Aggiungi partecipante ──
const showAdd = ref(false);
const mode = ref('patient'); // patient | external
const addForm = useForm({ patient_id: null, name: '', email: '', phone: '', status: 'in_attesa' });
const submitAdd = () => {
    if (mode.value === 'patient') { addForm.name = ''; addForm.email = ''; addForm.phone = ''; }
    else addForm.patient_id = null;
    addForm.post(route('groups.participants.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => { addForm.reset(); showAdd.value = false; },
    });
};
const updateStatus = (p, status) => router.put(route('groups.participants.update', [props.group.id, p.id]), { status }, { preserveScroll: true });
const removeParticipant = (p) => { if (confirm(`Rimuovere ${p.name}?`)) router.delete(route('groups.participants.destroy', [props.group.id, p.id]), { preserveScroll: true }); };

// ── Richieste ──
const approve = (r) => router.post(route('groups.enrollments.approve', [props.group.id, r.id]), {}, { preserveScroll: true });
const reject = (r) => router.post(route('groups.enrollments.reject', [props.group.id, r.id]), {}, { preserveScroll: true });

// ── Impostazioni (modifica) ──
const settings = useForm({
    category: props.group.category,
    name: props.group.name,
    description: props.group.description,
    leader_user_id: props.group.leader_user_id,
    cadence: props.group.cadence,
    modality: props.group.modality,
    location: props.group.location,
    capacity: props.group.capacity,
    next_meeting_at: props.group.next_meeting_at ? props.group.next_meeting_at.substring(0, 16) : '',
    status: props.group.status,
});
const saveSettings = () => settings.put(route('groups.update', props.group.id), { preserveScroll: true });
const destroyGroup = () => { if (confirm('Eliminare definitivamente questo gruppo?')) router.delete(route('groups.destroy', props.group.id)); };

const emailAll = () => {
    const emails = props.participants.map(p => p.email).filter(Boolean).join(',');
    if (emails) window.location.href = `mailto:?bcc=${emails}`;
};

const inputCls = 'w-full px-4 py-2.5 rounded-2xl border border-line bg-white text-sm text-ink focus:border-sage focus:ring-sage/30';
</script>

<template>
    <AppLayout :title="group.name">
        <Link :href="route('groups.index')" class="inline-flex items-center gap-1 text-sm text-inkSoft hover:text-ink mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Torna ai gruppi
        </Link>

        <!-- Header gruppo -->
        <Card class="mb-5">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-full flex items-center justify-center shrink-0"
                     :class="group.tone === 'sage' ? 'bg-sageLight' : group.tone === 'lavender' ? 'bg-lavenderLight' : 'bg-blush'">
                    <IconCircle :icon="leafIcon" :tone="group.tone" size="lg" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="font-serif text-2xl text-ink">{{ group.name }}</h1>
                        <PillBadge :tone="group.tone">{{ statusLabel[group.status] }}</PillBadge>
                    </div>
                    <p class="text-sm text-inkSoft mt-1.5 leading-relaxed">{{ group.description }}</p>
                </div>
                <button @click="tab = 'impostazioni'" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl border border-line text-sm text-ink hover:bg-cream transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586Z"/></svg>
                    Modifica
                </button>
            </div>

            <!-- Meta -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5 pt-5 border-t border-line">
                <div><div class="text-xs text-inkSoft">Conduttore</div><div class="text-sm font-medium text-ink mt-0.5">{{ group.leader || '—' }}</div></div>
                <div><div class="text-xs text-inkSoft">Periodicità</div><div class="text-sm font-medium text-ink mt-0.5 capitalize">{{ group.cadence || '—' }}</div></div>
                <div><div class="text-xs text-inkSoft">Prossimo incontro</div><div class="text-sm font-medium text-ink mt-0.5">{{ fmtDate(group.next_meeting_at) }}</div></div>
                <div><div class="text-xs text-inkSoft">Modalità</div><div class="text-sm font-medium text-ink mt-0.5">{{ group.modality === 'online' ? 'Online' : 'In presenza' }}</div></div>
                <div><div class="text-xs text-inkSoft">Posti disponibili</div><div class="text-sm font-medium text-ink mt-0.5">{{ Math.max(0, group.capacity - group.enrolled) }} / {{ group.capacity }}</div></div>
            </div>
        </Card>

        <!-- Tabs -->
        <div class="flex items-center gap-6 border-b border-line mb-5 px-1">
            <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
                :class="['pb-3 -mb-px text-sm font-medium border-b-2 transition-colors', tab === t.key ? 'border-sage text-sage' : 'border-transparent text-inkSoft hover:text-ink']">
                {{ t.label }}
            </button>
        </div>

        <!-- Tab: Partecipanti -->
        <div v-show="tab === 'partecipanti'" class="space-y-5">
            <Card :padded="false">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5">
                    <div class="relative flex-1 max-w-xs">
                        <input v-model="search" type="text" placeholder="Cerca partecipante..." :class="inputCls" />
                    </div>
                    <button @click="showAdd = !showAdd" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-sage hover:bg-sage/90 text-white text-sm font-medium transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                        Aggiungi partecipante
                    </button>
                </div>

                <!-- Form aggiungi -->
                <div v-if="showAdd" class="px-5 pb-5">
                    <div class="rounded-2xl bg-cream border border-line p-4 space-y-3">
                        <div class="flex gap-2">
                            <button @click="mode = 'patient'" :class="['px-3 py-1.5 rounded-full text-xs border', mode === 'patient' ? 'bg-sage text-white border-sage' : 'bg-white text-inkSoft border-line']">Paziente esistente</button>
                            <button @click="mode = 'external'" :class="['px-3 py-1.5 rounded-full text-xs border', mode === 'external' ? 'bg-sage text-white border-sage' : 'bg-white text-inkSoft border-line']">Contatto esterno</button>
                        </div>
                        <div v-if="mode === 'patient'">
                            <select v-model="addForm.patient_id" :class="inputCls">
                                <option :value="null">Seleziona un paziente…</option>
                                <option v-for="p in patientsOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <input v-model="addForm.name" type="text" placeholder="Nome e cognome" :class="inputCls" />
                            <input v-model="addForm.email" type="email" placeholder="Email" :class="inputCls" />
                            <input v-model="addForm.phone" type="text" placeholder="Telefono" :class="inputCls" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <select v-model="addForm.status" :class="inputCls + ' max-w-[180px]'">
                                <option v-for="s in participantStatuses" :key="s" :value="s">{{ pStatusLabel[s] }}</option>
                            </select>
                            <button @click="submitAdd" :disabled="addForm.processing" class="px-4 py-2.5 rounded-2xl bg-sage hover:bg-sage/90 text-white text-sm font-medium disabled:opacity-60">Aggiungi</button>
                        </div>
                    </div>
                </div>

                <!-- Tabella -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-inkSoft border-y border-line">
                                <th class="font-medium px-5 py-3">Nome</th>
                                <th class="font-medium px-5 py-3">Email</th>
                                <th class="font-medium px-5 py-3">Telefono</th>
                                <th class="font-medium px-5 py-3">Stato</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="p in filtered" :key="p.id" class="hover:bg-cream">
                                <td class="px-5 py-3 font-medium text-ink">
                                    {{ p.name }}
                                    <span v-if="p.is_patient" class="ml-1 text-[10px] text-sage">• paziente</span>
                                </td>
                                <td class="px-5 py-3 text-inkSoft">{{ p.email || '—' }}</td>
                                <td class="px-5 py-3 text-inkSoft">{{ p.phone || '—' }}</td>
                                <td class="px-5 py-3">
                                    <select :value="p.status" @change="updateStatus(p, $event.target.value)"
                                        class="text-xs rounded-full border-line py-1 pl-2 pr-7 focus:border-sage focus:ring-sage/30"
                                        :class="pStatusTone[p.status] === 'sage' ? 'text-sage bg-sageLight' : pStatusTone[p.status] === 'peach' ? 'text-blushDeep bg-blush' : 'text-lavender bg-lavenderLight'">
                                        <option v-for="s in participantStatuses" :key="s" :value="s">{{ pStatusLabel[s] }}</option>
                                    </select>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button @click="removeParticipant(p)" class="text-inkSoft hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-7 0h8l-1 12a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1L7 7Z"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0"><td colspan="5" class="px-5 py-8 text-center text-inkSoft">Nessun partecipante.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-3 p-5 border-t border-line">
                    <button @click="emailAll" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-line text-sm text-ink hover:bg-cream transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/></svg>
                        Invia email a tutti
                    </button>
                    <a :href="route('groups.export', group.id)" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-line text-sm text-ink hover:bg-cream transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                        Esporta elenco
                    </a>
                </div>
            </Card>

            <!-- Richieste di iscrizione -->
            <Card :padded="false">
                <div class="px-5 pt-5 pb-3">
                    <h3 class="font-serif text-lg text-ink">Richieste di iscrizione</h3>
                    <p class="text-xs text-inkSoft mt-0.5">Nuove richieste ricevute dai moduli online.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-inkSoft border-y border-line">
                                <th class="font-medium px-5 py-3">Nome</th>
                                <th class="font-medium px-5 py-3">Email</th>
                                <th class="font-medium px-5 py-3">Data</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="r in requests" :key="r.id" class="hover:bg-cream">
                                <td class="px-5 py-3 font-medium text-ink">{{ r.name }}</td>
                                <td class="px-5 py-3 text-inkSoft">{{ r.email || '—' }}</td>
                                <td class="px-5 py-3 text-inkSoft">{{ r.date }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <button @click="approve(r)" class="text-xs px-3 py-1.5 rounded-full bg-sage hover:bg-sage/90 text-white mr-2">Approva</button>
                                    <button @click="reject(r)" class="text-xs px-3 py-1.5 rounded-full border border-line text-inkSoft hover:bg-cream">Rifiuta</button>
                                </td>
                            </tr>
                            <tr v-if="requests.length === 0"><td colspan="4" class="px-5 py-8 text-center text-inkSoft">Nessuna richiesta in sospeso.</td></tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Tab: Incontri / Materiali (placeholder) -->
        <Card v-show="tab === 'incontri'" class="text-center py-16 text-inkSoft">Pianificazione incontri in arrivo.</Card>
        <Card v-show="tab === 'materiali'" class="text-center py-16 text-inkSoft">Materiali condivisi in arrivo.</Card>

        <!-- Tab: Impostazioni -->
        <div v-show="tab === 'impostazioni'" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <Card class="lg:col-span-2 space-y-4">
                <h3 class="font-serif text-lg text-ink">Impostazioni gruppo</h3>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Nome</label>
                    <input v-model="settings.name" type="text" :class="inputCls" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Descrizione</label>
                    <textarea v-model="settings.description" rows="3" :class="inputCls"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Categoria</label>
                        <select v-model="settings.category" :class="inputCls"><option v-for="c in categories" :key="c.key" :value="c.key">{{ c.label }}</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Stato</label>
                        <select v-model="settings.status" :class="inputCls"><option v-for="s in statuses" :key="s" :value="s">{{ statusLabel[s] }}</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Conduttore</label>
                        <select v-model="settings.leader_user_id" :class="inputCls"><option :value="null">—</option><option v-for="l in leaders" :key="l.id" :value="l.id">{{ l.name }}</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Periodicità</label>
                        <select v-model="settings.cadence" :class="inputCls"><option v-for="c in cadences" :key="c" :value="c">{{ c.charAt(0).toUpperCase() + c.slice(1) }}</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Modalità</label>
                        <select v-model="settings.modality" :class="inputCls"><option v-for="m in modalities" :key="m" :value="m">{{ m === 'presenza' ? 'In presenza' : 'Online' }}</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Posti totali</label>
                        <input v-model="settings.capacity" type="number" min="1" max="200" :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Prossimo incontro</label>
                        <input v-model="settings.next_meeting_at" type="datetime-local" :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Luogo</label>
                        <input v-model="settings.location" type="text" :class="inputCls" />
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button @click="destroyGroup" class="text-sm text-red-500 hover:underline">Elimina gruppo</button>
                    <button @click="saveSettings" :disabled="settings.processing" class="px-5 py-2.5 rounded-2xl bg-sage hover:bg-sage/90 text-white text-sm font-medium disabled:opacity-60">Salva</button>
                </div>
            </Card>

            <!-- Volantino / QR -->
            <Card class="text-center">
                <h3 class="font-serif text-lg text-ink mb-2">Volantino & QR</h3>
                <p class="text-xs text-inkSoft mb-4">Le persone possono iscriversi inquadrando il QR del volantino.</p>
                <img :src="route('groups.flyer', { group: group.id, preview: 1 })" alt="QR iscrizione"
                     class="w-40 h-40 mx-auto rounded-2xl border border-line object-contain bg-white" @error="$event.target.style.display='none'" />
                <div class="mt-4 text-xs text-inkSoft break-all bg-cream rounded-xl px-3 py-2">{{ group.public_url }}</div>
                <a :href="route('groups.flyer', group.id)" target="_blank"
                   class="inline-flex items-center gap-2 mt-4 px-4 py-2.5 rounded-2xl bg-sage hover:bg-sage/90 text-white text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                    Scarica volantino
                </a>
            </Card>
        </div>
    </AppLayout>
</template>
