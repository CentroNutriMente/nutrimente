<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import PillBadge from '@/Components/ui/PillBadge.vue';
import StatTile from '@/Components/ui/StatTile.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    months: Array,
    stats: Object,
    years: Array,
    filters: Object,
});

const status = ref(props.filters.status ?? '');
const year = ref(props.filters.year ?? new Date().getFullYear());
const search = ref('');

watch([status, year], () => {
    router.get(route('invoices.index'), {
        status: status.value || undefined,
        year: year.value || undefined,
    }, { preserveState: true, replace: true });
});

const statusLabel = { draft: 'Bozza', issued: 'Emessa', paid: 'Pagata', cancelled: 'Annullata' };
const statusTone = { draft: 'neutral', issued: 'blush', paid: 'sage', cancelled: 'neutral' };
const paymentLabel = { contanti: 'Contanti', bonifico: 'Bonifico', carta: 'Carta', paypal: 'PayPal' };

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtEur = (v) => `€ ${Number(v ?? 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// Ricerca cliente lato client: i nomi sono cifrati a riposo, non filtrabili in SQL.
const visibleMonths = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.months;
    return props.months
        .map(m => ({ ...m, invoices: m.invoices.filter(i => (i.client_name || '').toLowerCase().includes(q)) }))
        .filter(m => m.invoices.length > 0);
});

const totalShown = computed(() => visibleMonths.value.reduce((s, m) => s + m.invoices.length, 0));

// Mesi aperti: di default tutti. Chiusura per singolo mese.
const collapsed = ref({});
const toggleMonth = (m) => { collapsed.value[m.month] = !collapsed.value[m.month]; };

const exportUrl = computed(() => route('invoices.export', {
    year: year.value || undefined,
    status: status.value || undefined,
}));

const cancelInvoice = (inv) => {
    if (confirm(`Annullare la fattura ${inv.invoice_code}? Resterà agli atti come annullata.`)) {
        router.delete(route('invoices.destroy', inv.id), { preserveScroll: true });
    }
};

// pr-10: spazio per la freccetta nativa della select, altrimenti si sovrappone al testo.
const selectCls = 'pl-3.5 pr-10 py-2.5 rounded-ctrl border border-line bg-card text-sm text-ink focus:border-sage focus:ring-sageLight';
</script>

<template>
    <AppLayout title="Fatture">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-5">
            <div>
                <h1 class="font-serif text-3xl text-ink">Fatture</h1>
                <p class="text-sm text-inkSoft mt-1">Archivio delle fatture emesse, suddivise per mese di emissione.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a :href="exportUrl"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-ctrl border border-line bg-card text-sm text-ink hover:bg-cream transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                    Esporta Excel
                </a>
                <Link :href="route('invoices.upload')"
                      class="inline-flex items-center gap-2 px-4 py-2.5 rounded-ctrl border border-line bg-card text-sm text-ink hover:bg-cream transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                    Carica PDF
                </Link>
                <Link :href="route('invoices.create')"
                      class="inline-flex items-center gap-2 px-4 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Nuova fattura
                </Link>
            </div>
        </div>

        <!-- Riepilogo -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <StatTile :label="`Fatturato ${stats.year}`" :value="fmtEur(stats.year_total)" tone="sage"
                      icon="M12 3v18M8 7h5.5a2.5 2.5 0 0 1 0 5H10a2.5 2.5 0 0 0 0 5H16" />
            <StatTile label="Fatturato totale" :value="fmtEur(stats.total)" tone="lavender"
                      icon="M4 19V5m0 14h16M8 16V9m4 7v-4m4 4V6" />
            <StatTile label="Numero fatture" :value="stats.count" :sub="`${stats.count_all} in archivio`" tone="blush"
                      icon="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
        </div>

        <!-- Filtri -->
        <Card class="mb-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Cerca cliente…"
                       class="flex-1 px-4 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink focus:border-sage focus:ring-sageLight" />
                <select v-model="year" :class="selectCls">
                    <option value="tutti">Tutti gli anni</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
                <select v-model="status" :class="selectCls">
                    <option value="">Tutti gli stati</option>
                    <option value="draft">Bozza</option>
                    <option value="issued">Emessa</option>
                    <option value="paid">Pagata</option>
                    <option value="cancelled">Annullata</option>
                </select>
            </div>
        </Card>

        <!-- Sottosezioni per mese -->
        <div v-if="totalShown === 0">
            <Card class="text-center py-12">
                <p class="text-sm text-inkSoft">Nessuna fattura per i filtri selezionati.</p>
            </Card>
        </div>

        <div v-else class="space-y-5">
            <Card v-for="m in visibleMonths" :key="m.month" :padded="false">
                <button @click="toggleMonth(m)" class="w-full flex items-center gap-3 px-5 py-4 text-left">
                    <svg class="w-4 h-4 text-inkMuted transition-transform" :class="collapsed[m.month] ? '-rotate-90' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                    </svg>
                    <h2 class="font-serif text-lg text-ink">{{ m.label }}</h2>
                    <PillBadge tone="sage">{{ m.invoices.length }} {{ m.invoices.length === 1 ? 'fattura' : 'fatture' }}</PillBadge>
                    <span class="ml-auto text-sm font-medium text-ink">{{ fmtEur(m.total) }}</span>
                </button>

                <div v-show="!collapsed[m.month]" class="overflow-x-auto border-t border-line">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-inkSoft border-b border-line">
                                <th class="font-medium px-5 py-3">Codice</th>
                                <th class="font-medium px-5 py-3">Data</th>
                                <th class="font-medium px-5 py-3">Cliente</th>
                                <th class="font-medium px-5 py-3 hidden lg:table-cell">Emessa da</th>
                                <th class="font-medium px-5 py-3 text-right">Importo</th>
                                <th class="font-medium px-5 py-3 hidden md:table-cell">Metodo</th>
                                <th class="font-medium px-5 py-3">Stato</th>
                                <th class="font-medium px-5 py-3 text-right">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="inv in m.invoices" :key="inv.id" class="hover:bg-cream">
                                <td class="px-5 py-3">
                                    <span class="inline-block font-mono text-xs px-2 py-1 rounded-md bg-sageLight text-sage">{{ inv.invoice_code }}</span>
                                    <div class="text-[11px] text-inkMuted mt-1">
                                        n. {{ inv.invoice_number }}
                                        <span v-if="inv.uploaded" class="text-sage" title="PDF caricato">· PDF</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-inkSoft whitespace-nowrap">{{ fmt(inv.issued_at) }}</td>
                                <td class="px-5 py-3 font-medium">
                                    <Link v-if="inv.patient_id" :href="route('patients.show', inv.patient_id)" class="text-ink hover:text-sage hover:underline">{{ inv.client_name }}</Link>
                                    <span v-else class="text-ink">{{ inv.client_name }}</span>
                                </td>
                                <td class="px-5 py-3 text-inkSoft hidden lg:table-cell">{{ inv.issuer_name || '—' }}</td>
                                <td class="px-5 py-3 text-right font-medium text-ink whitespace-nowrap">
                                    {{ fmtEur(inv.total) }}
                                    <div v-if="inv.marca_da_bollo > 0" class="text-[11px] text-inkMuted font-normal">incl. bollo {{ fmtEur(inv.marca_da_bollo) }}</div>
                                </td>
                                <td class="px-5 py-3 text-inkSoft hidden md:table-cell">{{ paymentLabel[inv.payment_method] || inv.payment_method || '—' }}</td>
                                <td class="px-5 py-3">
                                    <PillBadge :tone="statusTone[inv.status]">{{ statusLabel[inv.status] }}</PillBadge>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a :href="route('invoices.pdf.view', inv.id)" target="_blank" class="text-inkSoft hover:text-sage transition-colors" title="Visualizza online">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        </a>
                                        <a :href="route('invoices.pdf', inv.id)" class="text-inkSoft hover:text-sage transition-colors" title="Scarica PDF">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                                        </a>
                                        <Link :href="route('invoices.show', inv.id)" class="text-inkSoft hover:text-sage transition-colors" title="Apri dettaglio">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </Link>
                                        <button v-if="inv.status !== 'cancelled'" @click="cancelInvoice(inv)" class="text-inkSoft hover:text-red-500 transition-colors" title="Annulla fattura">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-7 0h8l-1 12a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1L7 7Z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
