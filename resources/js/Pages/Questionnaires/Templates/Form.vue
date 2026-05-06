<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ template: Object });

const isEdit = !!props.template;

const buildQuestions = () => {
    if (!props.template?.questions?.length) return [];
    return props.template.questions.map(q => ({
        text: q.text ?? '',
        type: q.type ?? 'scale',
        options: q.options ? q.options.map(o => ({ label: o.label ?? '', value: o.value ?? 0 })) : [],
    }));
};

const form = useForm({
    name:        props.template?.name ?? '',
    description: props.template?.description ?? '',
    questions:   buildQuestions(),
});

function addQuestion() {
    form.questions.push({ text: '', type: 'scale', options: [{ label: '', value: 0 }] });
}

function removeQuestion(index) {
    form.questions.splice(index, 1);
}

function addOption(qIndex) {
    form.questions[qIndex].options.push({ label: '', value: 0 });
}

function removeOption(qIndex, oIndex) {
    form.questions[qIndex].options.splice(oIndex, 1);
}

function onTypeChange(qIndex) {
    const q = form.questions[qIndex];
    if (q.type === 'scale' && !q.options.length) {
        q.options = [{ label: '', value: 0 }];
    } else if (q.type !== 'scale') {
        q.options = [];
    }
}

function submit() {
    if (isEdit) {
        form.put(route('questionnaire-templates.update', props.template.id));
    } else {
        form.post(route('questionnaire-templates.store'));
    }
}
</script>

<template>
    <AppLayout :title="isEdit ? 'Modifica modello' : 'Nuovo modello questionario'">
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

                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Informazioni generali</h2>

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

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Domande</h2>
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
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Testo della domanda</label>
                                        <textarea v-model="q.text" rows="2" required
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                        <select v-model="q.type" @change="onTypeChange(qi)"
                                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                                            <option value="scale">Scala (punteggio)</option>
                                            <option value="yesno">Si / No</option>
                                            <option value="text">Testo libero</option>
                                        </select>
                                    </div>

                                    <div v-if="q.type === 'scale'">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-xs font-medium text-gray-600">Opzioni</label>
                                            <button type="button" @click="addOption(qi)"
                                                class="text-xs text-purple-600 hover:underline">+ Aggiungi opzione</button>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="(opt, oi) in q.options" :key="oi" class="flex items-center gap-2">
                                                <input v-model="opt.label" type="text" placeholder="Etichetta"
                                                    class="flex-1 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                                <input v-model.number="opt.value" type="number" placeholder="Valore"
                                                    class="w-20 border border-gray-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400" />
                                                <button type="button" @click="removeOption(qi, oi)"
                                                    class="text-gray-300 hover:text-red-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="q.type === 'yesno'" class="flex gap-4">
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">No = 0</span>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Si = 1</span>
                                    </div>
                                </div>

                                <button type="button" @click="removeQuestion(qi)"
                                    class="text-gray-300 hover:text-red-400 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
