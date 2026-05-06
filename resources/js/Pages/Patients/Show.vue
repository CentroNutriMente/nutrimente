<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patient: Object, canViewReports: Boolean });

const activeTab = ref(props.canViewReports ? 'cartella' : 'appointments');
const tabs = [
    ...(props.canViewReports ? [{ key: 'cartella', label: 'Cartella Clinica' }] : []),
    { key: 'appointments', label: 'Appuntamenti' },
    { key: 'invoices',     label: 'Fatture' },
    { key: 'consents',     label: 'Consensi' },
];

const recordTypeLabel = {
    anamnesi: 'Anamnesi', seduta: 'Seduta', valutazione: 'Valutazione',
    'follow-up': 'Follow-up', trattamento: 'Trattamento', colloquio: 'Colloquio', test: 'Test',
};
const categoryColor = {
    psicologo: 'bg-blue-50 text-blue-600',
    nutrizionista: 'bg-teal-50 text-teal-600',
    osteopata: 'bg-amber-50 text-amber-700',
    generale: 'bg-gray-100 text-gray-600',
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtDatetime = (d) => d ? new Date(d).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const tagColor = (color) => ({ backgroundColor: color + '22', color });

const invoiceStatusLabel = { draft: 'Bozza', issued: 'Emessa', paid: 'Pagata', cancelled: 'Annullata' };
const invoiceStatusClass = {
    draft: 'bg-gray-100 text-gray-600',
    issued: 'bg-amber-100 text-amber-700',
    paid: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-600',
};

// Consent document upload
const showUploadForm = ref(false);
const uploadForm = useForm({
    title: '',
    file: null,
    patient_id: props.patient.id,
    category: 'consenso',
});

function pickFile(e) {
    const f = e.target.files[0];
    if (!f) return;
    uploadForm.file = f;
    if (!uploadForm.title) uploadForm.title = f.name.replace(/\.[^.]+$/, '');
}

function submitUpload() {
    uploadForm.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset();
            showUploadForm.value = false;
        },
    });
}

function formatSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

const deleteDocForm = useForm({});
function deleteDoc(id) {
    if (!confirm('Eliminare questo documento?')) return;
    deleteDocForm.delete(route('documents.destroy', id));
}
</script>

<template>
    <AppLayout :title="`${patient.last_name} ${patient.first_name}`">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">{{ patient.last_name }} {{ patient.first_name }}</h1>
                    <p class="text-sm text-gray-400">CF: {{ patient.codice_fiscale ?? '—' }}</p>
                </div>
                <span :class="patient.is_active ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500'" class="ml-2 text-xs px-2 py-1 rounded-full font-medium">
                    {{ patient.is_active ? 'Attivo' : 'Archiviato' }}
                </span>
                <div class="ml-auto flex gap-2">
                    <Link :href="route('appointments.create', { patient_id: patient.id })" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        + Appuntamento
                    </Link>
                    <Link v-if="canViewReports" :href="route('reports.create', { patient_id: patient.id })" class="px-3 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700">
                        + Referto
                    </Link>
                    <Link :href="route('invoices.create', { patient_id: patient.id })" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">
                        + Fattura
                    </Link>
                    <Link :href="route('patients.edit', patient.id)" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Modifica
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Info sidebar -->
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-700 mb-3 text-sm uppercase tracking-wide">Dati anagrafici</h3>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-gray-400 text-xs">Data di nascita</dt><dd>{{ fmt(patient.date_of_birth) }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Genere</dt><dd>{{ patient.gender ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Città</dt><dd>{{ patient.city ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Indirizzo</dt><dd>{{ patient.address ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-700 mb-3 text-sm uppercase tracking-wide">Contatti</h3>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-gray-400 text-xs">Email</dt><dd>{{ patient.email ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Telefono</dt><dd>{{ patient.phone ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Medico di base</dt><dd>{{ patient.medico_base ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs">Contatto emergenza</dt>
                            <dd>{{ patient.emergency_contact_name ?? '—' }}<br>{{ patient.emergency_contact_phone ?? '' }}</dd>
                        </div>
                    </dl>
                </div>
                <div v-if="patient.diagnosis" class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-700 mb-2 text-sm uppercase tracking-wide">Diagnosi</h3>
                    <span class="inline-block text-xs px-2.5 py-1 rounded-full font-medium bg-rose-50 text-rose-700 border border-rose-100">
                        {{ patient.diagnosis }}
                    </span>
                </div>
                <div v-if="patient.tags.length" class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-700 mb-3 text-sm uppercase tracking-wide">Tag / Disturbi</h3>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="tag in patient.tags"
                            :key="tag.id"
                            :style="tagColor(tag.color)"
                            class="text-xs px-2 py-1 rounded-full font-medium"
                        >{{ tag.name }}</span>
                    </div>
                </div>
                <div v-if="patient.notes" class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-700 mb-2 text-sm uppercase tracking-wide">Note</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ patient.notes }}</p>
                </div>
            </div>

            <!-- Main tabs -->
            <div class="lg:col-span-3">
                <!-- Tab header -->
                <div class="flex gap-1 bg-white rounded-xl border border-gray-200 p-1 mb-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            activeTab === tab.key ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors'
                        ]"
                    >{{ tab.label }}</button>
                </div>

                <!-- Appuntamenti -->
                <div v-if="activeTab === 'appointments'">
                    <div v-if="!patient.appointments.length" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
                        Nessun appuntamento registrato.
                    </div>
                    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Titolo</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Professionista</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Stato</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="apt in patient.appointments" :key="apt.id">
                                    <td class="px-4 py-3 text-gray-500">{{ fmtDatetime(apt.start_at) }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ apt.title }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ apt.user?.name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-blue-100 text-blue-700">{{ apt.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cartella Clinica -->
                <div v-if="canViewReports && activeTab === 'cartella'" class="space-y-8">

                    <!-- Annotazioni cliniche -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Annotazioni cliniche</h3>
                            <Link :href="route('patients.records', patient.id)"
                                class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                + Nuova annotazione
                            </Link>
                        </div>
                        <div v-if="!patient.records.length" class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            Nessuna annotazione clinica.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="rec in patient.records" :key="rec.id"
                                class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-gray-800 text-sm">{{ rec.title }}</span>
                                        <span :class="[categoryColor[rec.category] ?? 'bg-gray-100 text-gray-600', 'text-xs px-1.5 py-0.5 rounded']">
                                            {{ rec.category }}
                                        </span>
                                        <span class="text-xs bg-gray-50 text-gray-500 px-1.5 py-0.5 rounded">
                                            {{ recordTypeLabel[rec.record_type] ?? rec.record_type }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ fmt(rec.record_date) }} · {{ rec.user?.name }}
                                    </div>
                                    <p v-if="rec.notes" class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ rec.notes }}</p>
                                </div>
                                <div class="shrink-0 text-xs text-gray-400">
                                    <span v-if="rec.is_shared_with_team" class="bg-purple-50 text-purple-500 px-1.5 py-0.5 rounded">team</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referti -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Referti</h3>
                            <Link :href="route('reports.create', { patient_id: patient.id })"
                                class="text-xs px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                                + Nuovo referto
                            </Link>
                        </div>
                        <div v-if="!patient.reports.length" class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            Nessun referto per questo paziente.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="rep in patient.reports" :key="rep.id"
                                class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-4">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="font-medium text-gray-800 text-sm">{{ rep.title }}</span>
                                        <span v-if="rep.template" class="text-xs bg-teal-50 text-teal-600 px-2 py-0.5 rounded-full">
                                            {{ rep.template.name }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ fmt(rep.report_date) }} · {{ rep.user?.name }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="route('reports.pdf', rep.id)" target="_blank"
                                        class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                                        PDF
                                    </a>
                                    <Link :href="route('reports.edit', rep.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        Modifica
                                    </Link>
                                    <Link :href="route('reports.show', rep.id)"
                                        class="text-xs text-teal-600 hover:underline">
                                        Apri
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fatture -->
                <div v-if="activeTab === 'invoices'">
                    <div v-if="!patient.invoices.length" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
                        Nessuna fattura.
                    </div>
                    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">N. Fattura</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                                    <th class="text-right px-4 py-3 font-medium text-gray-600">Totale</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Stato</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="inv in patient.invoices" :key="inv.id">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ inv.invoice_code }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ fmt(inv.issued_at) }}</td>
                                    <td class="px-4 py-3 text-right font-medium">€ {{ Number(inv.total).toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="[invoiceStatusClass[inv.status], 'text-xs px-2 py-1 rounded-full font-medium']">
                                            {{ invoiceStatusLabel[inv.status] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link :href="route('invoices.show', inv.id)" class="text-xs text-purple-600 hover:underline">Apri</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Consensi -->
                <div v-if="activeTab === 'consents'" class="space-y-6">

                    <!-- Consensi registrati -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Consensi registrati</h3>
                        <div v-if="!patient.consents.length" class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            Nessun consenso registrato.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="c in patient.consents" :key="c.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
                                <div :class="c.accepted ? 'bg-purple-100 text-purple-600' : 'bg-red-100 text-red-500'" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="c.accepted ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">{{ c.type }}</div>
                                    <div class="text-xs text-gray-400">{{ c.method }} · {{ fmtDatetime(c.accepted_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documenti allegati -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Documenti allegati</h3>
                            <button @click="showUploadForm = !showUploadForm"
                                class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                + Carica documento
                            </button>
                        </div>

                        <!-- Upload form -->
                        <div v-if="showUploadForm" class="bg-white rounded-xl border border-gray-200 p-4 mb-3">
                            <form @submit.prevent="submitUpload" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">File</label>
                                    <input type="file" @change="pickFile"
                                        accept=".pdf,.jpg,.jpeg,.png,.docx,.doc"
                                        class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" />
                                    <p v-if="uploadForm.errors.file" class="text-xs text-red-500 mt-1">{{ uploadForm.errors.file }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Titolo</label>
                                    <input v-model="uploadForm.title" type="text" placeholder="Es. Consenso informato firmato"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400" />
                                    <p v-if="uploadForm.errors.title" class="text-xs text-red-500 mt-1">{{ uploadForm.errors.title }}</p>
                                </div>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="showUploadForm = false"
                                        class="text-xs px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">
                                        Annulla
                                    </button>
                                    <button type="submit" :disabled="uploadForm.processing || !uploadForm.file"
                                        class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
                                        {{ uploadForm.processing ? 'Caricamento...' : 'Carica' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Document list -->
                        <div v-if="!patient.consent_documents?.length && !showUploadForm"
                            class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            Nessun documento allegato.
                        </div>
                        <div v-else-if="patient.consent_documents?.length" class="space-y-2">
                            <div v-for="doc in patient.consent_documents" :key="doc.id"
                                class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate">{{ doc.title }}</div>
                                    <div class="text-xs text-gray-400">{{ doc.file_name }} · {{ formatSize(doc.file_size) }} · {{ fmt(doc.created_at) }}</div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="route('documents.view', doc.id)" target="_blank"
                                        class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                                        Apri
                                    </a>
                                    <a :href="route('documents.download', doc.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        Scarica
                                    </a>
                                    <button @click="deleteDoc(doc.id)"
                                        class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors">
                                        Elimina
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
