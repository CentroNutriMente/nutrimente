<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({ template: Object });

const isEdit = !!props.template;

const buildSections = () => {
    const s = props.template?.scoring?.sections;
    if (!s?.length) return [];
    return s.map(sec => ({
        name:       sec.name ?? '',
        operation:  sec.operation ?? 'sum',
        multiplier: sec.multiplier ?? null,
        divisor:    sec.divisor ?? null,
    }));
};

// During editing questions store section_id as the actual "sN" id from template.
// We need to map them to array indices (0-based string) for the reactive form.
const buildQuestions = () => {
    if (!props.template?.questions?.length) return [];
    const sections = props.template?.scoring?.sections ?? [];
    return props.template.questions.map(q => {
        let sectionIndex = '';
        if (q.section_id) {
            const idx = sections.findIndex(s => s.id === q.section_id);
            sectionIndex = idx >= 0 ? String(idx) : '';
        }
        return {
            text:         q.text ?? '',
            section_index: sectionIndex,
            answers: q.answers ? q.answers.map(a => ({ label: a.label ?? '', score: a.score ?? 0 })) : [{ label: '', score: 0 }],
        };
    });
};

const buildScoring = () => {
    const s = props.template?.scoring;
    return {
        sections:   buildSections(),
        formula:    s?.formula ?? '',
        base:       s?.base ?? 'sum',
        divisor:    s?.divisor ?? null,
        multiplier: s?.multiplier ?? null,
        thresholds: s?.thresholds ? s.thresholds.map(t => ({
            min:   t.min,
            max:   t.max,
            label: t.label,
            color: t.color,
        })) : [],
    };
};

const form = useForm({
    name:        props.template?.name ?? '',
    description: props.template?.description ?? '',
    questions:   buildQuestions(),
    scoring:     buildScoring(),
});

const formulaTextarea = ref(null);

const hasSections = computed(() => form.scoring.sections.length > 0);

// Section management
function addSection() {
    form.scoring.sections.push({ name: '', operation: 'sum', multiplier: null, divisor: null });
}

function removeSection(i) {
    form.scoring.sections.splice(i, 1);
    // Clear section_index for questions that referenced this or higher index
    form.questions.forEach(q => {
        if (q.section_index === String(i)) {
            q.section_index = '';
        } else if (q.section_index !== '' && Number(q.section_index) > i) {
            q.section_index = String(Number(q.section_index) - 1);
        }
    });
}

// Question management
function addQuestion() {
    const last = form.questions[form.questions.length - 1];
    const answers = last
        ? last.answers.map(a => ({ label: a.label, score: a.score }))
        : [{ label: '', score: 0 }];
    form.questions.push({ text: '', section_index: '', answers });
}

function removeQuestion(qi) {
    if (form.questions.length <= 1) return;
    form.questions.splice(qi, 1);
}

function copyAnswersFrom(targetQi, sourceQi) {
    const source = form.questions[sourceQi];
    if (!source) return;
    form.questions[targetQi].answers = source.answers.map(a => ({ label: a.label, score: a.score }));
    copyMenuOpen.value = null;
}

function addAnswer(qi) {
    form.questions[qi].answers.push({ label: '', score: 0 });
}

function removeAnswer(qi, ai) {
    if (form.questions[qi].answers.length <= 1) return;
    form.questions[qi].answers.splice(ai, 1);
}

function moveAnswer(qi, ai, dir) {
    const answers = form.questions[qi].answers;
    const target = ai + dir;
    if (target < 0 || target >= answers.length) return;
    [answers[ai], answers[target]] = [answers[target], answers[ai]];
}

const copyMenuOpen = ref(null);

function closeCopyMenu(e) {
    if (!e.target.closest('.relative')) copyMenuOpen.value = null;
}
onMounted(() => document.addEventListener('click', closeCopyMenu));
onBeforeUnmount(() => document.removeEventListener('click', closeCopyMenu));

// Threshold management
function addThreshold() {
    form.scoring.thresholds.push({ min: 0, max: 0, label: '', color: 'green' });
}

function removeThreshold(i) {
    form.scoring.thresholds.splice(i, 1);
}

// Formula helpers
function appendToFormula(name) {
    const sep = form.scoring.formula && !form.scoring.formula.endsWith(' ') ? ' ' : '';
    form.scoring.formula += sep + name;
    formulaTextarea.value?.focus();
}

const formulaPreview = computed(() => {
    if (hasSections.value) {
        return form.scoring.formula || '—';
    }
    const base = form.scoring.base === 'average' ? 'Media' : 'Somma';
    let f = `Punteggio = ${base}`;
    if (form.scoring.multiplier) f += ` × ${form.scoring.multiplier}`;
    if (form.scoring.divisor)    f += ` / ${form.scoring.divisor}`;
    return f;
});

function submit() {
    // Re-index sections with proper IDs
    const reindexedSections = form.scoring.sections.map((s, i) => ({
        id:         's' + (i + 1),
        name:       s.name,
        operation:  s.operation,
        multiplier: s.multiplier || null,
        divisor:    s.divisor || null,
    }));

    // Re-index questions and map section_index → section id
    const reindexedQuestions = form.questions.map((q, qi) => {
        let section_id = null;
        if (hasSections.value && q.section_index !== '') {
            const idx = Number(q.section_index);
            if (reindexedSections[idx]) {
                section_id = reindexedSections[idx].id;
            }
        }
        return {
            id:         'q' + (qi + 1),
            section_id,
            text:       q.text,
            answers:    q.answers.map((a, ai) => ({
                id:    'a' + (ai + 1),
                label: a.label,
                score: a.score,
            })),
        };
    });

    const payload = {
        name:        form.name,
        description: form.description,
        questions:   reindexedQuestions,
        scoring: {
            sections:   hasSections.value ? reindexedSections : [],
            formula:    hasSections.value ? form.scoring.formula : '',
            base:       hasSections.value ? null : form.scoring.base,
            divisor:    hasSections.value ? null : (form.scoring.divisor || null),
            multiplier: hasSections.value ? null : (form.scoring.multiplier || null),
            thresholds: form.scoring.thresholds,
        },
    };

    if (isEdit) {
        form.transform(() => payload).put(route('questionnaire-templates.update', props.template.id));
    } else {
        form.transform(() => payload).post(route('questionnaire-templates.store'));
    }
}
</script>

<template>
    <AppLayout title="Gestione Questionari">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('questionnaire-templates.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ isEdit ? 'Modifica modello' : 'Nuovo modello questionario' }}
                </h1>
            </div>
        </template>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Section A: Info -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Informazioni generali</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome modello <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                        <textarea v-model="form.description" rows="2"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none" />
                    </div>
                </div>

                <!-- Section B: Sezioni -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Sezioni (opzionale)</h2>
                        <button type="button" @click="addSection"
                            class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            + Aggiungi sezione
                        </button>
                    </div>

                    <div v-if="form.scoring.sections.length === 0" class="text-center text-sm text-gray-400 py-4 border border-dashed border-gray-200 rounded-lg">
                        Nessuna sezione. Le sezioni permettono un punteggio composito con formula personalizzata.
                    </div>

                    <div class="space-y-3">
                        <div v-for="(sec, si) in form.scoring.sections" :key="si"
                            class="border border-gray-200 rounded-lg p-3 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-purple-600 bg-purple-50 rounded px-2 py-1 shrink-0">
                                    S{{ si + 1 }}
                                </span>
                                <input v-model="sec.name" type="text" placeholder="Nome sezione (es. Restrizione)"
                                    class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                                <select v-model="sec.operation"
                                    class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                                    <option value="sum">Somma</option>
                                    <option value="average">Media</option>
                                </select>
                                <button type="button" @click="removeSection(si)"
                                    class="text-gray-300 hover:text-red-400 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-3 pl-9">
                                <div class="flex items-center gap-1.5">
                                    <label class="text-xs text-gray-500">Moltiplicatore</label>
                                    <input v-model.number="sec.multiplier" type="number" step="any" placeholder="—"
                                        class="w-20 border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <label class="text-xs text-gray-500">Divisore</label>
                                    <input v-model.number="sec.divisor" type="number" step="any" min="0.01" placeholder="—"
                                        class="w-20 border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="hasSections" class="text-xs text-gray-400 mt-3">
                        Assegna le domande alle sezioni nel pannello Domande qui sotto.
                    </p>
                </div>

                <!-- Section C: Domande -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Domande</h2>
                        <button type="button" @click="addQuestion"
                            class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            + Aggiungi domanda
                        </button>
                    </div>

                    <div v-if="form.questions.length === 0" class="text-center text-sm text-gray-400 py-6 border border-dashed border-gray-200 rounded-lg">
                        Nessuna domanda. Clicca "+ Aggiungi domanda" per iniziare.
                    </div>

                    <div class="space-y-4">
                        <div v-for="(q, qi) in form.questions" :key="qi"
                            class="border border-gray-200 rounded-lg p-4 space-y-3">

                            <div class="flex items-start gap-3">
                                <span class="text-xs font-semibold text-purple-600 bg-purple-50 rounded px-2 py-1 shrink-0 mt-0.5">
                                    D{{ qi + 1 }}
                                </span>
                                <div class="flex-1 space-y-3">
                                    <textarea v-model="q.text" rows="2" required
                                        placeholder="Testo della domanda..."
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none" />

                                    <div v-if="hasSections">
                                        <label class="text-xs font-medium text-gray-600 mb-1 block">Sezione</label>
                                        <select v-model="q.section_index"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                                            <option value="">— Nessuna sezione —</option>
                                            <option v-for="(sec, si) in form.scoring.sections" :key="si" :value="String(si)">
                                                {{ sec.name || 'Sezione ' + (si + 1) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-medium text-gray-600">Risposte</label>
                                            <div class="flex items-center gap-3">
                                                <div class="relative" v-if="form.questions.length > 1">
                                                    <button type="button" @click="copyMenuOpen = copyMenuOpen === qi ? null : qi"
                                                        class="text-xs text-gray-400 hover:text-purple-600 transition-colors">
                                                        Copia da...
                                                    </button>
                                                    <div v-if="copyMenuOpen === qi"
                                                        class="absolute right-0 top-5 z-10 bg-white border border-gray-200 rounded-lg shadow-md py-1 min-w-40">
                                                        <button v-for="(other, oi) in form.questions" :key="oi"
                                                            v-show="oi !== qi"
                                                            type="button"
                                                            @click="copyAnswersFrom(qi, oi)"
                                                            class="w-full text-left px-3 py-1.5 text-xs hover:bg-purple-50 text-gray-700 truncate">
                                                            D{{ oi + 1 }}{{ other.text ? ': ' + other.text.slice(0, 30) + (other.text.length > 30 ? '…' : '') : '' }}
                                                        </button>
                                                    </div>
                                                </div>
                                                <button type="button" @click="addAnswer(qi)"
                                                    class="text-xs text-purple-600 hover:underline">+ Aggiungi risposta</button>
                                            </div>
                                        </div>
                                        <div v-for="(ans, ai) in q.answers" :key="ai" class="flex items-center gap-2">
                                            <div class="flex flex-col gap-0.5 shrink-0">
                                                <button type="button" @click="moveAnswer(qi, ai, -1)"
                                                    :disabled="ai === 0"
                                                    class="text-gray-300 hover:text-gray-500 transition-colors disabled:opacity-20">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="moveAnswer(qi, ai, 1)"
                                                    :disabled="ai === q.answers.length - 1"
                                                    class="text-gray-300 hover:text-gray-500 transition-colors disabled:opacity-20">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <input v-model="ans.label" type="text" placeholder="Etichetta risposta"
                                                class="flex-1 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                            <input v-model.number="ans.score" type="number" placeholder="0"
                                                class="w-20 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                            <button type="button" @click="removeAnswer(qi, ai)"
                                                :disabled="q.answers.length <= 1"
                                                class="text-gray-300 hover:text-red-400 transition-colors disabled:opacity-30">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" @click="removeQuestion(qi)"
                                    :disabled="form.questions.length <= 1"
                                    class="text-gray-300 hover:text-red-400 transition-colors shrink-0 disabled:opacity-30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section D: Formula punteggio -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Formula punteggio</h2>

                    <!-- Mode A: flat (no sections) -->
                    <template v-if="!hasSections">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Base di calcolo</label>
                                <select v-model="form.scoring.base"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                                    <option value="sum">Somma punteggi</option>
                                    <option value="average">Media punteggi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Moltiplica per</label>
                                <input v-model.number="form.scoring.multiplier" type="number" step="any" placeholder="—"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dividi per</label>
                                <input v-model.number="form.scoring.divisor" type="number" step="any" min="0.01" placeholder="—"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg px-4 py-2.5 text-sm text-purple-700 font-mono">
                            {{ formulaPreview }}
                        </div>
                    </template>

                    <!-- Mode B: section-based -->
                    <template v-else>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Formula</label>
                            <textarea ref="formulaTextarea" v-model="form.scoring.formula" rows="2"
                                placeholder="Restrizione + Preoccupazione + Valutazione + Forma"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none" />
                            <p class="text-xs text-gray-400 mt-1">Usa i nomi delle sezioni come variabili. Operatori supportati: + - * / ( )</p>
                        </div>

                        <div v-if="form.scoring.sections.some(s => s.name)">
                            <p class="text-xs font-medium text-gray-500 mb-2">Variabili disponibili:</p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="(sec, si) in form.scoring.sections" :key="si"
                                    v-show="sec.name"
                                    type="button"
                                    @click="appendToFormula(sec.name)"
                                    class="text-xs px-2.5 py-1 bg-purple-100 text-purple-700 rounded-full hover:bg-purple-200 transition-colors font-mono">
                                    {{ sec.name }}
                                </button>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg px-4 py-2.5 text-sm text-purple-700 font-mono">
                            {{ formulaPreview }}
                        </div>
                    </template>

                    <!-- Section E: Fasce -->
                    <div class="border-t border-gray-100 pt-5 mt-1">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700">Fasce di interpretazione</label>
                            <button type="button" @click="addThreshold"
                                class="text-xs text-purple-600 hover:underline">+ Aggiungi fascia</button>
                        </div>

                        <div v-if="form.scoring.thresholds.length === 0" class="text-xs text-gray-400 py-3 border border-dashed border-gray-200 rounded-lg text-center">
                            Nessuna fascia definita. Il punteggio verrà mostrato senza interpretazione.
                        </div>

                        <div class="space-y-2">
                            <div v-for="(t, ti) in form.scoring.thresholds" :key="ti"
                                class="flex items-center gap-2 flex-wrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500">Da</span>
                                    <input v-model.number="t.min" type="number"
                                        class="w-16 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500">A</span>
                                    <input v-model.number="t.max" type="number"
                                        class="w-16 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                </div>
                                <input v-model="t.label" type="text" placeholder="Etichetta"
                                    class="flex-1 min-w-24 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                <select v-model="t.color"
                                    class="border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400">
                                    <option value="green">Verde</option>
                                    <option value="yellow">Giallo</option>
                                    <option value="orange">Arancione</option>
                                    <option value="red">Rosso</option>
                                </select>
                                <button type="button" @click="removeThreshold(ti)"
                                    class="text-gray-300 hover:text-red-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('questionnaire-templates.index')"
                        class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                        Annulla
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Salvataggio...' : 'Salva modello' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
