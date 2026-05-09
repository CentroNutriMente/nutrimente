<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    questionnaire: Object,
    canEdit:       Boolean,
});

const q    = props.questionnaire;
const tmpl = q.template;

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';

function getAnswerForQuestion(qId) {
    return q.answers?.find(a => a.question_id === qId) ?? null;
}

function getAnswerLabel(question, answerId) {
    if (!answerId) return '—';
    const ans = question.answers?.find(a => a.id === answerId);
    return ans ? ans.label : '—';
}

const colorMap = {
    'green-light':  'bg-green-50 text-green-600',
    'green':        'bg-green-100 text-green-700',
    'yellow-light': 'bg-yellow-50 text-yellow-600',
    'yellow':       'bg-yellow-100 text-yellow-700',
    'orange-light': 'bg-orange-50 text-orange-600',
    'orange':       'bg-orange-100 text-orange-700',
    'orange-dark':  'bg-orange-200 text-orange-800',
    'red':          'bg-red-100 text-red-700',
};

function computeSectionScores() {
    const sections = tmpl?.scoring?.sections ?? [];
    const formula  = tmpl?.scoring?.formula ?? '';
    if (!sections.length || !formula) return [];

    const qSection = {};
    for (const question of (tmpl.questions ?? [])) {
        if (question.section_id) qSection[question.id] = question.section_id;
    }

    const buckets = Object.fromEntries(sections.map(s => [s.id, []]));
    for (const a of (q.answers ?? [])) {
        const sid = qSection[a.question_id];
        if (sid && buckets[sid] !== undefined) buckets[sid].push(Number(a.score));
    }

    return sections.map(s => {
        const scores = buckets[s.id] ?? [];
        const n = scores.length;
        const raw = scores.reduce((a, b) => a + b, 0);
        let val = (s.operation === 'average' && n > 0) ? raw / n : raw;
        if (s.multiplier) val *= Number(s.multiplier);
        if (s.divisor && Number(s.divisor) !== 0) val /= Number(s.divisor);
        return { name: s.name, score: Math.round(val * 1e6) / 1e6 };
    });
}

const sectionScores = computeSectionScores();
const hasSections   = sectionScores.length > 0;

function getScoreInfo() {
    const thresholds = tmpl?.scoring?.thresholds ?? [];
    const score      = q.total_score;
    const match      = thresholds.find(t => score >= t.min && score <= t.max);
    if (!match) return { label: null, colorClass: 'bg-gray-100 text-gray-600' };
    return {
        label:      match.label,
        colorClass: colorMap[match.color] ?? 'bg-gray-100 text-gray-600',
    };
}

const scoreInfo = getScoreInfo();

const sectionById = Object.fromEntries(
    (tmpl?.scoring?.sections ?? []).map(s => [s.id, s])
);

function destroy() {
    if (!confirm('Eliminare questo questionario?')) return;
    router.delete(route('questionnaires.destroy', q.id));
}
</script>

<template>
    <AppLayout :title="tmpl?.name ?? 'Questionario'">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.show', q.patient_id)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold text-gray-800">{{ tmpl?.name }}</h1>
                    <p class="text-sm text-gray-400">{{ q.patient?.last_name }} {{ q.patient?.first_name }} · {{ fmt(q.filled_at) }}</p>
                </div>
                <div v-if="canEdit" class="flex gap-2">
                    <Link :href="route('questionnaires.edit', q.id)"
                        class="px-3 py-2 border border-purple-200 text-purple-600 rounded-lg text-sm font-medium hover:bg-purple-50 transition-colors">
                        Modifica
                    </Link>
                    <button @click="destroy"
                        class="px-3 py-2 border border-red-200 text-red-500 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                        Elimina
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-3xl space-y-6">

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ q.patient?.last_name }} {{ q.patient?.first_name }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ fmt(q.filled_at) }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Compilato da {{ q.user?.name }}
                        </div>
                        <div v-if="q.report" class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Referto:
                            <Link :href="route('reports.show', q.report.id)" class="text-teal-600 hover:underline font-medium">
                                {{ q.report.title }}
                            </Link>
                        </div>
                    </div>

                    <div class="text-center shrink-0">
                        <div :class="[scoreInfo.colorClass, 'text-3xl font-bold px-6 py-4 rounded-xl']">
                            {{ q.total_score }}
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Punteggio totale</p>
                        <p v-if="scoreInfo.label" class="text-xs font-medium mt-0.5" :class="scoreInfo.colorClass.split(' ')[1]">
                            {{ scoreInfo.label }}
                        </p>
                    </div>
                </div>

                <!-- Section scores table -->
                <div v-if="hasSections" class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Punteggi per sezione</p>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="s in sectionScores" :key="s.name">
                                <td class="py-1.5 text-gray-600">{{ s.name }}</td>
                                <td class="py-1.5 text-right font-semibold text-gray-800">{{ s.score }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="q.notes" class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Note</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ q.notes }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Risposte</h2>
                <div class="space-y-4">
                    <div v-for="(question, qi) in tmpl?.questions ?? []" :key="question.id"
                        class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <p class="text-sm font-medium text-gray-800">
                                <span class="text-purple-500 font-semibold mr-1">{{ qi + 1 }}.</span>
                                {{ question.text }}
                            </p>
                            <span v-if="question.section_id && sectionById[question.section_id]"
                                class="text-xs text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full shrink-0 font-medium whitespace-nowrap">
                                {{ sectionById[question.section_id].name }}
                            </span>
                        </div>

                        <div class="space-y-1.5">
                            <div v-for="ans in question.answers" :key="ans.id"
                                :class="[
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                    getAnswerForQuestion(question.id)?.answer_id === ans.id
                                        ? 'bg-purple-50 border border-purple-200'
                                        : 'text-gray-400'
                                ]">
                                <span :class="getAnswerForQuestion(question.id)?.answer_id === ans.id ? 'text-purple-700 font-medium' : ''">
                                    {{ ans.label }}
                                </span>
                                <span v-if="getAnswerForQuestion(question.id)?.answer_id === ans.id"
                                    class="ml-auto text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full font-medium">
                                    {{ ans.score }} pt
                                </span>
                                <span v-else class="ml-auto text-xs text-gray-300">{{ ans.score }}</span>
                            </div>
                        </div>

                        <div v-if="!getAnswerForQuestion(question.id)" class="text-xs text-gray-400 mt-2">
                            Nessuna risposta registrata
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
