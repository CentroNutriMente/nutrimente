<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    template: Object, // null = create mode
});

const isEdit = computed(() => !!props.template);

const form = useForm({
    name:                     props.template?.name ?? '',
    description:              props.template?.description ?? '',
    header_title:             props.template?.header_title ?? '',
    header_subtitle:          props.template?.header_subtitle ?? '',
    header_logo:              null,
    sections:                 props.template?.sections ?? [
        { id: uid(), label: 'STATO ATTUALE', type: 'textarea', placeholder: '', required: false },
        { id: uid(), label: 'CONCLUSIONI',   type: 'textarea', placeholder: '', required: false },
    ],
    show_patient_fields:      props.template?.show_patient_fields ?? true,
    show_professional_footer: props.template?.show_professional_footer ?? true,
    footer_note:              props.template?.footer_note ?? '',
    is_default:               props.template?.is_default ?? false,
});

function uid() {
    return Math.random().toString(36).slice(2, 9);
}

// Sezioni
function addSection() {
    form.sections.push({ id: uid(), label: '', type: 'textarea', placeholder: '', required: false });
}
function removeSection(i) {
    form.sections.splice(i, 1);
}
function moveUp(i) {
    if (i === 0) return;
    [form.sections[i - 1], form.sections[i]] = [form.sections[i], form.sections[i - 1]];
}
function moveDown(i) {
    if (i === form.sections.length - 1) return;
    [form.sections[i], form.sections[i + 1]] = [form.sections[i + 1], form.sections[i]];
}

function submit() {
    if (isEdit.value) {
        form.put(route('report-templates.update', props.template.id));
    } else {
        form.post(route('report-templates.store'));
    }
}

const sectionTypes = [
    { value: 'textarea', label: 'Testo libero (area)' },
    { value: 'text',     label: 'Campo breve (riga)' },
    { value: 'date',     label: 'Data' },
];
</script>

<template>
    <AppLayout :title="isEdit ? 'Modifica modello' : 'Nuovo modello'">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('report-templates.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ isEdit ? 'Modifica modello' : 'Nuovo modello di referto' }}
                </h1>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">

            <!-- Colonna principale -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Info modello -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Informazioni modello</h2>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nome del modello *</label>
                        <input v-model="form.name" type="text"
                            placeholder="es. Scheda Primo Colloquio"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione breve</label>
                        <input v-model="form.description" type="text"
                            placeholder="Descrizione opzionale..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                </div>

                <!-- Header del referto -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Intestazione referto</h2>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Titolo principale *</label>
                        <input v-model="form.header_title" type="text"
                            placeholder="es. SCHEDA PRIMO COLLOQUIO"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-semibold uppercase focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        <p v-if="form.errors.header_title" class="text-red-500 text-xs mt-1">{{ form.errors.header_title }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sottotitolo</label>
                        <input v-model="form.header_subtitle" type="text"
                            placeholder="es. Referto di colloquio clinico psicologico"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Logo / immagine header</label>
                        <input type="file" accept="image/*"
                            @change="e => form.header_logo = e.target.files[0]"
                            class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:border file:border-gray-200 file:rounded-lg file:text-xs file:bg-gray-50 file:text-gray-600 hover:file:bg-gray-100" />
                        <p v-if="template?.header_logo" class="text-xs text-gray-400 mt-1">Logo attuale salvato. Carica un nuovo file per sostituirlo.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="button" @click="form.show_patient_fields = !form.show_patient_fields"
                            :class="form.show_patient_fields ? 'bg-purple-600' : 'bg-gray-200'"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0">
                            <span :class="form.show_patient_fields ? 'translate-x-5' : 'translate-x-1'"
                                class="inline-block h-3 w-3 bg-white rounded-full transition-transform" />
                        </button>
                        <span class="text-sm text-gray-600">Mostra nome paziente e data in intestazione</span>
                    </div>
                </div>

                <!-- Sezioni configurabili -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Sezioni del referto</h2>
                        <button type="button" @click="addSection"
                            class="text-sm text-purple-600 hover:underline">
                            + Aggiungi sezione
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(sec, i) in form.sections" :key="sec.id"
                            class="border border-gray-100 rounded-xl p-4 bg-gray-50 space-y-3">

                            <div class="flex items-center gap-2">
                                <!-- Frecce ordine -->
                                <div class="flex flex-col gap-0.5 shrink-0">
                                    <button type="button" @click="moveUp(i)"
                                        :disabled="i === 0"
                                        class="p-0.5 text-gray-400 hover:text-gray-600 disabled:opacity-20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="moveDown(i)"
                                        :disabled="i === form.sections.length - 1"
                                        class="p-0.5 text-gray-400 hover:text-gray-600 disabled:opacity-20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Numero -->
                                <span class="text-xs font-mono text-gray-400 w-5 shrink-0">{{ i + 1 }}.</span>

                                <!-- Label sezione -->
                                <input v-model="sec.label" type="text"
                                    placeholder="Nome sezione (es. STATO ATTUALE)"
                                    class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-semibold uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white" />

                                <!-- Tipo -->
                                <select v-model="sec.type"
                                    class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                                    <option v-for="t in sectionTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>

                                <!-- Rimuovi -->
                                <button type="button" @click="removeSection(i)"
                                    :disabled="form.sections.length === 1"
                                    class="p-1 text-gray-300 hover:text-red-400 disabled:opacity-20 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Placeholder -->
                            <input v-model="sec.placeholder" type="text"
                                placeholder="Testo guida opzionale per il compilatore..."
                                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white" />

                            <!-- Required -->
                            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" v-model="sec.required" class="rounded border-gray-300 text-purple-600" />
                                Campo obbligatorio
                            </label>
                        </div>
                    </div>

                    <p v-if="form.errors.sections" class="text-red-500 text-xs mt-2">{{ form.errors.sections }}</p>
                </div>

                <!-- Pulsanti -->
                <div class="flex gap-3">
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Salvataggio...' : (isEdit ? 'Aggiorna modello' : 'Crea modello') }}
                    </button>
                    <Link :href="route('report-templates.index')"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Annulla
                    </Link>
                </div>
            </div>

            <!-- Sidebar opzioni -->
            <div class="space-y-4">

                <!-- Footer -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h3 class="font-medium text-gray-700 text-sm">Piè di pagina</h3>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="form.show_professional_footer = !form.show_professional_footer"
                            :class="form.show_professional_footer ? 'bg-purple-600' : 'bg-gray-200'"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0">
                            <span :class="form.show_professional_footer ? 'translate-x-5' : 'translate-x-1'"
                                class="inline-block h-3 w-3 bg-white rounded-full transition-transform" />
                        </button>
                        <span class="text-xs text-gray-600">Mostra firma e dati professionista</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nota footer</label>
                        <textarea v-model="form.footer_note" rows="2"
                            placeholder="Nota aggiuntiva in fondo al referto..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" />
                    </div>
                </div>

                <!-- Opzioni -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-700 text-sm mb-3">Opzioni</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <button type="button" @click="form.is_default = !form.is_default"
                            :class="form.is_default ? 'bg-purple-600' : 'bg-gray-200'"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0">
                            <span :class="form.is_default ? 'translate-x-5' : 'translate-x-1'"
                                class="inline-block h-3 w-3 bg-white rounded-full transition-transform" />
                        </button>
                        <span class="text-xs text-gray-600">Modello predefinito (proposto automaticamente)</span>
                    </label>
                </div>

                <!-- Anteprima sezioni -->
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-500 text-xs uppercase tracking-wide mb-3">Anteprima struttura</h3>
                    <div class="space-y-2">
                        <div v-if="form.show_patient_fields"
                            class="text-xs text-gray-400 italic">— Paziente, data, C.F. —</div>
                        <div v-for="sec in form.sections" :key="sec.id"
                            class="text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-dashed border-gray-200 pb-1">
                            {{ sec.label || '(sezione senza nome)' }}
                        </div>
                        <div v-if="form.show_professional_footer"
                            class="text-xs text-gray-400 italic pt-1">— Firma professionista —</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
