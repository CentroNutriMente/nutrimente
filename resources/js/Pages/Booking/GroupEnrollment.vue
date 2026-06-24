<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import BotanicalSprig from '@/Components/ui/BotanicalSprig.vue';
import { computed } from 'vue';

const props = defineProps({
    groups: Array,
    categories: Array,
    preselect: Object,
    source: String,
});

const page = usePage();
const flash = computed(() => page.props.flash?.banner);

const HOW_HEARD = [
    'Passaparola', 'Social', 'MioDottore', 'Sito web', 'Volantino / QR', 'Altro',
];

const catLabel = (key) => props.categories.find(c => c.key === key)?.label ?? key;

const form = useForm({
    nome: '',
    cognome: '',
    email: '',
    phone: '',
    group_id: props.preselect?.id ?? null,
    how_heard: '',
    privacy_consent: false,
    source: props.source ?? 'form',
});

const submit = () => {
    form.transform((d) => ({
        name: `${d.nome} ${d.cognome}`.trim(),
        email: d.email,
        phone: d.phone,
        group_id: d.group_id,
        how_heard: d.how_heard,
        privacy_consent: d.privacy_consent,
        source: d.source,
    })).post(route('groups.public.store'), {
        onSuccess: () => { form.reset(); window.scrollTo({ top: 0, behavior: 'smooth' }); },
    });
};

const inputCls = 'w-full px-4 py-2.5 rounded-2xl border border-cream-300 bg-white text-sm text-stone-700 placeholder-stone-400 focus:border-sage-300 focus:ring-sage-200';
</script>

<template>
    <div class="min-h-screen bg-cream-100">
        <!-- Top nav -->
        <nav class="bg-cream-50/80 backdrop-blur border-b border-cream-200 sticky top-0 z-10">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 text-stone-800">
                    <BotanicalSprig klass="w-6 h-6 text-sage-400" />
                    <span class="font-serif text-xl lowercase">nutrimente</span>
                </a>
                <a href="/login" class="text-sm font-medium text-white bg-sage-500 hover:bg-sage-600 px-4 py-1.5 rounded-2xl transition-colors">Accesso professionisti</a>
            </div>
        </nav>

        <!-- Hero -->
        <header class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-16 left-1/3 w-72 h-72 rounded-full blur-3xl opacity-50" style="background: radial-gradient(circle, #D9CFEA 0%, transparent 70%)"></div>
                <div class="absolute -top-10 right-1/4 w-72 h-72 rounded-full blur-3xl opacity-40" style="background: radial-gradient(circle, #F6E4D2 0%, transparent 70%)"></div>
            </div>
            <div class="max-w-2xl mx-auto px-4 pt-10 pb-6 text-center">
                <a href="/" class="self-start text-sm text-stone-400 hover:text-stone-600 inline-flex items-center gap-1 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Torna alla home
                </a>
                <h1 class="font-serif text-4xl text-stone-800">Nuova iscrizione a un gruppo</h1>
                <p class="text-stone-500 mt-3 max-w-md mx-auto">Compila i dati per richiedere l'iscrizione. Verrai ricontattato a breve.</p>
                <p v-if="preselect" class="mt-3 inline-block text-sm text-sage-700 bg-sage-100 px-3 py-1.5 rounded-full">Gruppo: {{ preselect.name }}</p>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 pb-16">
            <div v-if="flash" class="mb-5 rounded-2xl bg-sage-100 border border-sage-200 text-sage-700 px-4 py-3 text-sm text-center">{{ flash }}</div>

            <form @submit.prevent="submit" class="bg-white rounded-4xl border border-cream-200 shadow-soft p-6 sm:p-8 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Nome</label>
                        <input v-model="form.nome" type="text" :class="inputCls" placeholder="Nome" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Cognome</label>
                        <input v-model="form.cognome" type="text" :class="inputCls" placeholder="Cognome" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Email</label>
                        <input v-model="form.email" type="email" :class="inputCls" placeholder="email@esempio.it" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Telefono</label>
                        <input v-model="form.phone" type="text" :class="inputCls" placeholder="333 1234567" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-stone-600 mb-1.5">Gruppo di interesse</label>
                    <select v-model="form.group_id" :class="inputCls">
                        <option :value="null">Seleziona un gruppo…</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }} — {{ catLabel(g.category) }}</option>
                    </select>
                    <p v-if="form.errors.group_id" class="text-xs text-red-500 mt-1">{{ form.errors.group_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-stone-600 mb-1.5">Come hai conosciuto questo gruppo?</label>
                    <select v-model="form.how_heard" :class="inputCls">
                        <option value="">Seleziona un'opzione</option>
                        <option v-for="h in HOW_HEARD" :key="h" :value="h">{{ h }}</option>
                    </select>
                </div>

                <label class="flex items-start gap-3 text-sm text-stone-600">
                    <input v-model="form.privacy_consent" type="checkbox" class="mt-0.5 rounded border-cream-300 text-sage-500 focus:ring-sage-200" />
                    <span>Ho letto l'informativa privacy e acconsento al trattamento dei dati.</span>
                </label>
                <p v-if="form.errors.privacy_consent" class="text-xs text-red-500 -mt-3">È necessario il consenso per inviare la richiesta.</p>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="/" class="text-sm text-stone-400 hover:text-stone-600">Annulla</a>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-2xl bg-sage-500 hover:bg-sage-600 text-white text-sm font-medium shadow-soft transition-colors disabled:opacity-60">
                        Invia richiesta
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
