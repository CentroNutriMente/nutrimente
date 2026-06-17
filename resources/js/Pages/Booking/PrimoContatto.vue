<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const HOW_FOUND = [
    { value: 'passaparola', label: 'Passaparola / Biglietti da visita' },
    { value: 'social',      label: 'Social' },
    { value: 'miodottore',  label: 'MioDottore' },
];

const CONTACT_METHOD = [
    { value: 'whatsapp_tel_mail', label: 'WhatsApp / Telefono / Mail' },
    { value: 'social',            label: 'Social' },
    { value: 'miodottore',        label: 'MioDottore' },
];

const DAYS  = [
    { code: 'lun', label: 'Lun' },
    { code: 'mar', label: 'Mar' },
    { code: 'mer', label: 'Mer' },
    { code: 'gio', label: 'Gio' },
    { code: 'ven', label: 'Ven' },
    { code: 'sab', label: 'Sab' },
];
const HOURS = Array.from({ length: 12 }, (_, i) => String(8 + i).padStart(2, '0') + ':00'); // 08:00 → 19:00

const form = useForm({
    name:           '',
    surname:        '',
    phone:          '',
    email:          '',
    how_found:      [],
    contact_method: [],
    availability:   [],
    notes:          '',
});

function toggle(list, value) {
    const i = form[list].indexOf(value);
    if (i === -1) form[list].push(value);
    else form[list].splice(i, 1);
}

function slotKey(day, hour) { return `${day}|${hour}`; }
function hasSlot(day, hour) { return form.availability.includes(slotKey(day, hour)); }

function submit() {
    form.post('/prenota', {
        onSuccess: () => {
            form.reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Top nav bar -->
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-10">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="/logo.jpeg" alt="NutriMente" class="h-9 w-auto" />
                </a>
                <div class="flex items-center gap-2">
                    <a href="/mia-area"
                        class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors font-medium">
                        Area pazienti
                    </a>
                    <a href="/login"
                        class="text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 px-4 py-1.5 rounded-xl transition-colors">
                        Accesso professionisti
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-4 py-12 flex flex-col items-center text-center gap-4">
                <img src="/logo.jpeg" alt="NutriMente" class="h-28 w-auto mb-2" />
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Scheda Primo Contatto</h1>
                <p class="text-gray-500 max-w-xl leading-relaxed">
                    Lasciaci i tuoi dati e le tue disponibilità: un professionista del centro ti
                    contatterà a breve per fissare il primo colloquio.
                </p>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 py-10">

            <!-- Success flash -->
            <div v-if="page.props.flash?.success"
                class="mb-6 rounded-2xl bg-green-50 border border-green-200 text-green-800 px-5 py-4 text-sm">
                {{ page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- Dati anagrafici -->
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Dati anagrafici</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Cognome *</label>
                            <input v-model="form.surname" type="text"
                                class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                            <p v-if="form.errors.surname" class="text-xs text-red-500 mt-1">{{ form.errors.surname }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nome *</label>
                            <input v-model="form.name" type="text"
                                class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Telefono</label>
                            <input v-model="form.phone" type="tel"
                                class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Mail *</label>
                            <input v-model="form.email" type="email"
                                class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <!-- Come ci hai trovato / Modalità di contatto -->
                <div class="grid sm:grid-cols-2 gap-6">
                    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Come ci hai trovato</h2>
                        <div class="space-y-2.5">
                            <label v-for="o in HOW_FOUND" :key="o.value"
                                class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" :checked="form.how_found.includes(o.value)"
                                    @change="toggle('how_found', o.value)"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-400" />
                                {{ o.label }}
                            </label>
                        </div>
                    </section>

                    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Modalità di contatto</h2>
                        <div class="space-y-2.5">
                            <label v-for="o in CONTACT_METHOD" :key="o.value"
                                class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" :checked="form.contact_method.includes(o.value)"
                                    @change="toggle('contact_method', o.value)"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-400" />
                                {{ o.label }}
                            </label>
                        </div>
                    </section>
                </div>

                <!-- Giorni e fasce orarie -->
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Giorni e fasce orarie disponibili</h2>
                    <p class="text-xs text-gray-400 mb-4">Seleziona le caselle in cui di solito sei disponibile.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-center text-sm">
                            <thead>
                                <tr>
                                    <th class="p-2"></th>
                                    <th v-for="d in DAYS" :key="d.code"
                                        class="p-2 font-semibold text-gray-600">{{ d.label }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="hour in HOURS" :key="hour">
                                    <td class="p-2 text-gray-400 font-medium whitespace-nowrap">{{ hour }}</td>
                                    <td v-for="d in DAYS" :key="d.code" class="p-1">
                                        <button type="button" @click="toggle('availability', slotKey(d.code, hour))"
                                            class="w-full h-9 rounded-lg border text-xs transition-colors"
                                            :class="hasSlot(d.code, hour)
                                                ? 'bg-purple-600 border-purple-600 text-white'
                                                : 'bg-gray-50 border-gray-200 text-transparent hover:bg-purple-50 hover:border-purple-200'">
                                            ✓
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Note -->
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Note (facoltative)</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                        placeholder="Qualcosa che vuoi farci sapere…"></textarea>
                    <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
                </section>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white font-semibold py-3.5 rounded-2xl transition-colors">
                    {{ form.processing ? 'Invio in corso…' : 'Invia richiesta' }}
                </button>
            </form>
        </main>
    </div>
</template>
