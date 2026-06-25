<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import IconCircle from '@/Components/ui/IconCircle.vue';
import PillBadge from '@/Components/ui/PillBadge.vue';
import StatTile from '@/Components/ui/StatTile.vue';
import WatercolorHeader from '@/Components/ui/WatercolorHeader.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    userName: String,
    stats: Object,
    todayAppointments: Array,
    requests: Array,
    requestsTotal: Number,
    tasks: Array,
    groups: Array,
});

const firstName = (props.userName || '').split(' ')[0];
const now = new Date();
const greeting = now.getHours() < 12 ? 'Buongiorno' : now.getHours() < 18 ? 'Buon pomeriggio' : 'Buonasera';
const dateLabel = now.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const search = ref('');
const doSearch = () => {
    if (search.value.trim()) router.get(route('patients.index'), { q: search.value.trim() });
};

const euro = (v) => '€ ' + Number(v || 0).toLocaleString('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

const quickActions = [
    { label: 'Nuovo\npaziente',        tone: 'sage',     href: route('patients.create'),  icon: 'M12 5v14M5 12h14' },
    { label: 'Nuova scheda\ncolloquio', tone: 'lavender', href: route('reports.create'),   icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z' },
    { label: 'Nuova iscrizione\ngruppo', tone: 'sage',    href: route('groups.index'),     icon: 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a6 6 0 0 1 12 0M15 14a6 6 0 0 1 6 6' },
    { label: 'Nuova\nfattura',         tone: 'peach',    href: route('invoices.create'),  icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z' },
    { label: 'Carica\ndocumento',      tone: 'lavender', href: route('documents.index'),  icon: 'M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2' },
];

const statTiles = [
    { label: 'Pazienti attivi', value: props.stats.active_patients, sub: 'Totali',        icon: 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a6 6 0 0 1 12 0M15 14a6 6 0 0 1 6 6', tone: 'sage',     delta: props.stats.new_patients ? `+ ${props.stats.new_patients} questo mese` : null },
    { label: 'Nuovi pazienti',  value: props.stats.new_patients,    sub: 'Questo mese',   icon: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0', tone: 'lavender' },
    { label: 'Colloqui svolti', value: props.stats.sessions_done,   sub: 'Questo mese',   icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z', tone: 'sage' },
    { label: 'Fatturato mese',  value: euro(props.stats.revenue_month), sub: 'Lordo',     icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m0 0a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', tone: 'lavender' },
    { label: 'Fatture emesse',  value: props.stats.invoices_month,  sub: 'Questo mese',   icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z', tone: 'peach' },
];
</script>

<template>
    <AppLayout title="Dashboard">
        <!-- Intestazione -->
        <WatercolorHeader class="px-1 pb-2">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 px-2 pt-4 pb-6">
                <div>
                    <h1 class="font-serif text-4xl md:text-[52px] font-semibold text-ink leading-none">{{ greeting }}, {{ firstName }}</h1>

                    <!-- Bloom watercolor (lavanda tint --lav-tint) + fogliame sotto il saluto -->
                    <div class="relative h-14 w-full max-w-md mt-2 mb-1">
                        <span class="wash" style="top:-14px;left:-6px;width:280px;height:100px;background:radial-gradient(circle at 40% 50%, var(--lav-tint), rgba(236,230,244,0) 70%);"></span>
                        <div class="relative flex items-end gap-5 pl-1 h-full">
                            <svg class="w-5 h-11" style="color:var(--lav)" aria-hidden="true"><use href="#bot-lavender" /></svg>
                            <svg class="w-20 h-7 mb-2" style="color:var(--sage)" aria-hidden="true"><use href="#bot-sprig" /></svg>
                            <svg class="w-8 h-11" style="color:var(--sage)" aria-hidden="true"><use href="#bot-sprout" /></svg>
                        </div>
                    </div>

                    <p class="text-inkSoft">
                        Hai {{ todayAppointments.length }} {{ todayAppointments.length === 1 ? 'colloquio' : 'colloqui' }} oggi,
                        {{ tasks.length }} {{ tasks.length === 1 ? 'attività' : 'attività' }} da completare e
                        {{ requestsTotal }} {{ requestsTotal === 1 ? 'richiesta' : 'richieste' }} da gestire.
                    </p>
                </div>
                <div class="flex flex-col items-stretch lg:items-end gap-3 shrink-0">
                    <span class="text-xs text-inkSoft capitalize hidden lg:block">{{ dateLabel }}</span>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Cerca paziente..."
                                class="w-56 pl-4 pr-9 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink placeholder-inkSoft focus:border-sage focus:ring-sageLight" />
                            <button @click="doSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-inkSoft">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                            </button>
                        </div>
                        <Link :href="route('patients.create')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                            Nuovo paziente
                        </Link>
                    </div>
                </div>
            </div>
        </WatercolorHeader>

        <!-- Riga 1: Azioni rapide + Agenda -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <!-- Azioni rapide -->
            <Card class="xl:col-span-2">
                <h2 class="font-serif text-xl text-ink mb-5">Azioni rapide</h2>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    <Link v-for="a in quickActions" :key="a.label" :href="a.href"
                        class="flex flex-col items-center gap-2.5 p-3 rounded-ctrl hover:bg-cream transition-colors text-center">
                        <IconCircle :icon="a.icon" :tone="a.tone" size="lg" />
                        <span class="text-xs text-inkSoft leading-tight whitespace-pre-line">{{ a.label }}</span>
                    </Link>
                </div>
            </Card>

            <!-- Agenda di oggi -->
            <Card :padded="false" class="xl:row-span-2">
                <div class="flex items-center justify-between px-6 pt-6 pb-4">
                    <div class="flex items-center gap-2.5">
                        <IconCircle icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" tone="sage" size="sm" />
                        <h2 class="font-serif text-xl text-ink">Agenda di oggi</h2>
                    </div>
                    <Link :href="route('calendar')" class="text-xs text-sage hover:underline">Vai al calendario →</Link>
                </div>
                <div class="px-3 pb-4">
                    <div v-if="todayAppointments.length === 0" class="py-10 text-center text-inkSoft text-sm">Nessun appuntamento per oggi.</div>
                    <div v-else class="divide-y divide-line">
                        <div v-for="a in todayAppointments" :key="a.id" class="flex items-center gap-3 px-3 py-3">
                            <span class="text-sm font-semibold text-ink w-12 shrink-0">{{ a.time }}</span>
                            <span class="text-sm font-bold text-ink w-12 shrink-0">{{ a.who }}</span>
                            <span class="flex-1 text-sm text-inkSoft min-w-0 truncate" :class="a.online ? 'text-lavender' : ''">{{ a.online ? 'Online' : a.label }}</span>
                            <span class="w-2 h-2 rounded-full shrink-0" :class="a.tone === 'sage' ? 'bg-sage' : 'bg-lavender'"></span>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Riga 2: Richieste / Attività / Gruppi -->
            <!-- Richieste da gestire -->
            <Card>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <IconCircle icon="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" tone="lavender" size="sm" />
                        <h2 class="font-serif text-lg text-ink">Richieste da gestire</h2>
                    </div>
                    <PillBadge tone="lavender">{{ requestsTotal }} totali</PillBadge>
                </div>
                <div class="space-y-3">
                    <Link v-for="r in requests" :key="r.label" :href="r.href" class="flex items-center gap-3 group">
                        <span class="font-serif text-2xl font-semibold w-7 text-center" :class="r.tone === 'sage' ? 'text-sage' : 'text-lavender'">{{ r.count }}</span>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-ink group-hover:underline">{{ r.label }}</div>
                            <div class="text-xs text-inkSoft">{{ r.sub }}</div>
                        </div>
                    </Link>
                </div>
                <Link :href="route('contact-requests.inbox')" class="inline-block mt-4 text-sm text-sage hover:underline">Vai alle richieste →</Link>
            </Card>

            <!-- Attività da completare -->
            <Card>
                <div class="flex items-center gap-2.5 mb-4">
                    <IconCircle icon="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" tone="sage" size="sm" />
                    <h2 class="font-serif text-lg text-ink">Attività da completare</h2>
                </div>
                <div v-if="tasks.length === 0" class="text-sm text-inkSoft py-4">Nessuna attività in sospeso.</div>
                <div v-else class="space-y-3">
                    <div v-for="t in tasks" :key="t.id" class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full border-2 border-line shrink-0"></span>
                        <span class="flex-1 text-sm text-ink min-w-0 truncate">{{ t.title }}</span>
                        <PillBadge v-if="t.due" :tone="t.due === 'Scaduto' ? 'peach' : t.due === 'Oggi' ? 'lavender' : 'neutral'">{{ t.due }}</PillBadge>
                    </div>
                </div>
                <Link :href="route('workspace.index')" class="inline-block mt-4 text-sm text-sage hover:underline">Vedi tutte le attività →</Link>
            </Card>

            <!-- Gruppi attivi -->
            <Card>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <IconCircle icon="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a6 6 0 0 1 12 0M15 14a6 6 0 0 1 6 6" tone="sage" size="sm" />
                        <h2 class="font-serif text-lg text-ink">Gruppi attivi</h2>
                    </div>
                    <Link :href="route('groups.index')" class="text-xs text-sage hover:underline">Vai ai gruppi →</Link>
                </div>
                <div v-if="groups.length === 0" class="text-sm text-inkSoft py-4">Nessun gruppo attivo.</div>
                <div v-else class="space-y-3">
                    <Link v-for="g in groups" :key="g.id" :href="route('groups.show', g.id)" class="flex items-center gap-3 group">
                        <IconCircle icon="M12 3c1.5 2 4 3.5 4 7a4 4 0 1 1-8 0c0-3.5 2.5-5 4-7Z" :tone="g.tone" size="sm" />
                        <span class="flex-1 text-sm font-medium text-ink min-w-0 truncate group-hover:underline">{{ g.name }}</span>
                        <PillBadge :tone="g.tone">{{ g.enrolled }} / {{ g.capacity }} iscritti</PillBadge>
                    </Link>
                </div>
            </Card>
        </div>

        <!-- Statistiche -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mt-5">
            <StatTile v-for="s in statTiles" :key="s.label" v-bind="s" />
        </div>

        <!-- Citazione -->
        <div class="mt-5 rounded-xl2 bg-lavenderLight border border-line px-6 py-5 flex items-center gap-4">
            <svg class="w-7 h-7 text-lavender shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M7.17 6A5.17 5.17 0 0 0 2 11.17V18h6.83v-6.83H5.5A1.67 1.67 0 0 1 7.17 9.5V6Zm10 0A5.17 5.17 0 0 0 12 11.17V18h6.83v-6.83H15.5a1.67 1.67 0 0 1 1.67-1.67V6Z"/></svg>
            <p class="font-serif text-lg text-ink italic">Ogni piccolo passo che scegli oggi, sostiene il benessere di domani.</p>
        </div>
    </AppLayout>
</template>
