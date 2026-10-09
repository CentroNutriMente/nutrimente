<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import BotanicalSprig from '@/Components/ui/BotanicalSprig.vue';
import BotanicalSprite from '@/Components/ui/BotanicalSprite.vue';
import { computed } from 'vue';

const props = defineProps({
    groups: Array,
    preselect: Object,
    source: String,
});

const page = usePage();
const flash = computed(() => page.props.flash?.banner);

const HOW_HEARD = [
    'Passaparola', 'Social', 'MioDottore', 'Sito web', 'Volantino / QR', 'Altro',
];

const form = useForm({
    nome: '',
    cognome: '',
    email: '',
    phone: '',
    codice_fiscale: '',
    group_id: props.preselect?.id ?? null,
    how_heard: '',
    privacy_consent: false,
    source: props.source ?? 'form',
});

// Gruppo scelto nella select (o pre-selezionato dal QR): mostriamo date e
// condizioni sotto al campo, così si capisce subito se si può partecipare.
const selectedGroup = computed(() => props.groups.find((g) => g.id === form.group_id) ?? null);

const STATUS_LABEL = { attivo: 'In corso', in_partenza: 'In partenza', concluso: 'Concluso' };

const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

const fmtDay = (iso) => new Date(iso).toLocaleDateString('it-IT', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
});

const fmtTime = (d) => d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

const fmtSlot = (iso, minutes) => {
    const start = new Date(iso);
    if (!minutes) return fmtTime(start);
    return `${fmtTime(start)}–${fmtTime(new Date(start.getTime() + minutes * 60000))}`;
};

const submit = () => {
    form.transform((d) => ({
        name: `${d.nome} ${d.cognome}`.trim(),
        email: d.email,
        phone: d.phone,
        codice_fiscale: d.codice_fiscale,
        group_id: d.group_id,
        how_heard: d.how_heard,
        privacy_consent: d.privacy_consent,
        source: d.source,
    })).post(route('groups.public.store'), {
        onSuccess: () => { form.reset(); window.scrollTo({ top: 0, behavior: 'smooth' }); },
    });
};

const inputCls = 'w-full px-4 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink placeholder-inkSoft focus:border-sage focus:ring-sageLight';
</script>

<template>
    <div class="min-h-screen bg-cream">
        <BotanicalSprite />
        <!-- Top nav -->
        <nav class="bg-cream backdrop-blur border-b border-line sticky top-0 z-10">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 text-ink">
                    <BotanicalSprig id="bot-sprout" klass="w-6 h-8 text-sage" />
                    <span class="font-serif text-xl lowercase">nutrimente</span>
                </a>
                <a href="/login" class="text-sm font-medium text-white bg-sage hover:bg-sageDeep px-4 py-1.5 rounded-ctrl transition-colors">Accesso professionisti</a>
            </div>
        </nav>

        <!-- Hero -->
        <header class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <span class="wash wash--lavender" style="top:-70px;left:28%;width:360px;height:280px;"></span>
                <span class="wash wash--blush" style="top:-50px;right:22%;width:320px;height:260px;"></span>
            </div>
            <div class="max-w-2xl mx-auto px-4 pt-10 pb-6 text-center">
                <a href="/" class="self-start text-sm text-inkSoft hover:text-ink inline-flex items-center gap-1 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Torna alla home
                </a>
                <h1 class="font-serif text-4xl text-ink">Nuova iscrizione a un gruppo</h1>
                <p class="text-inkSoft mt-3 max-w-md mx-auto">Compila i dati per richiedere l'iscrizione. Verrai ricontattato a breve.</p>
                <p v-if="preselect" class="mt-3 inline-block text-sm text-sage bg-sageLight px-3 py-1.5 rounded-full">Gruppo: {{ preselect.name }}</p>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 pb-16">
            <div v-if="flash" class="mb-5 rounded-ctrl bg-sageLight border border-line text-sage px-4 py-3 text-sm text-center">{{ flash }}</div>

            <form @submit.prevent="submit" class="bg-white rounded-xl2 border border-line p-6 sm:p-8 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Nome</label>
                        <input v-model="form.nome" type="text" :class="inputCls" placeholder="Nome" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Cognome</label>
                        <input v-model="form.cognome" type="text" :class="inputCls" placeholder="Cognome" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Email</label>
                        <input v-model="form.email" type="email" :class="inputCls" placeholder="email@esempio.it" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Telefono</label>
                        <input v-model="form.phone" type="text" :class="inputCls" placeholder="333 1234567" />
                        <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-ink mb-1.5">Codice fiscale</label>
                        <input v-model="form.codice_fiscale" type="text" maxlength="16"
                               :class="[inputCls, 'uppercase']" placeholder="RSSMRA80A01H501U" />
                        <p v-if="form.errors.codice_fiscale" class="text-xs text-red-500 mt-1">{{ form.errors.codice_fiscale }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Gruppo di interesse</label>
                    <select v-model="form.group_id" :class="inputCls">
                        <option :value="null">Seleziona un gruppo…</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}{{ g.edition ? ' — ' + g.edition : '' }}</option>
                    </select>
                    <p v-if="form.errors.group_id" class="text-xs text-red-500 mt-1">{{ form.errors.group_id }}</p>

                    <!-- Scheda informativa del gruppo scelto -->
                    <div v-if="selectedGroup" class="mt-4 rounded-xl2 border border-line bg-cream p-5">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <h3 class="font-serif text-lg text-ink">{{ selectedGroup.name }}</h3>
                                <p v-if="selectedGroup.edition" class="text-sm text-inkMuted italic">{{ selectedGroup.edition }}</p>
                            </div>
                            <span class="text-xs font-medium text-sage bg-sageLight px-3 py-1 rounded-full shrink-0">
                                {{ STATUS_LABEL[selectedGroup.status] ?? selectedGroup.status }}
                            </span>
                        </div>

                        <p v-if="selectedGroup.description" class="text-sm text-inkSoft mt-2 leading-relaxed">{{ selectedGroup.description }}</p>

                        <!-- Quota di partecipazione -->
                        <div v-if="selectedGroup.price_label" class="mt-4 flex items-center gap-2 rounded-ctrl bg-white border border-line px-3.5 py-2.5">
                            <svg class="w-5 h-5 text-sage shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <span class="text-sm text-ink"><span class="font-medium">Quota:</span> {{ selectedGroup.price_label }}</span>
                        </div>

                        <!-- Info pratiche -->
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 mt-4">
                            <div v-if="selectedGroup.cadence">
                                <dt class="text-xs text-inkSoft">Periodicità</dt>
                                <dd class="text-sm font-medium text-ink mt-0.5">{{ capitalize(selectedGroup.cadence) }}</dd>
                            </div>
                            <div v-if="selectedGroup.duration_label">
                                <dt class="text-xs text-inkSoft">Durata incontro</dt>
                                <dd class="text-sm font-medium text-ink mt-0.5">{{ selectedGroup.duration_label }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-inkSoft">Modalità</dt>
                                <dd class="text-sm font-medium text-ink mt-0.5">{{ selectedGroup.modality === 'online' ? 'Online' : 'In presenza' }}</dd>
                            </div>
                            <div v-if="selectedGroup.location">
                                <dt class="text-xs text-inkSoft">Dove</dt>
                                <dd class="text-sm font-medium text-ink mt-0.5">{{ selectedGroup.location }}</dd>
                            </div>
                            <div v-if="selectedGroup.leader">
                                <dt class="text-xs text-inkSoft">Conduttrice</dt>
                                <dd class="text-sm font-medium text-ink mt-0.5">{{ selectedGroup.leader }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-inkSoft">Posti disponibili</dt>
                                <dd class="text-sm font-medium mt-0.5" :class="selectedGroup.available_seats > 0 ? 'text-ink' : 'text-red-500'">
                                    {{ selectedGroup.available_seats > 0 ? `${selectedGroup.available_seats} su ${selectedGroup.capacity}` : 'Nessuno — lista d\'attesa' }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Calendario degli incontri -->
                        <div class="mt-4 pt-4 border-t border-line">
                            <h4 class="text-sm font-medium text-ink mb-2">Date degli incontri</h4>
                            <ul v-if="selectedGroup.meetings.length" class="space-y-1.5">
                                <li v-for="m in selectedGroup.meetings" :key="m.id" class="flex items-baseline gap-2 text-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sage shrink-0 translate-y-[-2px]"></span>
                                    <span class="text-ink">{{ capitalize(fmtDay(m.datetime)) }}</span>
                                    <span class="text-inkSoft">· {{ fmtSlot(m.datetime, m.duration) }}</span>
                                    <span v-if="m.title" class="text-inkMuted italic">— {{ m.title }}</span>
                                </li>
                            </ul>
                            <p v-else-if="selectedGroup.next_meeting_at" class="text-sm text-inkSoft">
                                Primo incontro: <span class="text-ink font-medium">{{ capitalize(fmtDay(selectedGroup.next_meeting_at)) }}</span>
                                · {{ fmtSlot(selectedGroup.next_meeting_at, selectedGroup.session_minutes) }}.
                                Il calendario completo verrà comunicato dalla conduttrice.
                            </p>
                            <p v-else class="text-sm text-inkSoft">Le date verranno comunicate dalla conduttrice al momento del contatto.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">Come hai conosciuto questo gruppo?</label>
                    <select v-model="form.how_heard" :class="inputCls">
                        <option value="">Seleziona un'opzione</option>
                        <option v-for="h in HOW_HEARD" :key="h" :value="h">{{ h }}</option>
                    </select>
                </div>

                <label class="flex items-start gap-3 text-sm text-ink">
                    <input v-model="form.privacy_consent" type="checkbox" class="mt-0.5 rounded border-line text-sage focus:ring-sageLight" />
                    <span>Ho letto l'informativa privacy e acconsento al trattamento dei dati.</span>
                </label>
                <p v-if="form.errors.privacy_consent" class="text-xs text-red-500 -mt-3">È necessario il consenso per inviare la richiesta.</p>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="/" class="text-sm text-inkSoft hover:text-ink">Annulla</a>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors disabled:opacity-60">
                        Invia richiesta
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
