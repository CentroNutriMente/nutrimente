<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    templates:        Array,
    patients:         Array,
    selectedTemplate: Object,
    selectedPatient:  [Number, String, null],
    report:           Object,  // presente in edit mode
});

const isEdit = computed(() => !!props.report);

// Template selezionato
const activeTemplate = ref(props.selectedTemplate ?? null);

function buildSections(tmpl) {
    if (!tmpl) return [{ label: 'Note', content: '' }];
    return tmpl.sections.map(s => ({ label: s.label, content: '' }));
}

function prefillSections() {
    // In edit mode usa le sezioni salvate; in create mode usa la struttura del template
    if (isEdit.value) return props.report.sections_data;
    return buildSections(activeTemplate.value);
}

const form = useForm({
    patient_id:         props.selectedPatient ?? (props.report?.patient_id ?? ''),
    report_template_id: activeTemplate.value?.id ?? (props.report?.report_template_id ?? null),
    title:              props.report?.title ?? (activeTemplate.value?.name ?? ''),
    report_date:        props.report?.report_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10),
    sections_data:      prefillSections(),
    next_appointment:   props.report?.next_appointment ?? '',
    notes:              props.report?.notes ?? '',
});

// Cambio template
function selectTemplate(tmpl) {
    activeTemplate.value = tmpl;
    form.report_template_id = tmpl?.id ?? null;
    form.title = tmpl?.name ?? '';
    form.sections_data = buildSections(tmpl);
}

function submit() {
    if (isEdit.value) {
        form.put(route('reports.update', props.report.id));
    } else {
        form.post(route('reports.store'));
    }
}

const showTemplatePicker = ref(!isEdit.value && !props.selectedTemplate && props.templates.length > 0);
</script>

<template>
    <AppLayout :title="isEdit ? 'Modifica referto' : 'Nuovo referto'">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="isEdit ? route('reports.show', report.id) : route('reports.index')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ isEdit ? 'Modifica referto' : 'Nuovo referto' }}
                </h1>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
            <div class="lg:col-span-2 space-y-5">

                <!-- Selezione modello (solo in create) -->
                <div v-if="!isEdit && templates.length > 0"
                    class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-gray-700 text-sm">Modello</h2>
                        <Link :href="route('report-templates.create')"
                            class="text-xs text-purple-600 hover:underline">
                            + Crea modello
                        </Link>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <button type="button"
                            @click="selectTemplate(null)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors',
                                !activeTemplate
                                    ? 'bg-purple-600 text-white border-purple-600'
                                    : 'border-gray-200 text-gray-600 hover:border-purple-300'
                            ]">
                            Nessun modello
                        </button>
                        <button v-for="tmpl in templates" :key="tmpl.id"
                            type="button"
                            @click="selectTemplate(tmpl)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors flex items-center gap-1.5',
                                activeTemplate?.id === tmpl.id
                                    ? 'bg-purple-600 text-white border-purple-600'
                                    : 'border-gray-200 text-gray-600 hover:border-purple-300'
                            ]">
                            <span v-if="tmpl.is_default" class="text-yellow-300 text-xs">★</span>
                            {{ tmpl.name }}
                        </button>
                    </div>

                    <!-- Anteprima struttura template selezionato -->
                    <div v-if="activeTemplate" class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-1 font-medium">{{ activeTemplate.header_title }}</p>
                        <div class="flex gap-1.5 flex-wrap">
                            <span v-for="sec in activeTemplate.sections" :key="sec.id"
                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                {{ sec.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Nessun modello disponibile -->
                <div v-if="!isEdit && templates.length === 0"
                    class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Nessun modello disponibile.
                        <Link :href="route('report-templates.create')" class="underline">Crea il primo modello</Link>
                        per velocizzare la compilazione.
                    </span>
                </div>

                <!-- Dati referto -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Dati referto</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Paziente *</label>
                            <select v-model="form.patient_id"
                                :disabled="isEdit"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">Seleziona paziente...</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">
                                    {{ p.last_name }} {{ p.first_name }}
                                </option>
                            </select>
                            <p v-if="form.errors.patient_id" class="text-red-500 text-xs mt-1">{{ form.errors.patient_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Titolo referto *</label>
                            <input v-model="form.title" type="text"
                                placeholder="es. Seduta n.3 – Primo colloquio"
                                class="w-full border border-gray-200 rounded-lg px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Data referto *</label>
                            <input v-model="form.report_date" type="date"
                                class="w-full border border-gray-200 rounded-lg px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        </div>
                    </div>
                </div>

                <!-- Sezioni compilabili -->
                <div class="space-y-4">
                    <div v-for="(sec, i) in form.sections_data" :key="i"
                        class="bg-white rounded-xl border border-gray-200 p-6">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">
                            {{ sec.label }}
                            <span v-if="activeTemplate?.sections?.[i]?.required" class="text-red-400 ml-1">*</span>
                        </label>

                        <!-- Textarea o input in base al tipo del template -->
                        <textarea v-if="!activeTemplate || activeTemplate.sections[i]?.type !== 'text'"
                            v-model="sec.content"
                            rows="5"
                            :placeholder="activeTemplate?.sections?.[i]?.placeholder || ''"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-y" />
                        <input v-else
                            v-model="sec.content"
                            type="text"
                            :placeholder="activeTemplate?.sections?.[i]?.placeholder || ''"
                            class="w-full border border-gray-200 rounded-lg px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                </div>

                <!-- Prossimo appuntamento + note -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Prossimo Appuntamento</label>
                        <input v-model="form.next_appointment" type="text"
                            placeholder="es. 20 maggio 2026 ore 15:00"
                            class="w-full border border-gray-200 rounded-lg px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Note interne (non nel PDF)</label>
                        <textarea v-model="form.notes" rows="2"
                            placeholder="Annotazioni private, non incluse nel referto stampabile..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" />
                    </div>
                </div>

                <!-- Azioni -->
                <div class="flex gap-3 pb-10">
                    <button type="button" @click="submit" :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Salvataggio...' : (isEdit ? 'Aggiorna referto' : 'Salva referto') }}
                    </button>
                    <Link :href="isEdit ? route('reports.show', report.id) : route('reports.index')"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Annulla
                    </Link>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Info modello attivo -->
                <div v-if="activeTemplate" class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-700 text-sm mb-2">Modello attivo</h3>
                    <p class="text-sm font-semibold text-gray-800">{{ activeTemplate.name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ activeTemplate.header_title }}</p>
                    <div class="mt-3 text-xs text-gray-400 space-y-0.5">
                        <div>{{ activeTemplate.sections.length }} sezioni</div>
                        <div v-if="activeTemplate.show_patient_fields">✓ Dati paziente in intestazione</div>
                        <div v-if="activeTemplate.show_professional_footer">✓ Firma professionista</div>
                    </div>
                    <Link :href="route('report-templates.edit', activeTemplate.id)"
                        class="block mt-3 text-xs text-purple-600 hover:underline">
                        Modifica modello →
                    </Link>
                </div>

                <!-- Avviso segreto professionale -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-700 leading-relaxed">
                    I referti clinici sono soggetti al segreto professionale (art. 622 c.p.). Tratta i dati nel rispetto del GDPR e del consenso del paziente.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
