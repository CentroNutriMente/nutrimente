<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    patients: Array,
    profile: Object,
});

const form = useForm({
    patient_id: '',
    lines: [{ description: '', quantity: 1, unit_price: '' }],
    payment_method: '',
    issued_at: new Date().toISOString().slice(0, 10),
});

const addLine = () => form.lines.push({ description: '', quantity: 1, unit_price: '' });
const removeLine = (i) => form.lines.splice(i, 1);

const subtotal = computed(() =>
    form.lines.reduce((sum, l) => sum + (Number(l.quantity) * Number(l.unit_price) || 0), 0)
);

const BOLLO_THRESHOLD = 77.47;
const BOLLO_AMOUNT = 2.00;

const marcaDaBollo = computed(() => subtotal.value > BOLLO_THRESHOLD ? BOLLO_AMOUNT : 0);
const total = computed(() => subtotal.value + marcaDaBollo.value);

const fmtEur = (v) => `€ ${Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2 })}`;

const submit = () => form.post(route('invoices.store'));

const paymentMethods = ['contanti', 'bonifico', 'pos', 'assegno'];
</script>

<template>
    <AppLayout title="Nuova fattura">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('invoices.index')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Nuova fattura</h1>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
            <div class="lg:col-span-2 space-y-5">
                <!-- Intestazione -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="font-semibold text-gray-700">Intestazione fattura</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Paziente *</label>
                            <select v-model="form.patient_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Seleziona paziente...</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">
                                    {{ p.last_name }} {{ p.first_name }}{{ p.codice_fiscale ? ` — ${p.codice_fiscale}` : '' }}
                                </option>
                            </select>
                            <p v-if="form.errors.patient_id" class="text-red-500 text-xs mt-1">{{ form.errors.patient_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data fattura *</label>
                            <input v-model="form.issued_at" type="date" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Metodo di pagamento</label>
                            <select v-model="form.payment_method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">—</option>
                                <option v-for="m in paymentMethods" :key="m" :value="m">{{ m.charAt(0).toUpperCase() + m.slice(1) }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Righe fattura -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-700">Prestazioni</h2>
                        <button type="button" @click="addLine" class="text-sm text-emerald-600 hover:underline">+ Aggiungi riga</button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(line, i) in form.lines"
                            :key="i"
                            class="grid grid-cols-12 gap-2 items-start"
                        >
                            <div class="col-span-6">
                                <input
                                    v-model="line.description"
                                    type="text"
                                    placeholder="Descrizione prestazione..."
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                                <p v-if="form.errors[`lines.${i}.description`]" class="text-red-500 text-xs mt-1">{{ form.errors[`lines.${i}.description`] }}</p>
                            </div>
                            <div class="col-span-2">
                                <input
                                    v-model.number="line.quantity"
                                    type="number"
                                    min="1"
                                    placeholder="Qtà"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 text-center"
                                />
                            </div>
                            <div class="col-span-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                                    <input
                                        v-model.number="line.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0,00"
                                        class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 text-right"
                                    />
                                </div>
                            </div>
                            <div class="col-span-1 flex items-center justify-center pt-2">
                                <button
                                    v-if="form.lines.length > 1"
                                    type="button"
                                    @click="removeLine(i)"
                                    class="text-gray-300 hover:text-red-400 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Emissione...' : 'Emetti fattura' }}
                    </button>
                    <Link :href="route('invoices.index')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Annulla
                    </Link>
                </div>
            </div>

            <!-- Sidebar riepilogo -->
            <div class="space-y-4">
                <!-- Dati emittente -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-700 mb-3 text-sm">Dati emittente</h3>
                    <div class="text-sm space-y-1 text-gray-600">
                        <div v-if="profile?.partita_iva"><span class="text-gray-400">P.IVA:</span> {{ profile.partita_iva }}</div>
                        <div v-if="profile?.codice_fiscale"><span class="text-gray-400">CF:</span> {{ profile.codice_fiscale }}</div>
                        <div v-if="profile?.regime_fiscale"><span class="text-gray-400">Regime:</span> {{ profile.regime_fiscale }}</div>
                    </div>
                    <p v-if="!profile?.partita_iva" class="text-xs text-amber-600 mt-2">
                        Completa il profilo professionale per aggiungere i dati fiscali.
                    </p>
                </div>

                <!-- Riepilogo importi -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-700 mb-3 text-sm">Riepilogo</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Imponibile</span>
                            <span>{{ fmtEur(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>IVA (Art.10 esente)</span>
                            <span>€ 0,00</span>
                        </div>
                        <div class="flex justify-between" :class="marcaDaBollo > 0 ? 'text-amber-600' : 'text-gray-400'">
                            <span>Marca da bollo {{ subtotal > 0 && subtotal <= BOLLO_THRESHOLD ? `(sotto soglia €${BOLLO_THRESHOLD})` : '' }}</span>
                            <span>{{ marcaDaBollo > 0 ? fmtEur(marcaDaBollo) : '—' }}</span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 flex justify-between font-semibold text-gray-900">
                            <span>TOTALE</span>
                            <span>{{ fmtEur(total) }}</span>
                        </div>
                    </div>
                    <div v-if="marcaDaBollo > 0" class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-lg p-2">
                        Importo superiore a €77,47: marca da bollo di €2,00 applicata automaticamente.
                    </div>
                    <p class="mt-3 text-xs text-gray-400">
                        Operazione esente IVA ai sensi dell'art. 10, n. 18 del D.P.R. 633/72.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
