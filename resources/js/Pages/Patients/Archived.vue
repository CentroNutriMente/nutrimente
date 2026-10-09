<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    patients: Array,
    filters: Object,
});

const search = ref(props.filters.search ?? '');

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('patients.archived'), { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 350);
});

const restore = (p) => {
    if (confirm(`Riportare ${p.last_name} ${p.first_name} nell'elenco dei pazienti?`)) {
        router.post(route('patients.restore', p.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Archivio pazienti">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800">Pazienti eliminati</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Cartelle archiviate: fuori dall'elenco generale, ma consultabili.</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="search" type="text" placeholder="Cerca per nome, cognome, codice fiscale..."
                       class="pl-9 pr-4 py-2 w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Paziente</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Contatto</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Creato da</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Archiviato il</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="patients.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">Nessuna cartella archiviata.</td>
                        </tr>
                        <tr v-for="p in patients" :key="p.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <Link :href="route('patients.show', p.id)" class="font-medium text-gray-900 hover:text-purple-600">
                                    {{ p.last_name }} {{ p.first_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                                <div>{{ p.email ?? '' }}</div>
                                <div>{{ p.phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span v-if="p.creator" class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full font-medium">{{ p.creator }}</span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ p.deleted_at ?? '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <Link :href="route('patients.show', p.id)" class="text-xs text-purple-600 hover:underline mr-3">Consulta</Link>
                                <button @click="restore(p)" class="text-xs text-gray-400 hover:underline">Ripristina</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
