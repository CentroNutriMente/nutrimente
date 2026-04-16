<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    documents: Object,
    filters:   Object,
    templates: Array,
});

const activeTab   = ref('documents');
const showUpload  = ref(false);
const showNewTmpl = ref(false);

// ── Upload form ────────────────────────────────────────────────────────────────
const uploadForm = useForm({
    title:                  '',
    description:            '',
    category:               '',
    patient_id:             '',
    is_shared_with_patient: false,
    file:                   null,
});
function submitUpload() {
    uploadForm.post(route('documents.store'), {
        onSuccess: () => { showUpload.value = false; uploadForm.reset(); },
    });
}

// ── New template form ──────────────────────────────────────────────────────────
const newTmplForm = useForm({ name: '', description: '' });
function submitNewTmpl() {
    newTmplForm.post(route('doc-templates.store'), {
        onSuccess: () => { showNewTmpl.value = false; newTmplForm.reset(); },
    });
}

function deleteDoc(id) {
    if (confirm('Eliminare questo documento?'))
        router.delete(route('documents.destroy', id), { preserveScroll: true });
}
function deleteTmpl(id) {
    if (confirm('Eliminare questo template?'))
        router.delete(route('doc-templates.destroy', id), { preserveScroll: true });
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}
function formatDate(d) {
    return new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const categoryLabel = {
    questionario: 'Questionario', protocollo: 'Protocollo',
    modulo: 'Modulo',             contratto:  'Contratto',
};
const mimeIcon = (mime) => {
    if (mime?.includes('pdf'))                               return '📄';
    if (mime?.includes('image'))                             return '🖼️';
    if (mime?.includes('word') || mime?.includes('document')) return '📝';
    if (mime?.includes('sheet') || mime?.includes('excel'))  return '📊';
    return '📎';
};

const search   = ref(props.filters?.search   ?? '');
const category = ref(props.filters?.category ?? '');
function applyFilters() {
    router.get(route('documents.index'), { search: search.value, category: category.value },
        { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout title="Documenti">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Documenti</h1>
                <div class="flex items-center gap-2">
                    <button v-if="activeTab === 'documents'" @click="showUpload = !showUpload"
                        class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                        + Carica documento
                    </button>
                    <button v-if="activeTab === 'templates'" @click="showNewTmpl = !showNewTmpl"
                        class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                        + Nuovo template
                    </button>
                </div>
            </div>
        </template>

        <!-- Tabs -->
        <div class="flex gap-1 mb-5 bg-gray-100 p-1 rounded-xl w-fit">
            <button @click="activeTab = 'documents'"
                :class="activeTab === 'documents' ? 'bg-white shadow-sm text-gray-800 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-1.5 rounded-lg text-sm transition-all">
                Repository
            </button>
            <button @click="activeTab = 'templates'"
                :class="activeTab === 'templates' ? 'bg-white shadow-sm text-gray-800 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-1.5 rounded-lg text-sm transition-all">
                Modelli documento
            </button>
        </div>

        <!-- ── DOCUMENTS TAB ──────────────────────────────────────────────────── -->
        <template v-if="activeTab === 'documents'">

            <!-- Upload panel -->
            <div v-if="showUpload" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Carica nuovo documento</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Titolo *</label>
                        <input v-model="uploadForm.title" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                        <select v-model="uploadForm.category"
                            class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option value="">— nessuna —</option>
                            <option value="questionario">Questionario</option>
                            <option value="protocollo">Protocollo</option>
                            <option value="modulo">Modulo</option>
                            <option value="contratto">Contratto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">File *</label>
                        <input type="file" @change="e => uploadForm.file = e.target.files[0]"
                            class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione</label>
                        <textarea v-model="uploadForm.description" rows="2"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showUpload = false" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Annulla
                    </button>
                    <button @click="submitUpload" :disabled="uploadForm.processing || !uploadForm.title || !uploadForm.file"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-xl transition-colors">
                        {{ uploadForm.processing ? 'Caricamento…' : 'Carica' }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-3 mb-4">
                <input v-model="search" @keydown.enter="applyFilters" type="text" placeholder="Cerca documento…"
                    class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 w-64" />
                <select v-model="category" @change="applyFilters"
                    class="rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                    <option value="">Tutte le categorie</option>
                    <option value="questionario">Questionari</option>
                    <option value="protocollo">Protocolli</option>
                    <option value="modulo">Moduli</option>
                    <option value="contratto">Contratti</option>
                </select>
            </div>

            <!-- Documents grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="doc in documents.data" :key="doc.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col gap-2">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl leading-none mt-0.5">{{ mimeIcon(doc.mime_type) }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800 text-sm leading-tight truncate">{{ doc.title }}</div>
                            <div v-if="doc.description" class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ doc.description }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap mt-1">
                        <span v-if="doc.category" class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-medium">
                            {{ categoryLabel[doc.category] ?? doc.category }}
                        </span>
                        <span v-if="doc.patient_name" class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
                            {{ doc.patient_name }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-400 flex items-center gap-2 mt-auto pt-1 border-t border-gray-50">
                        <span>{{ formatSize(doc.file_size) }}</span>
                        <span>·</span>
                        <span>{{ formatDate(doc.created_at) }}</span>
                        <span>·</span>
                        <span class="truncate">{{ doc.uploaded_by_name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a :href="route('documents.download', doc.id)" target="_blank"
                            class="text-xs text-purple-600 hover:underline font-medium">
                            Scarica
                        </a>
                        <button @click="deleteDoc(doc.id)" class="text-xs text-red-400 hover:text-red-600 hover:underline ml-auto">
                            Elimina
                        </button>
                    </div>
                </div>

                <div v-if="documents.data.length === 0"
                    class="col-span-full bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 text-sm">
                    Nessun documento trovato.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="documents.last_page > 1" class="flex items-center gap-2 mt-4">
                <a v-for="link in documents.links" :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[link.active ? 'bg-purple-600 text-white' : 'text-gray-500 hover:bg-gray-50 bg-white border border-gray-200', 'text-xs px-3 py-1.5 rounded-lg transition-colors']"
                    v-html="link.label" />
            </div>
        </template>

        <!-- ── TEMPLATES TAB ──────────────────────────────────────────────────── -->
        <template v-if="activeTab === 'templates'">

            <!-- New template form -->
            <div v-if="showNewTmpl" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Nuovo template</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nome template *</label>
                        <input v-model="newTmplForm.name" type="text"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Descrizione</label>
                        <textarea v-model="newTmplForm.description" rows="2"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showNewTmpl = false" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Annulla
                    </button>
                    <button @click="submitNewTmpl" :disabled="newTmplForm.processing || !newTmplForm.name"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-xl transition-colors">
                        {{ newTmplForm.processing ? 'Creazione…' : 'Crea e modifica' }}
                    </button>
                </div>
            </div>

            <!-- Templates list -->
            <div class="space-y-3">
                <div v-for="tmpl in templates" :key="tmpl.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                        :class="tmpl.is_system ? 'bg-purple-100' : 'bg-gray-100'">
                        <svg class="w-5 h-5" :class="tmpl.is_system ? 'text-purple-600' : 'text-gray-500'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-800 leading-tight">{{ tmpl.name }}</span>
                            <span v-if="tmpl.is_system"
                                class="text-[10px] bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-medium">sistema</span>
                        </div>
                        <p v-if="tmpl.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ tmpl.description }}</p>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <a :href="`/doc-templates/${tmpl.id}/compile`"
                            class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium px-4 py-2 rounded-xl transition-colors">
                            Compila
                        </a>
                        <a :href="`/doc-templates/${tmpl.id}/edit`"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium px-3 py-2 rounded-xl transition-colors">
                            Modifica
                        </a>
                        <button v-if="!tmpl.is_system" @click="deleteTmpl(tmpl.id)"
                            class="text-gray-400 hover:text-red-500 p-2 rounded-xl hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-if="!templates.length"
                    class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 text-sm">
                    Nessun template disponibile. Crea il primo template.
                </div>
            </div>
        </template>

    </AppLayout>
</template>
