<script setup>
// Mini calendario mensile (navigatore) coerente con il linguaggio botanico.
// Settimana che parte da lunedì, etichette IT. Emette "select" col giorno cliccato.
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Date, default: () => new Date() }, // giorno selezionato / in vista
    today:      { type: Date, default: () => new Date() },
});
const emit = defineEmits(['select']);

// Mese attualmente mostrato (primo giorno del mese)
const cursor = ref(startOfMonth(props.modelValue));

// Se il calendario principale naviga, riallinea il mese mostrato
watch(() => props.modelValue, (d) => { cursor.value = startOfMonth(d); });

function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); }
function sameDay(a, b) {
    return a && b && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

const weekDays = ['L', 'M', 'M', 'G', 'V', 'S', 'D'];

const monthLabel = computed(() =>
    cursor.value.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })
);

// Griglia 6×7 a partire dal lunedì della settimana che contiene il 1° del mese
const cells = computed(() => {
    const first = cursor.value;
    const offset = (first.getDay() + 6) % 7; // lun=0 … dom=6
    const start = new Date(first);
    start.setDate(first.getDate() - offset);
    return Array.from({ length: 42 }, (_, i) => {
        const date = new Date(start);
        date.setDate(start.getDate() + i);
        return {
            date,
            day: date.getDate(),
            outside: date.getMonth() !== first.getMonth(),
            isToday: sameDay(date, props.today),
            isSelected: sameDay(date, props.modelValue),
        };
    });
});

function prev() { cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() - 1, 1); }
function next() { cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 1); }
</script>

<template>
    <div class="select-none">
        <!-- Testata: mese + navigazione -->
        <div class="flex items-center justify-between mb-3">
            <button type="button" @click="prev" class="p-1 rounded-full text-inkMuted hover:text-ink hover:bg-cream transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <span class="font-serif text-lg text-ink capitalize">{{ monthLabel }}</span>
            <button type="button" @click="next" class="p-1 rounded-full text-inkMuted hover:text-ink hover:bg-cream transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>

        <!-- Intestazioni giorni -->
        <div class="grid grid-cols-7 mb-1">
            <span v-for="(w, i) in weekDays" :key="i" class="text-center text-[11px] font-semibold text-inkMuted py-1">{{ w }}</span>
        </div>

        <!-- Giorni -->
        <div class="grid grid-cols-7 gap-y-0.5">
            <button
                v-for="(c, i) in cells"
                :key="i"
                type="button"
                @click="emit('select', c.date)"
                class="mx-auto flex h-7 w-7 items-center justify-center rounded-full text-[13px] transition-colors"
                :class="[
                    c.isSelected
                        ? 'bg-sage text-white font-semibold shadow-btn'
                        : c.outside
                            ? 'text-inkMuted/50 hover:bg-cream'
                            : 'text-ink hover:bg-cream',
                    !c.isSelected && c.isToday ? 'ring-1 ring-sage font-semibold text-sage' : '',
                ]"
            >{{ c.day }}</button>
        </div>
    </div>
</template>
