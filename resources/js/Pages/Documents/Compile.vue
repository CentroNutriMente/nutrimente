<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const props = defineProps({
    template: Object,
});

// ── Build compile state ────────────────────────────────────────────────────────
const state = reactive({
    title: '',
    sections: (props.template.content?.sections ?? []).map(section => ({
        id:       section.id,
        included: !section.optional,
        items: (section.items ?? []).map(item => ({
            id:         item.id,
            included:   !item.optional,
            value:      '',
            checkboxes: (item.checkboxes ?? []).map(cb => ({
                id:      cb.id,
                checked: cb.default_checked ?? false,
            })),
        })),
    })),
});

// Lookup helpers
function sectionDef(id) { return props.template.content.sections.find(s => s.id === id); }
function itemDef(sid, iid) { return sectionDef(sid)?.items?.find(i => i.id === iid); }
function checkboxDef(sid, iid, cid) { return itemDef(sid, iid)?.checkboxes?.find(c => c.id === cid); }
function stateSection(id) { return state.sections.find(s => s.id === id); }
function stateItem(sid, iid) { return stateSection(sid)?.items?.find(i => i.id === iid); }

// ── Submit ─────────────────────────────────────────────────────────────────────
const sending = reactive({ loading: false });

function generate() {
    if (!state.title.trim()) { alert('Inserisci un titolo per il documento.'); return; }
    sending.loading = true;
    router.post(`/doc-templates/${props.template.id}/generate`, {
        title:    state.title,
        sections: state.sections,
    }, {
        onFinish: () => { sending.loading = false; },
    });
}
</script>

<template>
    <AppLayout :title="`Compila – ${template.name}`">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="/documents" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-800 leading-tight">Compila documento</h1>
                        <p class="text-xs text-gray-400">{{ template.name }}</p>
                    </div>
                </div>
                <button @click="generate" :disabled="sending.loading || !state.title.trim()"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-5 py-2 rounded-xl transition-colors">
                    {{ sending.loading ? 'Generazione…' : 'Genera PDF' }}
                </button>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-5">

            <!-- Document title -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Titolo documento</label>
                <input v-model="state.title" type="text" placeholder="Es. Consenso informato – Mario Rossi – 16/04/2026"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
            </div>

            <!-- Sections -->
            <div v-for="sState in state.sections" :key="sState.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100"
                    :class="sectionDef(sState.id)?.optional ? 'bg-gray-50' : 'bg-purple-50/60'">
                    <!-- Toggle (only for optional sections) -->
                    <button v-if="sectionDef(sState.id)?.optional"
                        @click="sState.included = !sState.included"
                        :class="sState.included ? 'bg-purple-600' : 'bg-gray-200'"
                        class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors focus:outline-none">
                        <span :class="sState.included ? 'translate-x-4' : 'translate-x-0.5'"
                            class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transition-transform" />
                    </button>
                    <span v-else class="w-2 h-2 rounded-full bg-purple-400 shrink-0" />
                    <span class="font-semibold text-sm text-gray-800">{{ sectionDef(sState.id)?.title }}</span>
                    <span v-if="sectionDef(sState.id)?.optional && !sState.included"
                        class="ml-auto text-xs text-gray-400 italic">escluso dal PDF</span>
                </div>

                <div v-if="sState.included" class="px-5 py-4 space-y-5">
                    <!-- Section intro -->
                    <p v-if="sectionDef(sState.id)?.intro" class="text-xs text-gray-500 italic border-l-2 border-purple-200 pl-3">
                        {{ sectionDef(sState.id).intro }}
                    </p>

                    <!-- Items -->
                    <div v-for="iState in sState.items" :key="iState.id" class="relative">
                        <div class="flex items-start gap-2">
                            <!-- Item toggle (optional items) -->
                            <button v-if="itemDef(sState.id, iState.id)?.optional"
                                @click="iState.included = !iState.included"
                                :class="iState.included ? 'bg-purple-500' : 'bg-gray-200'"
                                class="mt-0.5 relative inline-flex h-4 w-7 shrink-0 rounded-full transition-colors focus:outline-none">
                                <span :class="iState.included ? 'translate-x-3.5' : 'translate-x-0.5'"
                                    class="inline-block h-3 w-3 mt-0.5 rounded-full bg-white shadow transition-transform" />
                            </button>
                            <span v-else class="mt-1.5 w-1.5 h-1.5 rounded-full bg-gray-300 shrink-0" />

                            <div class="flex-1 min-w-0" :class="{ 'opacity-40 pointer-events-none': !iState.included }">

                                <!-- PARAGRAPH -->
                                <template v-if="itemDef(sState.id, iState.id)?.type === 'paragraph'">
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        {{ itemDef(sState.id, iState.id).content }}
                                    </p>
                                </template>

                                <!-- FIELD -->
                                <template v-else-if="itemDef(sState.id, iState.id)?.type === 'field'">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        {{ itemDef(sState.id, iState.id).label }}
                                    </label>
                                    <textarea v-if="itemDef(sState.id, iState.id).multiline"
                                        v-model="iState.value" rows="3"
                                        :placeholder="itemDef(sState.id, iState.id).placeholder"
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-y" />
                                    <input v-else
                                        v-model="iState.value" type="text"
                                        :placeholder="itemDef(sState.id, iState.id).placeholder"
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                                </template>

                                <!-- CHECKBOX GROUP -->
                                <template v-else-if="itemDef(sState.id, iState.id)?.type === 'checkbox_group'">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">
                                        {{ itemDef(sState.id, iState.id).label }}
                                    </p>
                                    <label v-for="cbState in iState.checkboxes" :key="cbState.id"
                                        class="flex items-start gap-2.5 cursor-pointer mb-2">
                                        <input type="checkbox" v-model="cbState.checked"
                                            class="mt-0.5 w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-400 shrink-0" />
                                        <span class="text-sm text-gray-700">{{ checkboxDef(sState.id, iState.id, cbState.id)?.label }}</span>
                                    </label>
                                </template>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom generate button -->
            <div class="flex justify-end pt-2 pb-8">
                <button @click="generate" :disabled="sending.loading || !state.title.trim()"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white font-medium px-8 py-3 rounded-xl transition-colors">
                    {{ sending.loading ? 'Generazione in corso…' : 'Genera PDF e salva nel repository' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
