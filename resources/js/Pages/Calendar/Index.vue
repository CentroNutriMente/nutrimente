<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MiniMonth from '@/Components/ui/MiniMonth.vue';
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import itLocale from '@fullcalendar/core/locales/it';

const props = defineProps({ professionals: Array });

const selectedProfessional = ref('');
const selectedAppointment = ref(null);
const showModal = ref(false);
const calendarRef = ref(null);
const wrapperEl = ref(null);

// ── Stato barra strumenti (controllata da Vue, non dalla toolbar di FullCalendar) ──
const currentTitle = ref('');
const currentView = ref(typeof window !== 'undefined' && window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek');
const viewStart = ref(new Date());
const todayDate = new Date();

const showSidebar = computed(() => ['timeGridDay', 'dayGridMonth'].includes(currentView.value));
const miniSelected = computed(() => (currentView.value === 'timeGridDay' ? viewStart.value : todayDate));

// ── Palette per tipo (coerente col linguaggio botanico) ──
function toneForType(type) {
    return {
        session: 'sage',
        intervision: 'lavender',
        group: 'blush',
        personal: 'peach',
        blocked: 'slate',
    }[type] || 'sage';
}

const legend = [
    { tone: 'sage',     label: 'Colloqui / Sedute' },
    { tone: 'lavender', label: 'Intervisioni' },
    { tone: 'blush',    label: 'Incontri di gruppo' },
    { tone: 'peach',    label: 'Personale' },
    { tone: 'slate',    label: 'Bloccato' },
];

const typeLabels = {
    session: 'Seduta',
    intervision: 'Intervisione',
    personal: 'Personale',
    blocked: 'Bloccato',
    group: 'Incontro di gruppo',
};

const statusLabels = {
    scheduled: 'Programmato',
    confirmed: 'Confermato',
    cancelled: 'Annullato',
    completed: 'Completato',
};

// ── Utility ──
const cap = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);
const hhmm = (d) => (d ? `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}` : '');
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

// Icona (SVG stroke) associata al tono dell'evento
function eventIcon(tone) {
    const stroke = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
    if (tone === 'blush' || tone === 'lavender') {
        return `<svg class="nm-evt-icon" viewBox="0 0 24 24" ${stroke}><path d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-1a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm6.5-4a3 3 0 1 0-2.5-4.5"/></svg>`;
    }
    if (tone === 'slate' || tone === 'peach') {
        return `<svg class="nm-evt-icon" viewBox="0 0 24 24" ${stroke}><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>`;
    }
    return `<svg class="nm-evt-icon" viewBox="0 0 24 24" ${stroke}><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0"/></svg>`;
}

// ── Caricamento eventi ──
async function fetchEvents(info, successCallback, failureCallback) {
    const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
    if (selectedProfessional.value) params.set('user_id', selectedProfessional.value);

    try {
        const res = await fetch(`/appointments?${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        successCallback(data.map((e) => {
            const type = e.extendedProps?.type || 'session';
            const tone = toneForType(type);
            const cancelled = e.extendedProps?.status === 'cancelled' || String(e.title || '').startsWith('✕');
            const cls = ['nm-evt', `nm-evt--${tone}`];
            if (cancelled) cls.push('nm-evt--cancelled');
            if (e.extendedProps?.is_private) cls.push('nm-evt--private');
            return {
                ...e,
                color: undefined,
                backgroundColor: undefined,
                borderColor: undefined,
                textColor: undefined,
                classNames: cls,
            };
        }));
    } catch (err) {
        failureCallback?.(err);
    }
}

// ── Rendering evento (dipende dalla vista) ──
function renderEventContent(arg) {
    const tone = toneForType(arg.event.extendedProps.type || 'session');

    // Vista mese: solo un pallino colorato
    if (arg.view.type === 'dayGridMonth') {
        return { html: `<span class="nm-dot nm-dot--${tone}"></span>` };
    }

    const time = `${hhmm(arg.event.start)}${arg.event.end ? ` – ${hhmm(arg.event.end)}` : ''}`;
    return {
        html: `<div class="nm-evt-inner">
            <div class="nm-evt-time">${esc(time)}</div>
            <div class="nm-evt-title">${esc(arg.event.title)}</div>
            ${eventIcon(tone)}
        </div>`,
    };
}

// ── Intestazioni colonne (nome giorno + numero, oggi evidenziato) ──
function renderDayHeader(arg) {
    const name = cap(arg.date.toLocaleDateString('it-IT', { weekday: 'long' }));
    if (arg.view.type === 'dayGridMonth') {
        return { html: `<span class="nm-dayhead-name">${name}</span>` };
    }
    const num = arg.date.getDate();
    return {
        html: `<div class="nm-dayhead">
            <span class="nm-dayhead-name">${name}</span>
            <span class="nm-dayhead-num${arg.isToday ? ' is-today' : ''}">${num}</span>
        </div>`,
    };
}

// ── Interazioni ──
function handleEventClick({ event }) {
    if (event.extendedProps.is_private) return;
    selectedAppointment.value = { id: event.id, title: event.title, start: event.start, end: event.end, ...event.extendedProps };
    showModal.value = true;
}

function handleSelect(info) {
    router.visit(route('appointments.create', { start_at: info.startStr }));
}

async function deleteAppointment() {
    if (!confirm('Eliminare questo appuntamento?')) return;
    await fetch(`/appointments/${selectedAppointment.value.id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    showModal.value = false;
    calendarRef.value?.getApi().refetchEvents();
}

// Orario "da parete" (YYYY-MM-DDTHH:mm:ss) senza offset di fuso.
function toNaiveLocal(date) {
    if (!date) return null;
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
        + `T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

async function handleEventDrop({ event, revert }) {
    if (event.extendedProps.is_private || !event.extendedProps.is_own) { revert(); return; }
    try {
        await fetch(`/appointments/${event.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ start_at: toNaiveLocal(event.start), end_at: toNaiveLocal(event.end) }),
        });
    } catch {
        revert();
    }
}

async function handleEventResize({ event, revert }) {
    await handleEventDrop({ event, revert });
}

function handleDatesSet(arg) {
    currentTitle.value = cap(arg.view.title);
    currentView.value = arg.view.type;
    viewStart.value = arg.view.currentStart;
}

// ── Azioni barra strumenti ──
const api = () => calendarRef.value?.getApi();
const goPrev = () => api()?.prev();
const goNext = () => api()?.next();
const goToday = () => api()?.today();
const setView = (v) => api()?.changeView(v);
const onMiniSelect = (date) => api()?.gotoDate(date);
const filterProfessional = () => api()?.refetchEvents();

// FullCalendar misura la larghezza al mount: se il contenitore flex si assesta
// dopo (mount, comparsa/scomparsa della barra laterale, collasso della nav…),
// le colonne restano strette. Ricalcoliamo la dimensione a ogni cambio larghezza.
let resizeObserver = null;
let lastWidth = 0;
onMounted(() => {
    const el = wrapperEl.value;
    if (!el || typeof ResizeObserver === 'undefined') return;
    lastWidth = el.clientWidth;
    resizeObserver = new ResizeObserver(() => {
        const w = el.clientWidth;
        if (w !== lastWidth) { lastWidth = w; api()?.updateSize(); }
    });
    resizeObserver.observe(el);
    requestAnimationFrame(() => api()?.updateSize());
});
onBeforeUnmount(() => resizeObserver?.disconnect());
watch(showSidebar, () => nextTick(() => api()?.updateSize()));

const views = [
    { key: 'timeGridDay',   label: 'Giorno' },
    { key: 'timeGridWeek',  label: 'Settimana' },
    { key: 'dayGridMonth',  label: 'Mese' },
];

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: currentView.value,
    locale: itLocale,
    headerToolbar: false,
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    slotDuration: '00:30:00',
    slotLabelInterval: '01:00',
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    allDaySlot: false,
    nowIndicator: true,
    dayMaxEvents: false,
    expandRows: true,
    selectable: true,
    selectMirror: true,
    editable: true,
    eventResizableFromStart: true,
    height: 'auto',
    events: fetchEvents,
    eventClick: handleEventClick,
    select: handleSelect,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    eventContent: renderEventContent,
    dayHeaderContent: renderDayHeader,
    datesSet: handleDatesSet,
};
</script>

<template>
    <AppLayout title="Agenda">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-serif text-2xl font-semibold text-ink">Agenda</h1>
                <div class="flex flex-wrap items-center gap-2.5">
                    <select
                        v-model="selectedProfessional"
                        @change="filterProfessional"
                        class="rounded-ctrl border border-line bg-cardWarm px-3 pr-8 py-2 text-sm text-ink focus:border-sage focus:ring-sageLight"
                    >
                        <option value="">Tutti i professionisti</option>
                        <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <Link
                        :href="route('appointments.create')"
                        class="inline-flex items-center gap-2 rounded-ctrl bg-sage px-4 py-2 text-sm font-medium text-white shadow-btn transition-colors hover:bg-sageDeep whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                        Nuovo appuntamento
                    </Link>
                </div>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-5 items-start">
            <!-- Calendario -->
            <div ref="wrapperEl" class="nm-calendar flex-1 min-w-0 w-full bg-card rounded-xl2 border border-line shadow-soft p-4 sm:p-5">
                <!-- Barra strumenti -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="goPrev" class="p-1.5 rounded-full text-inkMuted hover:text-ink hover:bg-cream transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <button type="button" @click="goNext" class="p-1.5 rounded-full text-inkMuted hover:text-ink hover:bg-cream transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        <h2 class="font-serif text-xl sm:text-2xl font-semibold text-ink ml-1 capitalize">{{ currentTitle }}</h2>
                        <button
                            type="button"
                            @click="goToday"
                            class="ml-2 inline-flex items-center gap-1.5 rounded-ctrl border border-line bg-cardWarm px-3 py-1.5 text-sm font-medium text-inkSoft hover:bg-cream transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" /></svg>
                            Oggi
                        </button>
                    </div>

                    <!-- Selettore vista (segmented control) -->
                    <div class="inline-flex items-center rounded-ctrl bg-cream p-1">
                        <button
                            v-for="v in views"
                            :key="v.key"
                            type="button"
                            @click="setView(v.key)"
                            class="px-4 py-1.5 rounded-[9px] text-sm font-medium transition-colors"
                            :class="currentView === v.key ? 'bg-card text-ink shadow-soft' : 'text-inkSoft hover:text-ink'"
                        >{{ v.label }}</button>
                    </div>
                </div>

                <FullCalendar ref="calendarRef" :options="calendarOptions" />

                <!-- Legenda a fondo (vista settimana / schermi piccoli) -->
                <div :class="showSidebar ? 'lg:hidden' : ''" class="flex flex-wrap gap-x-5 gap-y-2 mt-5 pt-4 border-t border-line">
                    <div v-for="l in legend" :key="l.tone" class="flex items-center gap-2 text-sm text-inkSoft">
                        <span class="nm-legend-dot" :class="`nm-legend-dot--${l.tone}`"></span>{{ l.label }}
                    </div>
                </div>
            </div>

            <!-- Barra laterale: mini-mese + legenda (vista giorno / mese) -->
            <aside v-if="showSidebar" class="hidden lg:flex w-72 shrink-0 flex-col gap-4">
                <div class="bg-card rounded-xl2 border border-line shadow-soft p-5">
                    <MiniMonth :model-value="miniSelected" :today="todayDate" @select="onMiniSelect" />
                </div>
                <div class="bg-card rounded-xl2 border border-line shadow-soft p-5">
                    <div class="space-y-3">
                        <div v-for="l in legend" :key="l.tone" class="flex items-center gap-3 text-sm text-inkSoft">
                            <span class="nm-legend-dot nm-legend-dot--lg" :class="`nm-legend-dot--${l.tone}`"></span>{{ l.label }}
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Modal dettaglio appuntamento -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
            <div class="bg-card rounded-xl2 shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-serif text-xl font-semibold text-ink">{{ selectedAppointment.title }}</h2>
                    <button @click="showModal = false" class="text-inkMuted hover:text-ink">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <dl class="space-y-2 text-sm mb-5">
                    <div class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Tipo</dt>
                        <dd class="text-ink">{{ typeLabels[selectedAppointment.type] ?? selectedAppointment.type }}</dd>
                    </div>
                    <div v-if="selectedAppointment.status" class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Stato</dt>
                        <dd class="text-ink">{{ statusLabels[selectedAppointment.status] ?? selectedAppointment.status }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Inizio</dt>
                        <dd class="text-ink">{{ new Date(selectedAppointment.start).toLocaleString('it-IT') }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Fine</dt>
                        <dd class="text-ink">{{ new Date(selectedAppointment.end).toLocaleString('it-IT') }}</dd>
                    </div>
                    <div v-if="selectedAppointment.professional" class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Professionista</dt>
                        <dd class="text-ink">{{ selectedAppointment.professional }}</dd>
                    </div>
                    <div v-if="selectedAppointment.room" class="flex gap-3">
                        <dt class="text-inkMuted w-28 shrink-0">Stanza</dt>
                        <dd class="text-ink">{{ selectedAppointment.room }}</dd>
                    </div>
                </dl>
                <div class="flex gap-2 flex-wrap">
                    <Link
                        v-if="selectedAppointment.is_group && selectedAppointment.group_id"
                        :href="route('groups.show', selectedAppointment.group_id)"
                        class="flex-1 px-4 py-2 rounded-ctrl bg-blushWarm text-white text-sm font-medium hover:opacity-90 text-center"
                    >Vai al gruppo</Link>
                    <Link
                        v-if="!selectedAppointment.is_group && selectedAppointment.is_own"
                        :href="route('appointments.edit', selectedAppointment.id)"
                        class="flex-1 px-4 py-2 rounded-ctrl bg-sage text-white text-sm font-medium hover:bg-sageDeep text-center"
                    >Modifica</Link>
                    <Link
                        v-if="!selectedAppointment.is_group && selectedAppointment.patient_id"
                        :href="route('patients.show', selectedAppointment.patient_id)"
                        class="flex-1 px-4 py-2 rounded-ctrl border border-line text-inkSoft text-sm font-medium hover:bg-cream text-center"
                    >Cartella paziente</Link>
                    <button
                        v-if="!selectedAppointment.is_group && selectedAppointment.is_own"
                        @click="deleteAppointment"
                        class="px-4 py-2 rounded-ctrl bg-warnBg text-warn text-sm font-medium hover:opacity-90"
                    >Elimina</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* ============================================================
   Restyling FullCalendar → linguaggio botanico nutrimente.
   Namespaced sotto .nm-calendar per non toccare altro.
   ============================================================ */
.nm-calendar .fc {
    --fc-border-color: var(--line);
    --fc-today-bg-color: rgba(232, 236, 224, 0.35);
    --fc-now-indicator-color: #D98A8A;
    --fc-page-bg-color: transparent;
    font-family: 'Mulish', system-ui, sans-serif;
}
.nm-calendar .fc a { color: inherit; }
.nm-calendar .fc-theme-standard td,
.nm-calendar .fc-theme-standard th { border-color: var(--line); }
.nm-calendar .fc-theme-standard .fc-scrollgrid { border-color: var(--line); border-radius: 14px; }
.nm-calendar .fc-scrollgrid-section-header > * { border-bottom-color: var(--line); }

/* Testata colonne */
.nm-calendar .fc-col-header-cell { padding: 10px 0; background: transparent; }
.nm-calendar .fc-col-header-cell-cushion { display: block; text-decoration: none; padding: 0; }
.nm-calendar .nm-dayhead { display: flex; align-items: center; justify-content: center; gap: 8px; }
.nm-calendar .nm-dayhead-name,
.nm-calendar .fc-col-header-cell .nm-dayhead-name {
    font-size: 13px; font-weight: 600; color: var(--ink-soft);
}
.nm-calendar .nm-dayhead-num {
    font-size: 13px; font-weight: 600; color: var(--ink-muted);
    min-width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;
}
.nm-calendar .nm-dayhead-num.is-today {
    background: var(--sage); color: #fff; border-radius: 999px;
}
/* Vista mese: solo nome giorno, allineato */
.nm-calendar .fc-dayGridMonth-view .fc-col-header-cell { text-align: center; }

/* Fasce orarie */
.nm-calendar .fc-timegrid-slot { height: 3.1em; }
.nm-calendar .fc-timegrid-slot-label-cushion {
    font-size: 12px; color: var(--ink-muted); font-weight: 500; padding-right: 10px;
}
.nm-calendar .fc-timegrid-axis-cushion { font-size: 11px; color: var(--ink-muted); }
.nm-calendar .fc-timegrid-slot-minor { border-top-style: none; }
.nm-calendar .fc-timegrid-col.fc-day-today,
.nm-calendar .fc-daygrid-day.fc-day-today { background: var(--fc-today-bg-color); }

/* Indicatore "adesso" */
.nm-calendar .fc-timegrid-now-indicator-line { border-color: #D98A8A; border-width: 1.5px 0 0; }
.nm-calendar .fc-timegrid-now-indicator-arrow {
    border-color: #D98A8A; border-width: 5px; border-radius: 999px;
    background: #D98A8A; width: 9px; height: 9px; margin-top: -4px; margin-left: -1px;
}

/* ── Eventi (viste giorno/settimana) ── */
.nm-calendar .fc-timegrid-event,
.nm-calendar .fc-timegrid-event .fc-event-main {
    border: none; box-shadow: none; padding: 0;
}
.nm-calendar .fc-timegrid-event {
    border-radius: 10px; overflow: hidden; margin-right: 4px;
    box-shadow: 0 1px 2px rgba(74, 68, 60, 0.06);
}
.nm-calendar .fc-v-event { background: transparent; border: none; }
.nm-calendar .nm-evt-inner {
    position: relative; height: 100%; padding: 6px 9px;
    border-left: 3px solid transparent; border-radius: 10px;
}
.nm-calendar .nm-evt-time { font-size: 11px; font-weight: 500; opacity: 0.75; line-height: 1.3; }
.nm-calendar .nm-evt-title {
    font-size: 13px; font-weight: 600; line-height: 1.3; margin-top: 1px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 16px;
}
.nm-calendar .nm-evt-icon {
    position: absolute; top: 7px; right: 8px; width: 14px; height: 14px; opacity: 0.55;
}

/* Toni */
.nm-calendar .nm-evt--sage     .nm-evt-inner { background: #EAF0E1; border-left-color: #8FA572; color: #46512F; }
.nm-calendar .nm-evt--lavender .nm-evt-inner { background: #EFEAF6; border-left-color: #9B85C4; color: #574B72; }
.nm-calendar .nm-evt--blush    .nm-evt-inner { background: #F8E4E4; border-left-color: #DB9A9A; color: #85474A; }
.nm-calendar .nm-evt--peach    .nm-evt-inner { background: #F7EBDB; border-left-color: #E0B074; color: #855F30; }
.nm-calendar .nm-evt--slate    .nm-evt-inner { background: #ECEBE8; border-left-color: #ABA8A2; color: #625D57; }
.nm-calendar .nm-evt--cancelled .nm-evt-title { text-decoration: line-through; opacity: 0.7; }
.nm-calendar .nm-evt--cancelled .nm-evt-inner { filter: grayscale(0.4); opacity: 0.85; }

/* ── Vista mese: pallini ── */
.nm-calendar .fc-daygrid-day-frame { padding: 2px; }
.nm-calendar .fc-daygrid-day-top { flex-direction: row; justify-content: center; padding-top: 6px; }
.nm-calendar .fc-daygrid-day-number {
    font-size: 13px; font-weight: 600; color: var(--ink-soft); text-decoration: none;
    width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px;
}
.nm-calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number { background: var(--sage); color: #fff; }
.nm-calendar .fc-day-other .fc-daygrid-day-number { color: var(--ink-muted); opacity: 0.55; }
.nm-calendar .fc-dayGridMonth-view .fc-daygrid-day-events {
    display: flex; flex-wrap: wrap; gap: 4px; justify-content: center;
    margin: 4px 4px 6px; min-height: 0;
}
.nm-calendar .fc-dayGridMonth-view .fc-daygrid-event-harness { position: static !important; margin: 0 !important; }
.nm-calendar .fc-dayGridMonth-view .fc-daygrid-event {
    padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important;
}
.nm-calendar .fc-dayGridMonth-view .fc-daygrid-day-bottom { display: none; }
.nm-calendar .fc-dayGridMonth-view .fc-daygrid-more-link { font-size: 11px; color: var(--ink-muted); }
.nm-calendar .nm-dot { width: 8px; height: 8px; border-radius: 999px; display: block; }
.nm-calendar .nm-dot--sage     { background: #8FA572; }
.nm-calendar .nm-dot--lavender { background: #9B85C4; }
.nm-calendar .nm-dot--blush    { background: #DB9A9A; }
.nm-calendar .nm-dot--peach    { background: #E0B074; }
.nm-calendar .nm-dot--slate    { background: #ABA8A2; }

/* ── Legenda ── */
.nm-legend-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; flex-shrink: 0; }
.nm-legend-dot--lg { width: 12px; height: 12px; }
.nm-legend-dot--sage     { background: #8FA572; }
.nm-legend-dot--lavender { background: #9B85C4; }
.nm-legend-dot--blush    { background: #DB9A9A; }
.nm-legend-dot--peach    { background: #E0B074; }
.nm-legend-dot--slate    { background: #ABA8A2; }
</style>
