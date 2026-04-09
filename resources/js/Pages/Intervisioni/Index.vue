<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ intervisioni: Object });

const statusLabel = { draft: 'Bozza', scheduled: 'Programmata', completed: 'Completata' };
const statusColor = {
    draft: 'bg-gray-100 text-gray-500',
    scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
};

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AppLayout title="Intervisioni">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Intervisioni</h1>
                <Link :href="route('intervisioni.create')"
                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    + Nuova intervisione
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="intervisioni.data.length === 0" class="py-16 text-center text-gray-400 text-sm">
                Nessuna intervisione ancora. Creane una per iniziare.
            </div>
            <table v-else class="w-full text-sm">
                <thead class="border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Titolo</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Paziente</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">Programmata</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Stato</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="iv in intervisioni.data" :key="iv.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-gray-800">{{ iv.title }}</td>
                        <td class="px-5 py-3.5 text-gray-500 hidden md:table-cell">{{ iv.patient_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-500 hidden lg:table-cell">{{ formatDate(iv.scheduled_at) }}</td>
                        <td class="px-5 py-3.5">
                            <span :class="[statusColor[iv.status] ?? 'bg-gray-100 text-gray-500', 'text-xs px-2 py-0.5 rounded-full font-medium']">
                                {{ statusLabel[iv.status] ?? iv.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <Link :href="route('intervisioni.show', iv.id)" class="text-xs text-purple-600 hover:underline">
                                Apri →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="intervisioni.last_page > 1" class="px-5 py-3 border-t border-gray-100 flex items-center gap-2">
                <Link v-for="link in intervisioni.links" :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[link.active ? 'bg-purple-600 text-white' : 'text-gray-500 hover:bg-gray-50', 'text-xs px-3 py-1.5 rounded-lg transition-colors']"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
