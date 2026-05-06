<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({ questionnaire: Object });

const authUserId = usePage().props.auth.user.id;
const isAuthor = props.questionnaire.user_id === authUserId;

const q    = props.questionnaire;
const tmpl = q.template;

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';

function getAnswerForQuestion(qId) {
    return q.answers?.find(a => a.question_id === qId) ?? null;
}

function getOptionLabel(question, value) {
    if (value === null || value === undefined) return '—';
    if (question.type === 'yesno') return value === 1 ? 'Si' : 'No';
    const opt = question.options?.find(o => o.value === value);
    return opt ? opt.label : String(value);
}

const maxScore = (() => {
    if (!tmpl?.questions) return 0;
    return tmpl.questions.reduce((sum, question) => {
        if (question.type === 'text') return sum;
        if (question.type === 'yesno') return sum + 1;
        if (question.type === 'scale') {
            const max = Math.max(...(question.options?.map(o => o.value) ?? [0]));
            return sum + max;
        }
        return sum;
    }, 0);
})();

const scoreClass = (() => {
    if (maxScore === 0) return 'bg-gray-100 text-gray-600';
    const pct = q.total_score / maxScore;
    if (pct <= 0.33) return 'bg-green-100 text-green-700';
    if (pct <= 0.66) return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
})();

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
                <div v-if="isAuthor" class="flex gap-2">
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
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ q.patient?.last_name }} {{ q.patient?.first_name }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ fmt(q.filled_at) }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Compilato da {{ q.user?.name }}
                        </div>
                        <div v-if="q.report" class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Referto:
                            <Link :href="route('reports.show', q.report.id)"
                                class="text-teal-600 hover:underline font-medium">
                                {{ q.report.title }}
                            </Link>
                        </div>
                    </div>

                    <div class="text-center shrink-0">
                        <div :class="[scoreClass, 'text-2xl font-bold px-5 py-3 rounded-xl']">
                            {{ q.total_score }}
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Punteggio totale</p>
                        <p v-if="maxScore > 0" class="text-xs text-gray-400">su {{ maxScore }}</p>
                    </div>
                </div>

                <div v-if="q.notes" class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Note</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ q.notes }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Risposte</h2>
                <div class="space-y-4">
                    <div v-for="(question, qi) in tmpl?.questions ?? []" :key="question.id"
                        class="border border-gray-100 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-800 mb-2">
                            <span class="text-purple-500 font-semibold mr-1">{{ qi + 1 }}.</span>
                            {{ question.text }}
                        </p>

                        <div v-if="question.type === 'text'" class="text-sm text-gray-600 bg-gray-50 rounded px-3 py-2">
                            {{ getAnswerForQuestion(question.id)?.text || '—' }}
                        </div>
                        <div v-else class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-800">
                                {{ getOptionLabel(question, getAnswerForQuestion(question.id)?.value) }}
                            </span>
                            <span v-if="getAnswerForQuestion(question.id)?.value !== null && getAnswerForQuestion(question.id)?.value !== undefined"
                                class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">
                                {{ getAnswerForQuestion(question.id)?.value }} pt
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
