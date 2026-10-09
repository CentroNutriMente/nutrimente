<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({ status: Number });
const page = usePage();

const info = {
    403: { title: 'Accesso negato',            desc: 'Non hai i permessi per accedere a questa risorsa.' },
    404: { title: 'Pagina non trovata',        desc: 'La risorsa che cerchi non esiste o è stata rimossa.' },
    500: { title: 'Errore interno del server', desc: 'Qualcosa è andato storto. Riprova tra qualche istante.' },
    503: { title: 'Servizio non disponibile',  desc: 'Il servizio è momentaneamente in manutenzione.' },
};

const { title, desc } = info[props.status] ?? { title: 'Errore', desc: 'Si è verificato un errore imprevisto.' };

// Destinazione "casa" coerente col ruolo, per non rimandare l'utente su una
// pagina a cui non ha accesso (es. system_admin → dashboard = 403).
const roles = computed(() => page.props.userRoles ?? []);
const isAuth = computed(() => !! page.props.auth?.user);

const home = computed(() => {
    if (! isAuth.value) return { href: '/', label: 'Torna alla home' };
    if (roles.value.includes('system_admin')) return { href: '/utenti', label: 'Torna alla gestione utenti' };
    const clinical = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];
    if (roles.value.some(r => clinical.includes(r))) return { href: '/dashboard', label: 'Torna alla dashboard' };
    return { href: '/mia-area', label: 'Torna alla tua area' };
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4">
        <img src="/logo.jpeg" alt="NutriMente" class="h-12 w-auto mb-10 opacity-80" />

        <div class="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>

        <p class="text-7xl font-bold text-gray-100 mb-1 leading-none">{{ status }}</p>
        <h1 class="text-xl font-semibold text-gray-800 mb-2">{{ title }}</h1>
        <p class="text-sm text-gray-500 mb-8 max-w-xs text-center">{{ desc }}</p>

        <a :href="home.href"
            class="px-5 py-2.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
            {{ home.label }}
        </a>
        <a v-if="home.href !== '/'" href="/" class="mt-3 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            Vai alla landing
        </a>
    </div>
</template>
