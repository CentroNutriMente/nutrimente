<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { reactive, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    template: Object,
});

// ── Reactive template state ────────────────────────────────────────────────────
const tmpl = reactive({
    name:        props.template.name,
    description: props.template.description ?? '',
    content:     JSON.parse(JSON.stringify(props.template.content ?? { sections: [] })),
});

const saving  = ref(false);
const saved   = ref(false);
const saveErr = ref('');

// ── ID generator ───────────────────────────────────────────────────────────────
function uid() {
    return Math.random().toString(36).slice(2, 9) + Date.now().toString(36).slice(-4);
}

// ── Section helpers ────────────────────────────────────────────────────────────
function addSection() {
    tmpl.content.sections.push({ id: uid(), title: 'Nuova sezione', optional: true, items: [] });
}
function removeSection(idx) {
    if (confirm('Rimuovere questa sezione e tutti i suoi punti?')) tmpl.content.sections.splice(idx, 1);
}
function moveSection(idx, dir) {
    const arr = tmpl.content.sections;
    const target = idx + dir;
    if (target < 0 || target >= arr.length) return;
    [arr[idx], arr[target]] = [arr[target], arr[idx]];
}

// ── Item helpers ───────────────────────────────────────────────────────────────
function addItem(section, type) {
    const base = { id: uid(), type, optional: true };
    if (type === 'paragraph')      section.items.push({ ...base, content: '' });
    else if (type === 'field')     section.items.push({ ...base, label: '', placeholder: '', multiline: false });
    else if (type === 'checkbox_group') section.items.push({ ...base, label: '', checkboxes: [] });
}
function removeItem(section, idx) {
    section.items.splice(idx, 1);
}
function moveItem(section, idx, dir) {
    const arr = section.items;
    const target = idx + dir;
    if (target < 0 || target >= arr.length) return;
    [arr[idx], arr[target]] = [arr[target], arr[idx]];
}

// ── Checkbox helpers ───────────────────────────────────────────────────────────
function addCheckbox(item) {
    item.checkboxes.push({ id: uid(), label: '', default_checked: false });
}
function removeCheckbox(item, idx) {
    item.checkboxes.splice(idx, 1);
}

// ── Save ───────────────────────────────────────────────────────────────────────
async function save() {
    saving.value  = true;
    saved.value   = false;
    saveErr.value = '';
    try {
        await axios.put(`/doc-templates/${props.template.id}`, {
            name:        tmpl.name,
            description: tmpl.description,
            content:     tmpl.content,
        });
        saved.value = true;
        setTimeout(() => { saved.value = false; }, 2500);
    } catch (e) {
        saveErr.value = e?.response?.data?.message ?? 'Errore durante il salvataggio.';
    } finally {
        saving.value = false;
    }
}

// Type labels
const typeLabel = { paragraph: 'Testo', field: 'Campo compilabile', checkbox_group: 'Checkbox' };
const typeColor = { paragraph: 'bg-gray-100 text-gray-600', field: 'bg-blue-50 text-blue-600', checkbox_group: 'bg-green-50 text-green-600' };
</script>

<template>
    <AppLayout :title="`Modifica template – ${tmpl.name}`">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="/documents" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h1 class="text-lg font-semibold text-gray-800">Modifica template</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="saved" class="text-xs text-green-600 font-medium">Salvato ✓</span>
                    <span v-if="saveErr" class="text-xs text-red-500">{{ saveErr }}</span>
                    <button @click="save" :disabled="saving"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-5 py-2 rounded-xl transition-colors">
                        {{ saving ? 'Salvataggio…' : 'Salva template' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-5">

            <!-- Template metadata -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nome template</label>
                    <input v-model="tmpl.name" type="text"
                        :disabled="template.is_system"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 disabled:bg-gray-50 disabled:text-gray-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Descrizione</label>
                    <textarea v-model="tmpl.description" rows="2"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                </div>
                <div v-if="template.is_system"
                    class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
                    Questo è un template di sistema. Puoi modificarne il contenuto ma non il nome.
                </div>
            </div>

            <!-- Sections -->
            <div v-for="(section, sIdx) in tmpl.content.sections" :key="section.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                <!-- Section header -->
                <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <div class="flex gap-1">
                        <button @click="moveSection(sIdx, -1)" :disabled="sIdx === 0"
                            class="p-1 rounded hover:bg-gray-200 disabled:opacity-30 transition-colors text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button @click="moveSection(sIdx, 1)" :disabled="sIdx === tmpl.content.sections.length - 1"
                            class="p-1 rounded hover:bg-gray-200 disabled:opacity-30 transition-colors text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <input v-model="section.title" type="text" placeholder="Titolo sezione"
                        class="flex-1 bg-transparent border-0 text-sm font-semibold text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-200 rounded px-1" />
                    <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
                        <input type="checkbox" v-model="section.optional"
                            class="w-3.5 h-3.5 rounded border-gray-300 text-purple-600 focus:ring-purple-400" />
                        opzionale
                    </label>
                    <button @click="removeSection(sIdx)"
                        class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Section intro (optional note) -->
                <div class="px-4 pt-3 pb-0">
                    <input v-model="section.intro" type="text" placeholder="Testo introduttivo della sezione (opzionale)…"
                        class="w-full rounded-lg border border-dashed border-gray-200 px-3 py-1.5 text-xs text-gray-500 placeholder-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-200" />
                </div>

                <!-- Items -->
                <div class="px-4 py-3 space-y-3">
                    <div v-for="(item, iIdx) in section.items" :key="item.id"
                        class="border border-gray-100 rounded-xl p-3 bg-gray-50/50">

                        <!-- Item toolbar -->
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex gap-1">
                                <button @click="moveItem(section, iIdx, -1)" :disabled="iIdx === 0"
                                    class="p-0.5 rounded hover:bg-gray-200 disabled:opacity-30 transition-colors text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button @click="moveItem(section, iIdx, 1)" :disabled="iIdx === section.items.length - 1"
                                    class="p-0.5 rounded hover:bg-gray-200 disabled:opacity-30 transition-colors text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                            <select v-model="item.type"
                                class="text-xs rounded-lg border border-gray-200 px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-purple-300">
                                <option value="paragraph">Testo fisso</option>
                                <option value="field">Campo compilabile</option>
                                <option value="checkbox_group">Checkbox</option>
                            </select>
                            <span :class="typeColor[item.type]" class="text-[10px] px-2 py-0.5 rounded-full font-medium">
                                {{ typeLabel[item.type] }}
                            </span>
                            <label class="flex items-center gap-1 text-[11px] text-gray-400 cursor-pointer ml-auto">
                                <input type="checkbox" v-model="item.optional"
                                    class="w-3 h-3 rounded border-gray-300 text-purple-600" />
                                opzionale
                            </label>
                            <button @click="removeItem(section, iIdx)"
                                class="p-0.5 rounded hover:bg-red-50 text-gray-300 hover:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- PARAGRAPH content -->
                        <textarea v-if="item.type === 'paragraph'"
                            v-model="item.content" rows="3" placeholder="Testo del paragrafo…"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-y bg-white" />

                        <!-- FIELD content -->
                        <template v-else-if="item.type === 'field'">
                            <input v-model="item.label" type="text" placeholder="Etichetta campo (es. Nome e cognome)"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white mb-2" />
                            <input v-model="item.placeholder" type="text" placeholder="Testo suggerimento (placeholder)…"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-500 focus:outline-none focus:ring-1 focus:ring-purple-200 bg-white mb-2" />
                            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" v-model="item.multiline"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-purple-600" />
                                Campo multiriga (textarea)
                            </label>
                        </template>

                        <!-- CHECKBOX GROUP content -->
                        <template v-else-if="item.type === 'checkbox_group'">
                            <input v-model="item.label" type="text" placeholder="Titolo gruppo checkbox…"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white mb-3" />
                            <div v-for="(cb, cIdx) in item.checkboxes" :key="cb.id" class="flex items-center gap-2 mb-2">
                                <input type="checkbox" v-model="cb.default_checked"
                                    class="w-4 h-4 rounded border-gray-300 text-purple-600 shrink-0" title="Selezionato di default" />
                                <input v-model="cb.label" type="text" placeholder="Testo del checkbox…"
                                    class="flex-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-200 bg-white" />
                                <button @click="removeCheckbox(item, cIdx)"
                                    class="text-gray-300 hover:text-red-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <button @click="addCheckbox(item)"
                                class="text-xs text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1 mt-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Aggiungi checkbox
                            </button>
                        </template>
                    </div>

                    <!-- Add item buttons -->
                    <div class="flex items-center gap-2 pt-1">
                        <span class="text-xs text-gray-400">Aggiungi punto:</span>
                        <button @click="addItem(section, 'paragraph')"
                            class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors font-medium">
                            Testo fisso
                        </button>
                        <button @click="addItem(section, 'field')"
                            class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors font-medium">
                            Campo compilabile
                        </button>
                        <button @click="addItem(section, 'checkbox_group')"
                            class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors font-medium">
                            Checkbox
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add section -->
            <button @click="addSection"
                class="w-full bg-white border-2 border-dashed border-gray-200 hover:border-purple-300 text-gray-400 hover:text-purple-600 rounded-2xl py-4 text-sm font-medium transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Aggiungi sezione
            </button>

            <div class="pb-8" />
        </div>
    </AppLayout>
</template>
