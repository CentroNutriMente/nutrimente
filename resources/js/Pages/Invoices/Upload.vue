<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ patients: Array });

// Lettura del PDF: precompila i campi riconosciuti, sempre modificabili.
const reading = ref(false);
const readResult = ref(null); // { filled: [], template, readable }

const LABELS = {
    invoice_code: 'numero',
    issued_at: 'data',
    total: 'importo',
    payment_method: 'metodo di pagamento',
    patient_id: 'paziente',
};

const onFile = async (event) => {
    const file = event.target.files[0];
    form.file = file;
    readResult.value = null;
    if (!file) return;

    reading.value = true;
    try {
        const body = new FormData();
        body.append('file', file);
        const { data } = await window.axios.post(route('invoices.upload.analyze'), body);

        const filled = [];
        for (const key of ['invoice_code', 'issued_at', 'total', 'payment_method', 'patient_id']) {
            if (data[key] !== undefined && data[key] !== null && data[key] !== '') {
                form[key] = data[key];
                filled.push(LABELS[key]);
            }
        }
        if (!form.patient_id && data.client_name) {
            form.client_name = data.client_name;
            filled.push('intestatario');
        }
        readResult.value = { filled, template: data.template, readable: data.readable };
    } catch (e) {
        readResult.value = { filled: [], readable: false, error: true };
    } finally {
        reading.value = false;
    }
};

const form = useForm({
    file: null,
    patient_id: null,
    client_name: '',
    invoice_code: '',
    invoice_number: null,
    issued_at: new Date().toISOString().slice(0, 10),
    total: null,
    payment_method: '',
    status: 'issued',
});

const submit = () => form.post(route('invoices.upload.store'), { forceFormData: true });

const inputCls = 'w-full px-4 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink focus:border-sage focus:ring-sageLight';
const labelCls = 'block text-sm font-medium text-ink mb-1.5';
</script>

<template>
    <AppLayout title="Carica fattura">
        <Link :href="route('invoices.index')" class="inline-flex items-center gap-1 text-sm text-inkSoft hover:text-ink mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Torna alle fatture
        </Link>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <Card class="lg:col-span-2 space-y-4">
                <div>
                    <h1 class="font-serif text-2xl text-ink">Carica una fattura</h1>
                    <p class="text-sm text-inkSoft mt-1">Per fatture già emesse fuori dall'app: allega il PDF, i campi vengono letti dal file quando possibile e restano modificabili.</p>
                </div>

                <div>
                    <label :class="labelCls">File PDF</label>
                    <input type="file" accept="application/pdf" @change="onFile"
                           class="block w-full text-sm text-inkSoft file:mr-3 file:py-2 file:px-3 file:rounded-ctrl file:border-0 file:bg-sageLight file:text-sage file:text-sm file:font-medium" />
                    <p v-if="form.errors.file" class="text-xs text-red-500 mt-1">{{ form.errors.file }}</p>
                    <p v-if="form.progress" class="text-xs text-inkSoft mt-1">{{ form.progress.percentage }}%</p>

                    <!-- Esito della lettura automatica -->
                    <p v-if="reading" class="text-xs text-inkSoft mt-2">Lettura del PDF in corso…</p>
                    <div v-else-if="readResult" class="mt-2 rounded-ctrl px-3 py-2 text-xs"
                         :class="readResult.filled.length ? 'bg-sageLight text-sage' : 'bg-cream text-inkSoft'">
                        <template v-if="readResult.filled.length">
                            Letti dal PDF: {{ readResult.filled.join(', ') }}. Controlla i valori prima di salvare.
                        </template>
                        <template v-else-if="readResult.readable === false">
                            Non sono riuscito a leggere il testo del PDF (probabilmente una scansione): compila i campi a mano.
                        </template>
                        <template v-else>
                            Nessun campo riconosciuto in questo PDF: compila i campi a mano.
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label :class="labelCls">Numero/codice fattura</label>
                        <input v-model="form.invoice_code" type="text" :class="inputCls" placeholder="Es. 22/4-PS7/2026" />
                        <p v-if="form.errors.invoice_code" class="text-xs text-red-500 mt-1">{{ form.errors.invoice_code }}</p>
                    </div>
                    <div>
                        <label :class="labelCls">Data di emissione</label>
                        <input v-model="form.issued_at" type="date" :class="inputCls" />
                        <p v-if="form.errors.issued_at" class="text-xs text-red-500 mt-1">{{ form.errors.issued_at }}</p>
                    </div>
                </div>

                <div>
                    <label :class="labelCls">Paziente</label>
                    <select v-model="form.patient_id" :class="inputCls">
                        <option :value="null">Nessuno / cliente esterno</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <p class="text-xs text-inkSoft mt-1">Se il cliente non è in anagrafica, lascia «nessuno» e scrivi il nome qui sotto.</p>
                </div>

                <div v-if="!form.patient_id">
                    <label :class="labelCls">Intestatario</label>
                    <input v-model="form.client_name" type="text" :class="inputCls" placeholder="Nome e cognome / ragione sociale" />
                    <p v-if="form.errors.client_name" class="text-xs text-red-500 mt-1">{{ form.errors.client_name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label :class="labelCls">Importo totale (€)</label>
                        <input v-model="form.total" type="number" min="0" step="0.01" :class="inputCls" placeholder="56.10" />
                        <p v-if="form.errors.total" class="text-xs text-red-500 mt-1">{{ form.errors.total }}</p>
                    </div>
                    <div>
                        <label :class="labelCls">Metodo di pagamento</label>
                        <select v-model="form.payment_method" :class="inputCls">
                            <option value="">—</option>
                            <option value="contanti">Contanti</option>
                            <option value="bonifico">Bonifico</option>
                            <option value="carta">Carta</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    <div>
                        <label :class="labelCls">Stato</label>
                        <select v-model="form.status" :class="inputCls">
                            <option value="issued">Emessa</option>
                            <option value="paid">Pagata</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('invoices.index')" class="text-sm text-inkSoft hover:underline">Annulla</Link>
                    <button @click="submit" :disabled="form.processing || !form.file"
                            class="px-5 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium disabled:opacity-60">
                        Carica fattura
                    </button>
                </div>
            </Card>

            <Card>
                <h2 class="font-serif text-lg text-ink mb-3">Come funziona</h2>
                <ul class="text-sm text-inkSoft space-y-2.5 leading-relaxed">
                    <li>Appena scegli il file, numero, data, importo, metodo e paziente vengono <span class="text-ink">letti dal PDF</span> (il paziente tramite il codice fiscale). Verifica sempre i valori.</li>
                    <li>Il PDF viene salvato <span class="text-ink">cifrato</span>, come gli altri documenti clinici.</li>
                    <li>La fattura compare nell'archivio nel mese della data di emissione, ordinata per numero.</li>
                    <li>Scaricandola o aprendola online ottieni <span class="text-ink">il PDF originale</span>, non una versione rigenerata.</li>
                    <li>I dati che inserisci finiscono anche nell'esportazione Excel.</li>
                    <li>Se ometti il numero progressivo, viene ricavato dalle cifre del codice.</li>
                </ul>
            </Card>
        </div>
    </AppLayout>
</template>
