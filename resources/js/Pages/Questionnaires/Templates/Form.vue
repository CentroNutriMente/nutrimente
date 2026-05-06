<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ template: Object });

const isEdit = !!props.template;

const buildQuestions = () => {
    if (!props.template?.questions?.length) return [];
    return props.template.questions.map(q => ({
        text: q.text ?? '',
        answers: q.answers ? q.answers.map(a => ({ label: a.label ?? '', score: a.score ?? 0 })) : [{ label: '', score: 0 }],
    }));
};

const buildScoring = () => {
    const s = props.template?.scoring;
    return {
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

function addQuestion() {
    form.questions.push({ text: '', answers: [{ label: '', score: 0 }] });
}

function removeQuestion(qi) {
    if (form.questions.length <= 1) return;
    form.questions.splice(qi, 1);
}

function addAnswer(qi) {
    form.questions[qi].answers.push({ label: '', score: 0 });
}

function removeAnswer(qi, ai) {
    if (form.questions[qi].answers.length <= 1) return;
    form.questions[qi].answers.splice(ai, 1);
}

function addThreshold() {
    form.scoring.thresholds.push({ min: 0, max: 0, label: '', color: 'green' });
}

function removeThreshold(i) {
    form.scoring.thresholds.splice(i, 1);
}

const formulaPreview = computed(() => {
    const base = form.scoring.base === 'average' ? 'Media' : 'Somma';
    let formula = `Punteggio = ${base}`;
    if (form.scoring.multiplier) {
        formula += ` × ${form.scoring.multiplier}`;
    }
    if (form.scoring.divisor) {
        formula += ` / ${form.scoring.divisor}`;
    }
    return formula;
});

function submit() {
    const payload = {
        name:        form.name,
        description: form.description,
        questions:   form.questions,
        scoring: {
            base:       form.scoring.base,
            divisor:    form.scoring.divisor || null,
            multiplier: form.scoring.multiplier || null,
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

                <!-- Section 1: Template info -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Informazioni generali</h2>

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

                <!-- Section 2: Questions -->
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

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-medium text-gray-600">Risposte</label>
                                            <button type="button" @click="addAnswer(qi)"
                                                class="text-xs text-purple-600 hover:underline">+ Aggiungi risposta</button>
                                        </div>
                                        <div v-for="(ans, ai) in q.answers" :key="ai" class="flex items-center gap-2">
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

                <!-- Section 3: Scoring formula -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Formula punteggio</h2>

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

                    <div>
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
