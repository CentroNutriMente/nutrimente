<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const status = ref(props.filters.status ?? '');
const year = ref(props.filters.year ?? new Date().getFullYear());

watch([status, year], () => {
    router.get(route('invoices.index'), {
        status: status.value || undefined,
        year: year.value || undefined,
    }, { preserveState: true, replace: true });
});

const statusLabel = { draft: 'Bozza', issued: 'Emessa', paid: 'Pagata', cancelled: 'Annullata' };
const statusClass = {
    draft: 'bg-gray-100 text-gray-600',
    issued: 'bg-amber-100 text-amber-700',
    paid: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-600',
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtEur = (v) => `€ ${Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <AppLayout title="Fatturazione">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Fatturazione</h1>
                <Link
                    :href="route('invoices.create')"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700"
                >+ Nuova fattura</Link>
            </div>
        </template>

        <!-- Filtri -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex gap-3">
            <select v-model="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">Tutti gli stati</option>
                <option value="draft">Bozza</option>
                <option value="issued">Emessa</option>
                <option value="paid">Pagata</option>
                <option value="cancelled">Annullata</option>
            </select>
            <select v-model="year" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">N. Fattura</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Paziente</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Imponibile</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Bollo</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Totale</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Stato</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">STS</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">Nessuna fattura trovata.</td>
                    </tr>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-gray-700">{{ inv.invoice_code }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ inv.patient?.last_name }} {{ inv.patient?.first_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ fmt(inv.issued_at) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ fmtEur(inv.subtotal) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">
                            <span v-if="inv.marca_da_bollo > 0" class="text-amber-600">{{ fmtEur(inv.marca_da_bollo) }}</span>
                            <span v-else class="text-gray-300">—</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ fmtEur(inv.total) }}</td>
                        <td class="px-4 py-3">
                            <span :class="[statusClass[inv.status], 'text-xs px-2 py-1 rounded-full font-medium']">
                                {{ statusLabel[inv.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="inv.sts_sent" class="text-xs text-purple-600 font-medium">Inviata</span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-right flex gap-2 justify-end">
                            <Link :href="route('invoices.show', inv.id)" class="text-xs text-purple-600 hover:underline">Apri</Link>
                            <a :href="route('invoices.pdf', inv.id)" target="_blank" class="text-xs text-gray-400 hover:underline">PDF</a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="invoices.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ invoices.from }}–{{ invoices.to }} di {{ invoices.total }}</span>
                <div class="flex gap-1">
                    <Link
                        v-for="link in invoices.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'px-3 py-1 rounded text-xs font-medium transition-colors'
                        ]"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
