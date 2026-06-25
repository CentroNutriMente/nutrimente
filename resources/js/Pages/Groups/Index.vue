<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import IconCircle from '@/Components/ui/IconCircle.vue';
import WatercolorHeader from '@/Components/ui/WatercolorHeader.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    groups: Array,
    categories: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.q || '');
const activeStatus = ref(props.filters.status || 'tutti');

const filterChips = [
    { key: 'tutti', label: 'Tutti' },
    { key: 'attivo', label: 'Attivi' },
    { key: 'in_partenza', label: 'In partenza' },
    { key: 'concluso', label: 'Conclusi' },
];

const apply = () => {
    router.get(route('groups.index'), { status: activeStatus.value, q: search.value.trim() }, { preserveState: true, replace: true });
};
const setStatus = (s) => { activeStatus.value = s; apply(); };

const leafIcon = 'M12 3c1.5 2 4 3.5 4 7a4 4 0 1 1-8 0c0-3.5 2.5-5 4-7Z';
const statusTone = { attivo: 'bg-sage', in_partenza: 'bg-lavender', concluso: 'bg-line' };
const statusLabel = { attivo: 'Attivo', in_partenza: 'In partenza', concluso: 'Concluso' };
</script>

<template>
    <AppLayout title="Gruppi">
        <WatercolorHeader class="pb-2">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 px-2 pt-4 pb-6">
                <div>
                    <h1 class="font-serif text-4xl text-ink leading-none">Gruppi di aiuto e sostegno</h1>
                    <p class="text-inkSoft mt-2">Gestisci i tuoi gruppi e le iscrizioni.</p>
                </div>
                <Link :href="route('groups.create')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors self-start sm:self-auto whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Nuovo gruppo
                </Link>
            </div>
        </WatercolorHeader>

        <!-- Filtri -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2 flex-wrap">
                <button v-for="c in filterChips" :key="c.key" @click="setStatus(c.key)"
                    :class="[activeStatus === c.key ? 'bg-sage text-white border-sage' : 'bg-white text-inkSoft border-line hover:border-sage', 'px-4 py-1.5 rounded-full text-sm border transition-colors']">
                    {{ c.label }}
                </button>
            </div>
            <div class="relative">
                <input v-model="search" @keyup.enter="apply" type="text" placeholder="Cerca gruppo..."
                    class="w-full sm:w-56 pl-4 pr-9 py-2 rounded-ctrl border border-line bg-cardWarm text-sm text-ink placeholder-inkSoft focus:border-sage focus:ring-sageLight" />
                <button @click="apply" class="absolute right-3 top-1/2 -translate-y-1/2 text-inkSoft">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                </button>
            </div>
        </div>

        <!-- Griglia gruppi -->
        <div v-if="groups.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <Link v-for="g in groups" :key="g.id" :href="route('groups.show', g.id)">
                <Card class="h-full hover:shadow-lg transition-shadow text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4"
                             :class="g.tone === 'sage' ? 'bg-sageLight' : g.tone === 'lavender' ? 'bg-lavenderLight' : 'bg-blush'">
                            <IconCircle :icon="leafIcon" :tone="g.tone" size="lg" />
                        </div>
                        <h3 class="font-serif text-xl text-ink">{{ g.name }}</h3>
                        <p class="text-sm text-inkSoft mt-2 leading-relaxed line-clamp-3">{{ g.description }}</p>
                        <div class="flex items-center gap-3 mt-4 text-xs text-inkSoft">
                            <span>{{ g.enrolled }} iscritti / {{ g.capacity }} posti</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :class="statusTone[g.status]"></span>
                                {{ statusLabel[g.status] }}
                            </span>
                        </div>
                    </div>
                </Card>
            </Link>
        </div>
        <Card v-else class="text-center py-16">
            <p class="text-inkSoft">Nessun gruppo trovato. Crea il primo con "Nuovo gruppo".</p>
        </Card>
    </AppLayout>
</template>
