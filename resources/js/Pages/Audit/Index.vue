<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const actionLabel = {
    login: 'Accesso',
    logout: 'Uscita',
    login_failed: 'Accesso fallito',
    viewed: 'Visualizzazione',
    created: 'Creazione',
    updated: 'Modifica',
    deleted: 'Eliminazione',
    downloaded: 'Download',
    exported: 'Esportazione',
};
const actionTone = {
    login: 'bg-sageLight text-sageDeep',
    logout: 'bg-cream text-inkSoft',
    login_failed: 'bg-red-50 text-red-600',
    viewed: 'bg-lavenderLight text-lavender',
    created: 'bg-sageLight text-sageDeep',
    updated: 'bg-blue-50 text-blue-600',
    deleted: 'bg-red-50 text-red-600',
    downloaded: 'bg-blush text-blushDeep',
    exported: 'bg-blush text-blushDeep',
};

const filterAction = (a) => router.get(route('audit.index'), a ? { action: a } : {}, { preserveState: true, replace: true });
</script>

<template>
    <AppLayout title="Registro operazioni">
        <template #header>
            <h1 class="font-serif text-2xl text-ink">Registro operazioni</h1>
            <p class="text-sm text-inkSoft mt-1">Tracciamento di accessi e operazioni sui dati (audit log GDPR).</p>
        </template>

        <div class="flex items-center gap-2 mb-4 flex-wrap">
            <button @click="filterAction(null)" :class="['text-xs px-3 py-1.5 rounded-full border', !filters.action ? 'bg-ink text-white border-ink' : 'border-line text-inkSoft hover:bg-cream']">Tutte</button>
            <button v-for="(lbl, key) in actionLabel" :key="key" @click="filterAction(key)"
                :class="['text-xs px-3 py-1.5 rounded-full border', filters.action === key ? 'bg-ink text-white border-ink' : 'border-line text-inkSoft hover:bg-cream']">
                {{ lbl }}
            </button>
        </div>

        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-inkSoft border-b border-line">
                            <th class="font-medium px-5 py-3">Data e ora</th>
                            <th class="font-medium px-5 py-3">Utente</th>
                            <th class="font-medium px-5 py-3">Operazione</th>
                            <th class="font-medium px-5 py-3">Oggetto</th>
                            <th class="font-medium px-5 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="l in logs.data" :key="l.id" class="hover:bg-cream">
                            <td class="px-5 py-3 text-inkSoft whitespace-nowrap">{{ l.at }}</td>
                            <td class="px-5 py-3 text-ink">{{ l.user }}</td>
                            <td class="px-5 py-3">
                                <span :class="['inline-block px-2.5 py-0.5 rounded-full text-xs', actionTone[l.action] || 'bg-cream text-inkSoft']">
                                    {{ actionLabel[l.action] || l.action }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-inkSoft">
                                <template v-if="l.model">{{ l.model }} #{{ l.modelId }}</template>
                                <template v-else-if="l.email">{{ l.email }}</template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-5 py-3 text-inkSoft">{{ l.ip || '—' }}</td>
                        </tr>
                        <tr v-if="logs.data.length === 0"><td colspan="5" class="px-5 py-8 text-center text-inkSoft">Nessuna operazione registrata.</td></tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <div v-if="logs.links && logs.links.length > 3" class="flex flex-wrap gap-1 mt-4">
            <template v-for="link in logs.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" v-html="link.label"
                    :class="['px-3 py-1.5 rounded-ctrl text-sm', link.active ? 'bg-ink text-white' : 'border border-line text-inkSoft hover:bg-cream']" />
                <span v-else v-html="link.label" class="px-3 py-1.5 rounded-ctrl text-sm text-gray-300" />
            </template>
        </div>
    </AppLayout>
</template>
