<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    patients: Object,
    tags: Array,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const selectedTag = ref(props.filters.tag ?? '');

let searchTimer = null;
watch([search, selectedTag], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('patients.index'), {
            search: search.value || undefined,
            tag: selectedTag.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

const tagColor = (color) => ({ backgroundColor: color + '22', color });
</script>

<template>
    <AppLayout title="Pazienti">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Pazienti</h1>
                <Link
                    :href="route('patients.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuovo paziente
                </Link>
            </div>
        </template>

        <!-- Filtri -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-wrap gap-3">
            <div class="flex-1 min-w-48">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cerca per nome, cognome, codice fiscale..."
                        class="pl-9 pr-4 py-2 w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                </div>
            </div>
            <select
                v-model="selectedTag"
                class="border border-gray-200 rounded-lg px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
                <option value="">Tutti i tag</option>
                <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
            </select>
        </div>

        <!-- Tabella -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Paziente</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Codice Fiscale</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Contatto</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Tag</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Creato da</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Stato</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="patients.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Nessun paziente trovato.</td>
                    </tr>
                    <tr
                        v-for="patient in patients.data"
                        :key="patient.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-4 py-3">
                            <Link :href="route('patients.show', patient.id)" class="font-medium text-gray-900 hover:text-purple-600">
                                {{ patient.last_name }} {{ patient.first_name }}
                            </Link>
                            <div v-if="patient.date_of_birth" class="text-xs text-gray-400">
                                {{ new Date(patient.date_of_birth).toLocaleDateString('it-IT') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ patient.codice_fiscale ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            <div>{{ patient.email ?? '' }}</div>
                            <div>{{ patient.phone ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-if="patient.diagnosis"
                                    class="text-xs px-2 py-0.5 rounded-full font-medium bg-rose-50 text-rose-700 border border-rose-100"
                                >{{ patient.diagnosis }}</span>
                                <span
                                    v-for="tag in patient.tags"
                                    :key="tag.id"
                                    :style="tagColor(tag.color)"
                                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                                >{{ tag.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="patient.creator" class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full font-medium">
                                {{ patient.creator.name }}
                            </span>
                            <span v-else class="text-xs text-gray-300">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="patient.is_active ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2 py-1 rounded-full font-medium">
                                {{ patient.is_active ? 'Attivo' : 'Archiviato' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('patients.show', patient.id)" class="text-xs text-purple-600 hover:underline mr-3">Apri</Link>
                            <Link :href="route('patients.edit', patient.id)" class="text-xs text-gray-400 hover:underline">Modifica</Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginazione -->
            <div v-if="patients.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    {{ patients.from }}–{{ patients.to }} di {{ patients.total }} pazienti
                </span>
                <div class="flex gap-1">
                    <Link
                        v-for="link in patients.links"
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
