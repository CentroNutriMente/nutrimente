<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const authUserId = usePage().props.auth.user.id;

const props = defineProps({
    patient:                Object,
    canViewReports:         Boolean,
    canCreateQuestionnaire: Boolean,
});

const activeTab = ref(props.canViewReports ? 'cartella' : 'appointments');
const tabs = [
    ...(props.canViewReports ? [{ key: 'cartella', label: 'Cartella Clinica' }] : []),
    { key: 'appointments', label: 'Appuntamenti' },
    { key: 'invoices',     label: 'Fatture' },
    { key: 'consents',     label: 'Consensi' },
];

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtDatetime = (d) => d ? new Date(d).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const unifiedList = computed(() => {
    const reports = (props.patient.reports ?? []).map(r => ({
        type: 'report',
        date: r.report_date,
        item: r,
    }));

    const standaloneQuestionnaires = (props.patient.questionnaires ?? [])
        .filter(q => !q.report_id)
        .map(q => ({
            type: 'questionnaire',
            date: q.filled_at,
            item: q,
        }));

    return [...reports, ...standaloneQuestionnaires].sort((a, b) => {
        const da = a.date ? new Date(a.date) : new Date(0);
        const db = b.date ? new Date(b.date) : new Date(0);
        return db - da;
    });
});

function questionnairesForReport(reportId) {
    return (props.patient.questionnaires ?? []).filter(q => q.report_id === reportId);
}

function scoreClass(q) {
    const tmpl = q.template;
    if (!tmpl?.questions) return 'bg-gray-100 text-gray-600';
    const max = tmpl.questions.reduce((sum, question) => {
        if (question.type === 'text') return sum;
        if (question.type === 'yesno') return sum + 1;
        if (question.type === 'scale') {
            const m = Math.max(...(question.options?.map(o => o.value) ?? [0]));
            return sum + m;
        }
        return sum;
    }, 0);
    if (max === 0) return 'bg-gray-100 text-gray-600';
    const pct = q.total_score / max;
    if (pct <= 0.33) return 'bg-green-100 text-green-700';
    if (pct <= 0.66) return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
}

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
                    <Link v-if="canCreateQuestionnaire" :href="route('questionnaires.create', { patient_id: patient.id })" class="px-3 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                        + Questionario
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
                <div v-if="canViewReports && activeTab === 'cartella'">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cartella Clinica</h3>
                        <div class="flex gap-2">
                            <Link :href="route('reports.create', { patient_id: patient.id })"
                                class="text-xs px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                                + Nuovo referto
                            </Link>
                            <Link v-if="canCreateQuestionnaire" :href="route('questionnaires.create', { patient_id: patient.id })"
                                class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                + Nuovo questionario
                            </Link>
                        </div>
                    </div>

                    <div v-if="unifiedList.length === 0" class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                        Nessun documento clinico per questo paziente.
                    </div>

                    <div v-else class="space-y-2">
                        <template v-for="entry in unifiedList" :key="entry.type + entry.item.id">

                            <!-- Report card -->
                            <div v-if="entry.type === 'report'"
                                class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-4">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="font-medium text-gray-800 text-sm">{{ entry.item.title }}</span>
                                        <span v-if="entry.item.template" class="text-xs bg-teal-50 text-teal-600 px-2 py-0.5 rounded-full">
                                            {{ entry.item.template.name }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ fmt(entry.item.report_date) }} · {{ entry.item.user?.name }}</div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="route('reports.pdf', entry.item.id)" target="_blank"
                                        class="text-xs border border-gray-200 text-gray-500 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">
                                        PDF
                                    </a>
                                    <Link v-if="entry.item.user?.id === authUserId" :href="route('reports.edit', entry.item.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        Modifica
                                    </Link>
                                    <Link :href="route('reports.show', entry.item.id)"
                                        class="text-xs text-teal-600 hover:underline">
                                        Apri
                                    </Link>
                                </div>
                            </div>

                            <!-- Linked questionnaires (indented below their report) -->
                            <div v-if="entry.type === 'report'" class="ml-6 space-y-2">
                                <Link
                                    v-for="qItem in questionnairesForReport(entry.item.id)"
                                    :key="qItem.id"
                                    :href="route('questionnaires.show', qItem.id)"
                                    class="block bg-purple-50/60 rounded-xl border border-purple-100 p-3 flex items-start gap-3 hover:border-purple-300 transition-colors">
                                    <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-xs font-medium text-gray-800">{{ qItem.template?.name }}</span>
                                            <span :class="[scoreClass(qItem), 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                                Score: {{ qItem.total_score }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ fmt(qItem.filled_at) }} · {{ qItem.user?.name }}</div>
                                    </div>
                                    <Link v-if="qItem.user?.id === authUserId"
                                        :href="route('questionnaires.edit', qItem.id)"
                                        class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors shrink-0"
                                        @click.stop>
                                        Modifica
                                    </Link>
                                </Link>
                            </div>

                            <!-- Standalone questionnaire card -->
                            <Link v-if="entry.type === 'questionnaire'"
                                :href="route('questionnaires.show', entry.item.id)"
                                class="block bg-white rounded-xl border border-purple-100 p-4 flex items-start gap-4 hover:border-purple-300 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="font-medium text-gray-800 text-sm">{{ entry.item.template?.name }}</span>
                                        <span :class="[scoreClass(entry.item), 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                            Score: {{ entry.item.total_score }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ fmt(entry.item.filled_at) }} · {{ entry.item.user?.name }}</div>
                                </div>
                                <Link v-if="entry.item.user?.id === authUserId"
                                    :href="route('questionnaires.edit', entry.item.id)"
                                    class="text-xs border border-purple-200 text-purple-600 px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors shrink-0"
                                    @click.stop>
                                    Modifica
                                </Link>
                            </Link>

                        </template>
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
