<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    templates:          Array,
    patient:            Object,
    reports:            Array,
    selectedTemplateId: Number,
    selectedReportId:   Number,
    questionnaire:      Object,
});

const isEdit = !!props.questionnaire;

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    patient_id:                 props.patient.id,
    questionnaire_template_id:  props.questionnaire?.questionnaire_template_id ?? props.selectedTemplateId ?? null,
    report_id:                  props.questionnaire?.report_id ?? props.selectedReportId ?? null,
    filled_at:                  props.questionnaire?.filled_at ? props.questionnaire.filled_at.slice(0, 10) : today,
    answers:                    [],
    notes:                      props.questionnaire?.notes ?? '',
});

const activeTemplate = computed(() =>
    props.templates.find(t => t.id === form.questionnaire_template_id) ?? null
);

function buildAnswers(template) {
    if (!template?.questions) return [];
    return template.questions.map(q => {
        const existing = props.questionnaire?.answers?.find(a => a.question_id === q.id);
        return {
            question_id: q.id,
            answer_id:   existing?.answer_id ?? null,
            score:       existing?.score ?? null,
        };
    });
}

watch(() => form.questionnaire_template_id, () => {
    form.answers = buildAnswers(activeTemplate.value);
}, { immediate: true });

function getAnswer(questionId) {
    return form.answers.find(a => a.question_id === questionId);
}

function setAnswer(questionId, answerId, score) {
    const a = getAnswer(questionId);
    if (a) {
        a.answer_id = answerId;
        a.score     = score;
    }
}

function computeLiveScore(template, answers) {
    const sections = template?.scoring?.sections ?? [];
    const formula  = template?.scoring?.formula ?? '';

    if (sections.length > 0 && formula) {
        const qSection = {};
        for (const q of (template.questions ?? [])) {
            if (q.section_id) qSection[q.id] = q.section_id;
        }

        const buckets = Object.fromEntries(sections.map(s => [s.id, []]));
        for (const a of answers) {
            const sid = qSection[a.question_id];
            if (sid && buckets[sid] !== undefined) buckets[sid].push(Number(a.score));
        }

        const vars = {};
        for (const s of sections) {
            const scores = buckets[s.id] ?? [];
            const n = scores.length;
            const raw = scores.reduce((a, b) => a + b, 0);
            let val = (s.operation === 'average' && n > 0) ? raw / n : raw;
            if (s.multiplier) val *= Number(s.multiplier);
            if (s.divisor && Number(s.divisor) !== 0) val /= Number(s.divisor);
            vars[s.name] = Math.round(val * 1e6) / 1e6;
        }

        let expr = formula;
        const sortedNames = Object.keys(vars).sort((a, b) => b.length - a.length);
        for (const name of sortedNames) expr = expr.replaceAll(name, String(vars[name]));
        if (!/^[\d\s+\-*/().]+$/.test(expr)) return { total: 0, sectionScores: vars };
        try {
            const total = Math.round(Function('"use strict"; return (' + expr + ')')());
            return { total, sectionScores: vars };
        } catch {
            return { total: 0, sectionScores: vars };
        }
    }

    const sum  = answers.reduce((acc, a) => acc + Number(a.score ?? 0), 0);
    const base = template?.scoring?.base ?? 'sum';
    const n    = answers.length;
    let val    = (base === 'average' && n > 0) ? sum / n : sum;
    if (template?.scoring?.multiplier) val *= Number(template.scoring.multiplier);
    if (template?.scoring?.divisor && Number(template.scoring.divisor) !== 0) val /= Number(template.scoring.divisor);
    return { total: Math.round(val), sectionScores: {} };
}

const liveResult = computed(() => {
    const answered = form.answers.filter(a => a.score !== null);
    return computeLiveScore(activeTemplate.value, answered);
});

const liveScore = computed(() => liveResult.value.total);

const liveSectionScores = computed(() => {
    const sections = activeTemplate.value?.scoring?.sections ?? [];
    if (!sections.length) return [];
    return sections.map(s => ({
        name:  s.name,
        score: liveResult.value.sectionScores[s.name] ?? 0,
    }));
});

const liveThreshold = computed(() => {
    const thresholds = activeTemplate.value?.scoring?.thresholds ?? [];
    const score = liveScore.value;
    return thresholds.find(t => score >= t.min && score <= t.max) ?? null;
});

const colorMap = {
    green:  'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    orange: 'bg-orange-100 text-orange-700',
    red:    'bg-red-100 text-red-700',
};

const liveScoreClass = computed(() => {
    if (!liveThreshold.value) return 'bg-purple-50 text-purple-700';
    return colorMap[liveThreshold.value.color] ?? 'bg-purple-50 text-purple-700';
});

const allAnswered = computed(() =>
    activeTemplate.value
        ? form.answers.every(a => a.answer_id !== null)
        : false
);

// Group questions by section for display
const questionGroups = computed(() => {
    if (!activeTemplate.value) return [];
    const sections = activeTemplate.value.scoring?.sections ?? [];
    if (!sections.length) {
        return [{ section: null, questions: activeTemplate.value.questions }];
    }

    const groups = sections.map(s => ({ section: s, questions: [] }));
    const ungrouped = [];

    for (const q of (activeTemplate.value.questions ?? [])) {
        if (q.section_id) {
            const g = groups.find(g => g.section.id === q.section_id);
            if (g) { g.questions.push(q); continue; }
        }
        ungrouped.push(q);
    }

    const result = groups.filter(g => g.questions.length > 0);
    if (ungrouped.length > 0) {
        result.push({ section: { id: '__general', name: 'Generale' }, questions: ungrouped });
    }
    return result;
});

function submit() {
    if (isEdit) {
        form.put(route('questionnaires.update', props.questionnaire.id));
    } else {
        form.post(route('questionnaires.store'));
    }
}

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '';

// Question index across groups (for display numbering)
function questionIndex(groupIndex, questionIndexInGroup) {
    let count = 0;
    for (let i = 0; i < groupIndex; i++) {
        count += questionGroups.value[i].questions.length;
    }
    return count + questionIndexInGroup + 1;
}
</script>

<template>
    <AppLayout :title="isEdit ? 'Modifica questionario' : 'Nuovo questionario'">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.show', patient.id)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">
                        {{ isEdit ? 'Modifica questionario' : 'Nuovo questionario' }}
                    </h1>
                    <p class="text-sm text-gray-400">{{ patient.last_name }} {{ patient.first_name }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">

                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Impostazioni</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seleziona modello <span class="text-red-500">*</span></label>
                        <select v-model="form.questionnaire_template_id" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option :value="null" disabled>Seleziona modello...</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.questionnaire_template_id" class="text-xs text-red-500 mt-1">{{ form.errors.questionnaire_template_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Associa a referto</label>
                        <select v-model="form.report_id"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option :value="null">— Nessun referto —</option>
                            <option v-for="r in reports" :key="r.id" :value="r.id">
                                {{ r.title }} ({{ fmt(r.report_date) }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data somministrazione <span class="text-red-500">*</span></label>
                        <input v-model="form.filled_at" type="date" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                    </div>
                </div>

                <div v-if="activeTemplate" class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-4 gap-4">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Compilazione</h2>
                        <div class="shrink-0 text-right">
                            <div :class="[liveScoreClass, 'text-sm font-semibold px-3 py-1.5 rounded-lg']">
                                Punteggio: {{ liveScore }}
                                <span v-if="liveThreshold" class="ml-1">— {{ liveThreshold.label }}</span>
                            </div>
                            <div v-if="liveSectionScores.length > 0" class="mt-2">
                                <table class="text-xs text-right ml-auto">
                                    <tbody>
                                        <tr v-for="s in liveSectionScores" :key="s.name">
                                            <td class="text-gray-500 pr-2">{{ s.name }}</td>
                                            <td class="font-medium text-gray-700">{{ s.score }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div v-for="(group, gi) in questionGroups" :key="group.section?.id ?? 'flat'">
                            <h3 v-if="group.section" class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                                {{ group.section.name }}
                            </h3>

                            <div class="space-y-4">
                                <div v-for="(q, qi) in group.questions" :key="q.id"
                                    class="border border-gray-100 rounded-lg p-4">
                                    <p class="text-sm font-semibold text-gray-800 mb-3">
                                        <span class="text-purple-500 mr-1">{{ questionIndex(gi, qi) }}.</span>
                                        {{ q.text }}
                                    </p>

                                    <div class="space-y-2">
                                        <label v-for="ans in q.answers" :key="ans.id"
                                            class="flex items-center gap-3 cursor-pointer group">
                                            <input
                                                type="radio"
                                                :name="`q_${q.id}`"
                                                :value="ans.id"
                                                :checked="getAnswer(q.id)?.answer_id === ans.id"
                                                @change="setAnswer(q.id, ans.id, ans.score)"
                                                class="text-purple-600 focus:ring-purple-400"
                                            />
                                            <span class="text-sm text-gray-700 group-hover:text-gray-900 flex-1">{{ ans.label }}</span>
                                            <span class="text-xs text-gray-400">({{ ans.score }})</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                    Seleziona un modello per visualizzare le domande.
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"
                        placeholder="Osservazioni cliniche..." />
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('patients.show', patient.id)"
                        class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                        Annulla
                    </Link>
                    <button type="submit" :disabled="form.processing || !activeTemplate"
                        class="px-5 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Salvataggio...' : 'Salva questionario' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
