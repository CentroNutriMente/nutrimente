<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
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

async function fetchEvents(info, successCallback) {
    const params = new URLSearchParams({
        start: info.startStr,
        end: info.endStr,
    });
    if (selectedProfessional.value) params.set('user_id', selectedProfessional.value);

    const res = await fetch(`/appointments?${params}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await res.json();
    successCallback(data);
}

function handleEventClick({ event }) {
    selectedAppointment.value = {
        id: event.id,
        title: event.title,
        start: event.start,
        end: event.end,
        ...event.extendedProps,
    };
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

async function handleEventDrop({ event, revert }) {
    try {
        await fetch(`/appointments/${event.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ start_at: event.startStr, end_at: event.endStr }),
        });
    } catch {
        revert();
    }
}

async function handleEventResize({ event, revert }) {
    await handleEventDrop({ event, revert });
}

function renderEventContent(arg) {
    const room = arg.event.extendedProps.room;
    const professional = arg.event.extendedProps.professional;
    return {
        html: `<div style="padding:2px 4px; font-size:11px; overflow:hidden">
            <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">${arg.event.title}</div>
            ${professional ? `<div style="opacity:0.85">${professional}</div>` : ''}
            ${room ? `<div style="opacity:0.75">📍 ${room}</div>` : ''}
        </div>`,
    };
}

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    locale: itLocale,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    allDaySlot: false,
    nowIndicator: true,
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
};

const typeLabels = {
    session: 'Seduta',
    intervision: 'Intervisione',
    personal: 'Personale',
    blocked: 'Bloccato',
};

const statusLabels = {
    scheduled: 'Programmato',
    confirmed: 'Confermato',
    cancelled: 'Annullato',
    completed: 'Completato',
};

function filterProfessional() {
    calendarRef.value?.getApi().refetchEvents();
}
</script>

<template>
    <AppLayout title="Calendario">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Calendario condiviso</h1>
                <div class="flex items-center gap-3">
                    <select
                        v-model="selectedProfessional"
                        @change="filterProfessional"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        <option value="">Tutti i professionisti</option>
                        <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <Link
                        :href="route('appointments.create')"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700"
                    >+ Appuntamento</Link>
                </div>
            </div>
        </template>

        <!-- Legenda tipi -->
        <div class="flex gap-4 mb-4">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="w-3 h-3 rounded-full bg-purple-600 inline-block"></span> Seduta
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="w-3 h-3 rounded-full bg-violet-600 inline-block"></span> Intervisione
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="w-3 h-3 rounded-full bg-sky-600 inline-block"></span> Personale
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span> Bloccato
            </div>
        </div>

        <!-- Calendario -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <FullCalendar ref="calendarRef" :options="calendarOptions" />
        </div>

        <!-- Modal dettaglio appuntamento -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="showModal = false">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800 text-lg">{{ selectedAppointment.title }}</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <dl class="space-y-2 text-sm mb-5">
                    <div class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Tipo</dt>
                        <dd>{{ typeLabels[selectedAppointment.type] ?? selectedAppointment.type }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Stato</dt>
                        <dd>{{ statusLabels[selectedAppointment.status] ?? selectedAppointment.status }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Inizio</dt>
                        <dd>{{ new Date(selectedAppointment.start).toLocaleString('it-IT') }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Fine</dt>
                        <dd>{{ new Date(selectedAppointment.end).toLocaleString('it-IT') }}</dd>
                    </div>
                    <div v-if="selectedAppointment.professional" class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Professionista</dt>
                        <dd>{{ selectedAppointment.professional }}</dd>
                    </div>
                    <div v-if="selectedAppointment.room" class="flex gap-3">
                        <dt class="text-gray-400 w-28 shrink-0">Stanza</dt>
                        <dd>{{ selectedAppointment.room }}</dd>
                    </div>
                </dl>
                <div class="flex gap-2 flex-wrap">
                    <Link
                        :href="route('appointments.edit', selectedAppointment.id)"
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 text-center"
                    >Modifica</Link>
                    <Link
                        v-if="selectedAppointment.patient_id"
                        :href="route('patients.show', selectedAppointment.patient_id)"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 text-center"
                    >Cartella paziente</Link>
                    <button
                        @click="deleteAppointment"
                        class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100 border border-red-200"
                    >Elimina</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
