<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
    if (!template) return [];
    return template.questions.map(q => {
        const existing = props.questionnaire?.answers?.find(a => a.question_id === q.id);
        return {
            question_id: q.id,
            value:       existing?.value ?? null,
            text:        existing?.text ?? null,
        };
    });
}

watch(() => form.questionnaire_template_id, () => {
    form.answers = buildAnswers(activeTemplate.value);
}, { immediate: true });

const totalScore = computed(() =>
    form.answers.reduce((sum, a) => {
        const v = parseInt(a.value);
        return sum + (isNaN(v) ? 0 : v);
    }, 0)
);

function getAnswer(questionId) {
    return form.answers.find(a => a.question_id === questionId);
}

function setAnswerValue(questionId, value) {
    const a = getAnswer(questionId);
    if (a) a.value = value;
}

function setAnswerText(questionId, text) {
    const a = getAnswer(questionId);
    if (a) a.text = text;
}

function submit() {
    if (isEdit) {
        form.put(route('questionnaires.update', props.questionnaire.id));
    } else {
        form.post(route('questionnaires.store'));
    }
}

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '';
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
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Impostazioni</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modello <span class="text-red-500">*</span></label>
                            <select v-model="form.questionnaire_template_id" required
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                                <option :value="null" disabled>Seleziona modello...</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <p v-if="form.errors.questionnaire_template_id" class="text-xs text-red-500 mt-1">{{ form.errors.questionnaire_template_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data compilazione <span class="text-red-500">*</span></label>
                            <input v-model="form.filled_at" type="date" required
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referto collegato</label>
                        <select v-model="form.report_id"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option :value="null">Nessun referto</option>
                            <option v-for="r in reports" :key="r.id" :value="r.id">
                                {{ r.title }} ({{ fmt(r.report_date) }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="2"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none" />
                    </div>
                </div>

                <div v-if="activeTemplate" class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Domande</h2>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Punteggio totale:</span>
                            <span class="text-sm font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full">{{ totalScore }}</span>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div v-for="(q, qi) in activeTemplate.questions" :key="q.id" class="border border-gray-100 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-800 mb-3">
                                <span class="text-purple-500 font-semibold mr-1">{{ qi + 1 }}.</span>
                                {{ q.text }}
                            </p>

                            <div v-if="q.type === 'scale'" class="space-y-2">
                                <label v-for="opt in q.options" :key="opt.value"
                                    class="flex items-center gap-3 cursor-pointer group">
                                    <input
                                        type="radio"
                                        :name="`q_${q.id}`"
                                        :value="opt.value"
                                        :checked="getAnswer(q.id)?.value === opt.value"
                                        @change="setAnswerValue(q.id, opt.value)"
                                        class="text-purple-600 focus:ring-purple-400"
                                    />
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ opt.label }}</span>
                                    <span class="text-xs text-gray-400 ml-auto">({{ opt.value }})</span>
                                </label>
                            </div>

                            <div v-else-if="q.type === 'yesno'" class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        :name="`q_${q.id}`"
                                        :value="0"
                                        :checked="getAnswer(q.id)?.value === 0"
                                        @change="setAnswerValue(q.id, 0)"
                                        class="text-purple-600 focus:ring-purple-400"
                                    />
                                    <span class="text-sm text-gray-700">No</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        :name="`q_${q.id}`"
                                        :value="1"
                                        :checked="getAnswer(q.id)?.value === 1"
                                        @change="setAnswerValue(q.id, 1)"
                                        class="text-purple-600 focus:ring-purple-400"
                                    />
                                    <span class="text-sm text-gray-700">Si</span>
                                </label>
                            </div>

                            <div v-else-if="q.type === 'text'">
                                <textarea
                                    :value="getAnswer(q.id)?.text ?? ''"
                                    @input="setAnswerText(q.id, $event.target.value)"
                                    rows="2"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none"
                                    placeholder="Risposta libera..."
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                    Seleziona un modello per visualizzare le domande.
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
