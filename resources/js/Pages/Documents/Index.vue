<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    documents: Object,
    filters: Object,
});

const showUpload = ref(false);

const uploadForm = useForm({
    title: '',
    description: '',
    category: '',
    patient_id: '',
    is_shared_with_patient: false,
    file: null,
});

function submitUpload() {
    uploadForm.post(route('documents.store'), {
        onSuccess: () => {
            showUpload.value = false;
            uploadForm.reset();
        },
    });
}

function deleteDoc(id) {
    if (confirm('Eliminare questo documento?')) {
        router.delete(route('documents.destroy', id), { preserveScroll: true });
    }
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
    questionario: 'Questionario',
    protocollo: 'Protocollo',
    modulo: 'Modulo',
    contratto: 'Contratto',
};

const mimeIcon = (mime) => {
    if (mime?.includes('pdf')) return '📄';
    if (mime?.includes('image')) return '🖼️';
    if (mime?.includes('word') || mime?.includes('document')) return '📝';
    if (mime?.includes('sheet') || mime?.includes('excel')) return '📊';
    return '📎';
};

// Search
const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');

function applyFilters() {
    router.get(route('documents.index'), { search: search.value, category: category.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout title="Documenti">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Repository Documenti</h1>
                <button @click="showUpload = !showUpload"
                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    + Carica documento
                </button>
            </div>
        </template>

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
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
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
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                <option value="">Tutte le categorie</option>
                <option value="questionario">Questionari</option>
                <option value="protocollo">Protocolli</option>
                <option value="modulo">Moduli</option>
                <option value="contratto">Contratti</option>
            </select>
        </div>

        <!-- Documents grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="doc in documents.data"
                :key="doc.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col gap-2"
            >
                <div class="flex items-start gap-3">
                    <span class="text-2xl leading-none mt-0.5">{{ mimeIcon(doc.mime_type) }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800 text-sm leading-tight truncate">{{ doc.title }}</div>
                        <div v-if="doc.description" class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ doc.description }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <span v-if="doc.category"
                        class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-medium">
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
                v-html="link.label"
            />
        </div>
    </AppLayout>
</template>
