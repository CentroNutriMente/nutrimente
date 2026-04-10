<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({ invoice: Object, canEdit: Boolean });

const inv = props.invoice;

const fmt = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—';
const fmtEur = (v) => `€ ${Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2 })}`;

const statusLabel = { draft: 'Bozza', issued: 'Emessa', paid: 'Pagata', cancelled: 'Annullata' };
const statusClass = {
    draft: 'bg-gray-100 text-gray-600',
    issued: 'bg-amber-100 text-amber-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-600',
};

const paymentLabels = {
    contanti: 'Contanti',
    bonifico: 'Bonifico bancario',
    carta: 'Carta di credito/debito',
    paypal: 'PayPal',
};

function markPaid() {
    if (confirm('Segnare questa fattura come pagata?')) {
        router.put(route('invoices.update', inv.id), {
            status: 'paid',
            paid_at: new Date().toISOString().slice(0, 10),
        });
    }
}

function cancel() {
    if (confirm('Annullare questa fattura? L\'operazione non può essere annullata.')) {
        router.delete(route('invoices.destroy', inv.id));
    }
}
</script>

<template>
    <AppLayout :title="`Fattura ${inv.invoice_code}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('invoices.index')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800">Fattura {{ inv.invoice_code }}</h1>
                        <span :class="[statusClass[inv.status], 'text-xs px-2 py-0.5 rounded-full font-medium']">
                            {{ statusLabel[inv.status] }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('invoices.pdf', inv.id)" target="_blank"
                        class="px-4 py-2 border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                        Scarica PDF
                    </a>
                    <template v-if="canEdit">
                        <button v-if="inv.status === 'issued'" @click="markPaid"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition-colors">
                            Segna pagata
                        </button>
                        <button v-if="inv.status !== 'cancelled'" @click="cancel"
                            class="px-4 py-2 text-red-400 hover:text-red-600 text-sm hover:underline transition-colors">
                            Annulla
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="max-w-3xl space-y-6">

            <!-- Intestazione fattura -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Emittente -->
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Emittente</p>
                        <p class="font-semibold text-gray-800">{{ inv.issuer_name }}</p>
                        <p v-if="inv.issuer_partita_iva" class="text-sm text-gray-500">P.IVA: {{ inv.issuer_partita_iva }}</p>
                        <p v-if="inv.issuer_codice_fiscale" class="text-sm text-gray-500">C.F.: {{ inv.issuer_codice_fiscale }}</p>
                        <p v-if="inv.issuer_address" class="text-sm text-gray-500">{{ inv.issuer_address }}</p>
                        <p class="text-sm text-gray-400 mt-1 capitalize">Regime: {{ inv.issuer_regime_fiscale }}</p>
                    </div>
                    <!-- Cliente -->
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Cliente</p>
                        <p class="font-semibold text-gray-800">{{ inv.client_name }}</p>
                        <p v-if="inv.client_codice_fiscale" class="text-sm text-gray-500">C.F.: {{ inv.client_codice_fiscale }}</p>
                        <p v-if="inv.client_address" class="text-sm text-gray-500">{{ inv.client_address }}</p>
                        <Link v-if="inv.patient" :href="route('patients.show', inv.patient_id)"
                            class="text-xs text-purple-600 hover:underline mt-1 inline-block">
                            Vai alla cartella →
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Data emissione</p>
                        <p class="text-sm font-medium text-gray-800">{{ fmt(inv.issued_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Metodo di pagamento</p>
                        <p class="text-sm font-medium text-gray-800">{{ paymentLabels[inv.payment_method] ?? inv.payment_method ?? '—' }}</p>
                    </div>
                    <div v-if="inv.paid_at">
                        <p class="text-xs text-gray-400 mb-0.5">Data pagamento</p>
                        <p class="text-sm font-medium text-green-700">{{ fmt(inv.paid_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Righe fattura -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800 text-sm">Prestazioni</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-2.5 text-xs font-medium text-gray-500">Descrizione</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500">Qtà</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500">Prezzo unit.</th>
                            <th class="text-right px-6 py-2.5 text-xs font-medium text-gray-500">Totale</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="line in inv.lines" :key="line.id">
                            <td class="px-6 py-3 text-gray-800">{{ line.description }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ line.quantity }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ fmtEur(line.unit_price) }}</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-800">{{ fmtEur(line.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totali -->
                <div class="px-6 py-4 border-t border-gray-100 space-y-1.5">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Imponibile</span>
                        <span>{{ fmtEur(inv.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>IVA</span>
                        <span class="text-gray-400">Esente {{ inv.iva_exemption_reason }}</span>
                    </div>
                    <div v-if="inv.marca_da_bollo > 0" class="flex justify-between text-sm text-amber-600">
                        <span>Marca da bollo</span>
                        <span>{{ fmtEur(inv.marca_da_bollo) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
                        <span>Totale</span>
                        <span>{{ fmtEur(inv.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- STS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">Sistema Tessera Sanitaria</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ inv.sts_sent ? `Inviata il ${fmt(inv.sts_sent_at)}` : 'Non ancora inviata al STS' }}
                    </p>
                </div>
                <form v-if="canEdit && !inv.sts_sent && inv.status !== 'cancelled'" @submit.prevent="router.post(route('invoices.sts', inv.id))">
                    <button type="submit"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-medium transition-colors">
                        Invia al STS
                    </button>
                </form>
                <span v-else-if="inv.sts_sent" class="text-xs bg-purple-100 text-purple-700 font-medium px-3 py-1.5 rounded-full">
                    Inviata
                </span>
            </div>
        </div>
    </AppLayout>
</template>
