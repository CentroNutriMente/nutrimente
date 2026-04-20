<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    professional: Object,
    availability: Array,   // [{day_of_week, start_time, end_time}]
    booked_slots: Array,   // [{date, time}]
});

const page = usePage();

// Parse JSON curriculum (formazione / esperienze / aree)
const cv = computed(() => {
    if (!props.professional.curriculum) return null;
    try { return JSON.parse(props.professional.curriculum); } catch { return null; }
});

// ── Week navigation ───────────────────────────────────────────────────────────
const weekOffset = ref(0); // 0 = current week, 1 = next week, ...

const weekStart = computed(() => {
    const d = new Date();
    const day = d.getDay(); // 0=Sun
    const diff = (day === 0 ? -6 : 1 - day); // get to Monday
    d.setDate(d.getDate() + diff + weekOffset.value * 7);
    d.setHours(0, 0, 0, 0);
    return d;
});

const weekDays = computed(() => {
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(weekStart.value);
        d.setDate(d.getDate() + i);
        return d;
    });
});

const weekLabel = computed(() => {
    const s = weekDays.value[0];
    const e = weekDays.value[6];
    return s.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' })
        + ' – '
        + e.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
});

function dateKey(d) { return d.toLocaleDateString('sv-SE'); }
function isToday(d) { return dateKey(d) === dateKey(new Date()); }
function isPast(d) {
    const today = new Date(); today.setHours(0,0,0,0);
    return d < today;
}

const dayNames = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];

// Generate individual bookable time slots from availability ranges for a given day.
// E.g. range 10:00–12:00 with 50-min sessions → ['10:00', '10:50']
function slotsForDay(dayIndex) {
    const ranges   = props.availability.filter(s => s.day_of_week === dayIndex);
    const duration = props.professional.session_duration || 50;
    const slots    = [];

    for (const range of ranges) {
        const [sh, sm] = range.start_time.split(':').map(Number);
        const [eh, em] = range.end_time.split(':').map(Number);
        const endMin   = eh * 60 + em;
        let   cur      = sh * 60 + sm;

        while (cur + duration <= endMin) {
            const h = String(Math.floor(cur / 60)).padStart(2, '0');
            const m = String(cur % 60).padStart(2, '0');
            slots.push(`${h}:${m}`);
            cur += duration;
        }
    }

    return slots;
}

function isBooked(date, time) {
    const dk = dateKey(date);
    return props.booked_slots.some(b => b.date === dk && b.time === time);
}

// ── Selected slot & form ──────────────────────────────────────────────────────
const selectedSlot = ref(null); // {date, time}
const showForm = ref(false);

function selectSlot(date, time) {
    if (isPast(date) || isBooked(date, time)) return;
    selectedSlot.value = { date: dateKey(date), time };
    form.requested_date = dateKey(date);
    form.requested_time = time;
    showForm.value = true;
    setTimeout(() => document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
}

const form = useForm({
    patient_name:    '',
    patient_surname: '',
    patient_email:   '',
    patient_phone:   '',
    notes:           '',
    requested_date:  '',
    requested_time:  '',
});

function submit() {
    form.post(`/prenota/${props.professional.slug}`, {
        onSuccess: () => {
            showForm.value = false;
            selectedSlot.value = null;
            form.reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: () => {
            // validation errors are shown inline via form.errors
        },
    });
}

// Unique day_of_week values that have at least one slot (for availability table)
const availabilityByDay = computed(() => {
    const map = {};
    props.availability.forEach(s => {
        if (!map[s.day_of_week]) map[s.day_of_week] = [];
        map[s.day_of_week].push(`${s.start_time}–${s.end_time}`);
    });
    return map;
});
const dayFull = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica'];
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Top bar -->
        <header class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-3">
                <a href="/prenota" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="/prenota" class="flex items-center gap-2">
                    <img src="/logo.jpeg" alt="NutriMente" class="h-8 w-auto" />
                </a>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-10 space-y-10">

            <!-- Success flash -->
            <div v-if="page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-800 rounded-2xl px-6 py-4 text-sm font-medium">
                {{ page.props.flash.success }}
            </div>

            <!-- Error flash -->
            <div v-if="page.props.flash?.error"
                class="bg-red-50 border border-red-200 text-red-700 rounded-2xl px-6 py-4 text-sm font-medium">
                {{ page.props.flash.error }}
            </div>

            <!-- Professional hero -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Top: photo + name + bio -->
                <div class="p-8 flex flex-col sm:flex-row gap-6">
                    <img :src="professional.photo" :alt="professional.name"
                        class="w-32 h-32 rounded-2xl object-cover shrink-0 mx-auto sm:mx-0 ring-2 ring-purple-100" />
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-purple-500 uppercase tracking-wider mb-1">
                            {{ professional.category }}
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-3">
                            {{ professional.title ? professional.title + ' ' : '' }}{{ professional.name.split(' ').slice(-1)[0] }}
                        </h1>
                        <p v-if="professional.bio" class="text-gray-500 text-sm leading-relaxed">{{ professional.bio }}</p>
                        <div v-if="professional.session_price" class="mt-4 inline-flex items-center gap-2 bg-purple-50 text-purple-700 text-sm font-medium px-3 py-1.5 rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            € {{ Number(professional.session_price).toLocaleString('it-IT', {minimumFractionDigits:2}) }} a seduta
                            <span v-if="professional.session_duration" class="text-purple-400 font-normal">· {{ professional.session_duration }} min</span>
                        </div>
                    </div>
                </div>

                <template v-if="cv">
                    <!-- Aree di intervento -->
                    <div v-if="cv.aree?.length" class="px-8 pb-6">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Aree di intervento</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="area in cv.aree" :key="area"
                                class="text-xs bg-purple-50 text-purple-700 font-medium px-3 py-1 rounded-full border border-purple-100">
                                {{ area }}
                            </span>
                        </div>
                    </div>

                    <!-- Formazione -->
                    <div v-if="cv.formazione?.length" class="border-t border-gray-100 px-8 py-6">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Formazione</p>
                        <div class="space-y-4">
                            <div v-for="f in cv.formazione" :key="f.titolo" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 leading-snug">{{ f.titolo }}</p>
                                    <p v-if="f.ente" class="text-xs text-gray-400 mt-0.5">{{ f.ente }}</p>
                                    <p v-if="f.nota" class="text-xs text-purple-400 mt-0.5 italic">{{ f.nota }}</p>
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1.5 ml-2">
                                    <span v-if="f.anno" class="text-xs text-gray-400 font-medium">{{ f.anno }}</span>
                                    <span v-if="f.voto" class="text-[10px] bg-emerald-50 text-emerald-600 font-bold px-2 py-0.5 rounded-full border border-emerald-100">
                                        {{ f.voto }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Esperienze cliniche -->
                    <div v-if="cv.esperienze?.length" class="border-t border-gray-100 px-8 py-6">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Esperienze cliniche</p>
                        <div class="space-y-4">
                            <div v-for="e in cv.esperienze" :key="e.ruolo"
                                class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ e.ruolo }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ e.ente }}</p>
                                    </div>
                                    <span class="text-[11px] bg-white border border-purple-100 text-purple-600 font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">
                                        {{ e.periodo }}
                                    </span>
                                </div>
                                <ul v-if="e.attivita?.length" class="space-y-1.5 pt-1 border-t border-gray-200">
                                    <li v-for="a in e.attivita" :key="a"
                                        class="flex items-start gap-2 text-xs text-gray-500 leading-snug">
                                        <span class="text-purple-300 shrink-0 mt-0.5 font-bold">–</span>
                                        {{ a }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Availability table -->
            <div v-if="Object.keys(availabilityByDay).length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Orari disponibili</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th v-for="(_, di) in availabilityByDay" :key="di"
                                    class="text-left pb-2 pr-6 font-semibold text-gray-500 text-xs uppercase tracking-wide">
                                    {{ dayFull[di] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td v-for="(slots, di) in availabilityByDay" :key="di" class="pt-3 pr-6 align-top">
                                    <div class="space-y-1">
                                        <span v-for="slot in slots" :key="slot"
                                            class="block text-gray-700 font-mono text-xs bg-purple-50 px-2 py-0.5 rounded">
                                            {{ slot }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Weekly calendar -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Week nav -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <button @click="weekOffset = Math.max(0, weekOffset - 1)"
                        :disabled="weekOffset === 0"
                        class="p-2 rounded-xl hover:bg-gray-100 disabled:opacity-30 transition-colors text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="text-sm font-semibold text-gray-700 capitalize">{{ weekLabel }}</span>
                    <button @click="weekOffset++"
                        class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Day columns -->
                <div class="grid grid-cols-7 divide-x divide-gray-100">
                    <div v-for="(day, idx) in weekDays" :key="idx" class="min-h-[140px]">
                        <!-- Day header -->
                        <div class="text-center py-2 border-b border-gray-100"
                            :class="isPast(day) ? 'opacity-40' : ''">
                            <div class="text-[10px] font-semibold text-gray-400 uppercase">{{ dayNames[idx] }}</div>
                            <div class="text-sm font-bold mt-0.5"
                                :class="isToday(day) ? 'text-purple-600' : 'text-gray-700'">
                                {{ day.getDate() }}
                            </div>
                        </div>

                        <!-- Slots -->
                        <div class="p-1 space-y-1">
                            <template v-if="!isPast(day)">
                                <button
                                    v-for="time in slotsForDay(idx)"
                                    :key="time"
                                    @click="selectSlot(day, time)"
                                    :disabled="isBooked(day, time)"
                                    :class="[
                                        isBooked(day, time)
                                            ? 'bg-gray-100 text-gray-300 cursor-not-allowed line-through'
                                            : selectedSlot?.date === dateKey(day) && selectedSlot?.time === time
                                                ? 'bg-purple-600 text-white shadow-sm'
                                                : 'bg-purple-50 text-purple-700 hover:bg-purple-100',
                                        'w-full text-[11px] font-semibold px-1 py-1 rounded-lg transition-all text-center'
                                    ]"
                                >
                                    {{ time }}
                                </button>
                                <div v-if="slotsForDay(idx).length === 0"
                                    class="text-center text-[10px] text-gray-200 pt-2">—</div>
                            </template>
                            <div v-else class="text-center text-[10px] text-gray-200 pt-4">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking form -->
            <div v-if="showForm" id="booking-form"
                class="bg-white rounded-2xl border border-purple-200 shadow-md p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-800">Richiesta appuntamento</h2>
                        <p class="text-xs text-gray-400">
                            {{ new Date(selectedSlot.date).toLocaleDateString('it-IT', {weekday:'long', day:'numeric', month:'long'}) }}
                            alle {{ selectedSlot.time }}
                            con {{ professional.title ? professional.title + ' ' : '' }}{{ professional.name }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nome *</label>
                        <input v-model="form.patient_name" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.patient_name" class="text-xs text-red-500 mt-1">{{ form.errors.patient_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Cognome *</label>
                        <input v-model="form.patient_surname" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.patient_surname" class="text-xs text-red-500 mt-1">{{ form.errors.patient_surname }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Email *</label>
                        <input v-model="form.patient_email" type="email"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.patient_email" class="text-xs text-red-500 mt-1">{{ form.errors.patient_email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Telefono</label>
                        <input v-model="form.patient_phone" type="tel"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Note (facoltativo)</label>
                        <textarea v-model="form.notes" rows="3"
                            placeholder="Breve descrizione del motivo della visita..."
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <button @click="submit" :disabled="form.processing || !form.patient_name || !form.patient_surname || !form.patient_email"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-semibold px-8 py-2.5 rounded-xl transition-colors">
                        {{ form.processing ? 'Invio in corso…' : 'Invia richiesta' }}
                    </button>
                    <button @click="showForm = false; selectedSlot = null"
                        class="text-sm text-gray-400 hover:text-gray-600 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Annulla
                    </button>
                </div>
            </div>

            <!-- Prompt if no slot selected -->
            <div v-else-if="availability.length"
                class="text-center text-sm text-gray-400 py-4">
                Seleziona uno slot disponibile nel calendario per prenotare.
            </div>
            <div v-else class="text-center text-sm text-gray-400 py-4">
                Nessun orario disponibile configurato. Contattaci direttamente.
            </div>

        </main>

        <footer class="border-t border-gray-100 mt-16">
            <div class="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between text-xs text-gray-400">
                <span>© {{ new Date().getFullYear() }} NutriMente</span>
                <a href="/login" class="hover:text-gray-600">Accesso professionisti</a>
            </div>
        </footer>
    </div>
</template>
