<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const authUserId = usePage().props.auth.user.id;

const props = defineProps({
    patient:                Object,
    isCreator:              Boolean,
    canCreateQuestionnaire: Boolean,
});

const activeTab = ref('cartella');
const tabs = computed(() => [
    { key: 'cartella',     label: 'Cartella Clinica' },
    { key: 'appointments', label: 'Appuntamenti' },
    ...(props.isCreator ? [
        { key: 'invoices', label: 'Fatture' },
        { key: 'consents', label: 'Consensi' },
    ] : []),
]);

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtDatetime = (d) => d ? new Date(d).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

function aptStatusInfo(apt) {
    if (apt.status === 'cancelled') return { label: 'Disdetto', cls: 'bg-red-100 text-red-600' };
    if (apt.status === 'scheduled' || apt.status === 'confirmed') {
        const past = apt.start_at && new Date(apt.start_at) < new Date();
        return past
            ? { label: 'Effettuato', cls: 'bg-green-100 text-green-700' }
            : { label: 'Prenotato',  cls: 'bg-blue-100 text-blue-700' };
    }
    return { label: apt.status, cls: 'bg-gray-100 text-gray-600' };
}

const unifiedList = computed(() => {
    const reports = (props.patient.reports ?? []).map(r => ({
        type: 'report',
        date: r.report_date,
        item: r,
    }));

    const visibleReportIds = new Set((props.patient.reports ?? []).map(r => r.id));

    const standaloneQuestionnaires = (props.patient.questionnaires ?? [])
        .filter(q => !q.report_id || !visibleReportIds.has(q.report_id))
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

function getScoreInfo(q) {
    const thresholds = q.template?.scoring?.thresholds ?? [];
    const score      = q.total_score;
    const match      = thresholds.find(t => score >= t.min && score <= t.max);
    if (!match) return { label: null, colorClass: 'bg-gray-100 text-gray-600' };
    const colorMap = {
        'green-light':  'bg-green-50 text-green-600',
        'green':        'bg-green-100 text-green-700',
        'yellow-light': 'bg-yellow-50 text-yellow-600',
        'yellow':       'bg-yellow-100 text-yellow-700',
        'orange-light': 'bg-orange-50 text-orange-600',
        'orange':       'bg-orange-100 text-orange-700',
        'orange-dark':  'bg-orange-200 text-orange-800',
        'red':          'bg-red-100 text-red-700',
    };
    return { label: match.label, colorClass: colorMap[match.color] ?? 'bg-gray-100 text-gray-600' };
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

// ── Grafici ──────────────────────────────────────────────────────────────────
const svgBandFill = {
    'green-light':  'rgba(34,197,94,0.07)',
    'green':        'rgba(34,197,94,0.13)',
    'yellow-light': 'rgba(234,179,8,0.07)',
    'yellow':       'rgba(234,179,8,0.13)',
    'orange-light': 'rgba(249,115,22,0.07)',
    'orange':       'rgba(249,115,22,0.13)',
    'orange-dark':  'rgba(249,115,22,0.22)',
    'red':          'rgba(239,68,68,0.13)',
};
const svgBandText = {
    'green-light':  '#16a34a',
    'green':        '#15803d',
    'yellow-light': '#ca8a04',
    'yellow':       '#854d0e',
    'orange-light': '#ea580c',
    'orange':       '#9a3412',
    'orange-dark':  '#7c2d12',
    'red':          '#991b1b',
};

function buildChart(items, thresholds, yMin, yMax) {
    const W = 520, H = 190, ml = 38, mr = 8, mt = 22, mb = 52;
    const cw = W - ml - mr, ch = H - mt - mb;
    const n = items.length;
    const xPos = i => ml + (n < 2 ? cw / 2 : (i * cw) / (n - 1));
    const yPos = v => {
        const range = yMax - yMin;
        if (range === 0) return mt + ch / 2;
        return mt + ch - ((Number(v) - yMin) / range) * ch;
    };
    const points = items.map((item, i) => ({
        x: xPos(i),
        y: yPos(item.total_score),
        score: item.total_score,
        dateLabel: new Date(item.filled_at).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: '2-digit' }),
    }));
    const polyline = points.map(p => `${p.x},${p.y}`).join(' ');
    const bands = thresholds.map(t => {
        const yTop = yPos(Math.min(Number(t.max), yMax));
        const yBot = yPos(Math.max(Number(t.min), yMin));
        return { ...t, y: yTop, h: Math.max(yBot - yTop, 0), midY: (yTop + yBot) / 2 };
    });
    const rawTicks = thresholds.length
        ? [...new Set(thresholds.flatMap(t => [Number(t.min), Number(t.max)]))]
        : [yMin, yMax];
    const yTicks = rawTicks
        .filter(v => v >= yMin && v <= yMax)
        .map(v => ({ v, y: yPos(v) }));
    return { W, H, ml, mr, mt, mb, cw, ch, points, polyline, bands, yTicks };
}

const chartsData = computed(() => {
    const qs = props.patient.questionnaires ?? [];
    const groups = {};
    for (const q of qs) {
        const tid = q.questionnaire_template_id;
        if (!groups[tid]) groups[tid] = { template: q.template, items: [] };
        groups[tid].items.push(q);
    }
    return Object.values(groups)
        .filter(g => g.items.length >= 2)
        .map(g => {
            const items = [...g.items].sort((a, b) => new Date(a.filled_at) - new Date(b.filled_at));
            const thresholds = [...(g.template?.scoring?.thresholds ?? [])]
                .sort((a, b) => Number(a.min) - Number(b.min));
            const scores = items.map(i => i.total_score);
            const yMin = thresholds.length ? Number(thresholds[0].min) : Math.min(...scores);
            const yMax = thresholds.length ? Number(thresholds[thresholds.length - 1].max) : Math.max(...scores);
            return {
                template: g.template,
                items,
                chart: buildChart(items, thresholds, yMin, yMax),
            };
        });
});
</script>

<template>
    <AppLayout :title="`${patient.last_name} ${patient.first_name}`">
        <template #header>
            <div class="w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-xl font-semibold text-gray-800 truncate">{{ patient.last_name }} {{ patient.first_name }}</h1>
                        <p class="text-sm text-gray-400">CF: {{ patient.codice_fiscale ?? '—' }}</p>
                    </div>
                    <span :class="patient.is_active ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500'" class="shrink-0 text-xs px-2 py-1 rounded-full font-medium">
                        {{ patient.is_active ? 'Attivo' : 'Archiviato' }}
                    </span>
                    <!-- Bottoni azione — visibili solo su sm+ -->
                    <div v-if="isCreator" class="hidden sm:flex gap-2 ml-2 shrink-0">
                        <Link :href="route('appointments.create', { patient_id: patient.id })" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                            + Appuntamento
                        </Link>
                        <Link :href="route('reports.create', { patient_id: patient.id })" class="px-3 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700">
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
                <!-- Bottoni azione — versione mobile, sotto il nome -->
                <div v-if="isCreator" class="flex flex-wrap gap-2 mt-3 sm:hidden">
                    <Link :href="route('appointments.create', { patient_id: patient.id })" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700">
                        + Appuntamento
                    </Link>
                    <Link :href="route('reports.create', { patient_id: patient.id })" class="px-3 py-1.5 bg-teal-600 text-white rounded-lg text-xs font-medium hover:bg-teal-700">
                        + Referto
                    </Link>
                    <Link v-if="canCreateQuestionnaire" :href="route('questionnaires.create', { patient_id: patient.id })" class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-medium hover:bg-purple-700">
                        + Questionario
                    </Link>
                    <Link :href="route('invoices.create', { patient_id: patient.id })" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-medium hover:bg-amber-700">
                        + Fattura
                    </Link>
                    <Link :href="route('patients.edit', patient.id)" class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50">
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
                <div class="flex gap-1 bg-white rounded-xl border border-gray-200 p-1 mb-4 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            activeTab === tab.key ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors shrink-0'
                        ]"
                    >{{ tab.label }}</button>
                </div>

                <!-- Appuntamenti -->
                <div v-if="activeTab === 'appointments'">
                    <div v-if="!patient.appointments.length" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
                        Nessun appuntamento registrato.
                    </div>
                    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Titolo</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Professionista</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Stato</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="apt in patient.appointments" :key="apt.id">
                                    <td class="px-4 py-3 text-gray-500">{{ fmtDatetime(apt.start_at) }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ apt.title }}</td>
                                    <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ apt.user?.name }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['text-xs px-2 py-1 rounded-full font-medium', aptStatusInfo(apt).cls]">
                                            {{ aptStatusInfo(apt).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link v-if="apt.user?.id === authUserId"
                                            :href="route('appointments.edit', { appointment: apt.id, return_to: 'patient' })"
                                            class="text-xs text-purple-600 hover:underline">
                                            Modifica
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>

                <!-- Cartella Clinica -->
                <div v-if="activeTab === 'cartella'">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cartella Clinica</h3>
                        <div v-if="isCreator" class="flex gap-2">
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
                                            <span :class="[getScoreInfo(qItem).colorClass, 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                                Score: {{ qItem.total_score }}
                                                <template v-if="getScoreInfo(qItem).label"> — {{ getScoreInfo(qItem).label }}</template>
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
                                        <span :class="[getScoreInfo(entry.item).colorClass, 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                            Score: {{ entry.item.total_score }}
                                            <template v-if="getScoreInfo(entry.item).label"> — {{ getScoreInfo(entry.item).label }}</template>
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

                    <!-- Grafici -->
                    <div v-if="chartsData.length > 0" class="mt-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Grafici</h3>
                        <div class="space-y-4">
                            <div v-for="group in chartsData" :key="group.template?.id ?? group.template?.name"
                                class="bg-white rounded-xl border border-gray-200 p-4">
                                <p class="text-sm font-semibold text-gray-700 mb-3">{{ group.template?.name }}</p>
                                <svg :viewBox="`0 0 ${group.chart.W} ${group.chart.H}`" width="100%"
                                    class="overflow-visible" style="font-family:inherit">

                                    <!-- Threshold bands -->
                                    <rect v-for="band in group.chart.bands" :key="'band-' + band.label"
                                        :x="group.chart.ml" :y="band.y"
                                        :width="group.chart.cw" :height="band.h"
                                        :fill="svgBandFill[band.color] ?? 'rgba(156,163,175,0.1)'" />

                                    <!-- Band labels (inside right edge of chart) -->
                                    <text v-for="band in group.chart.bands" :key="'bl-' + band.label"
                                        :x="group.chart.ml + group.chart.cw - 5" :y="band.midY"
                                        text-anchor="end" dominant-baseline="middle"
                                        font-size="8.5" :fill="svgBandText[band.color] ?? '#6b7280'"
                                        opacity="0.9">{{ band.label }}</text>

                                    <!-- X axis baseline -->
                                    <line :x1="group.chart.ml" :y1="group.chart.mt + group.chart.ch"
                                        :x2="group.chart.ml + group.chart.cw" :y2="group.chart.mt + group.chart.ch"
                                        stroke="#e5e7eb" stroke-width="1" />

                                    <!-- Y axis -->
                                    <line :x1="group.chart.ml" :y1="group.chart.mt"
                                        :x2="group.chart.ml" :y2="group.chart.mt + group.chart.ch"
                                        stroke="#e5e7eb" stroke-width="1" />

                                    <!-- Y ticks -->
                                    <g v-for="tick in group.chart.yTicks" :key="'yt-' + tick.v">
                                        <line :x1="group.chart.ml - 3" :y1="tick.y"
                                            :x2="group.chart.ml" :y2="tick.y"
                                            stroke="#d1d5db" stroke-width="1" />
                                        <text :x="group.chart.ml - 5" :y="tick.y"
                                            text-anchor="end" dominant-baseline="middle"
                                            font-size="9" fill="#9ca3af">{{ tick.v }}</text>
                                    </g>

                                    <!-- Score line -->
                                    <polyline :points="group.chart.polyline"
                                        fill="none" stroke="#7c3aed" stroke-width="2"
                                        stroke-linejoin="round" stroke-linecap="round" />

                                    <!-- Dots + labels -->
                                    <g v-for="pt in group.chart.points" :key="'pt-' + pt.x">
                                        <!-- Score above dot -->
                                        <text :x="pt.x" :y="pt.y - 9"
                                            text-anchor="middle" font-size="9"
                                            fill="#7c3aed" font-weight="600">{{ pt.score }}</text>
                                        <!-- Dot -->
                                        <circle :cx="pt.x" :cy="pt.y" r="4"
                                            fill="#7c3aed" stroke="white" stroke-width="1.5" />
                                        <!-- Date label (rotated -40°) -->
                                        <text :transform="`translate(${pt.x},${group.chart.mt + group.chart.ch + 10}) rotate(-40)`"
                                            text-anchor="end" font-size="9" fill="#9ca3af">{{ pt.dateLabel }}</text>
                                    </g>
                                </svg>
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
