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
const statusTone = { attivo: 'bg-sage-400', in_partenza: 'bg-lavender-400', concluso: 'bg-stone-300' };
const statusLabel = { attivo: 'Attivo', in_partenza: 'In partenza', concluso: 'Concluso' };
</script>

<template>
    <AppLayout title="Gruppi">
        <WatercolorHeader class="pb-2">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 px-2 pt-4 pb-6">
                <div>
                    <h1 class="font-serif text-4xl text-stone-800 leading-none">Gruppi di aiuto e sostegno</h1>
                    <p class="text-stone-500 mt-2">Gestisci i tuoi gruppi e le iscrizioni.</p>
                </div>
                <Link :href="route('groups.create')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-sage-500 hover:bg-sage-600 text-white text-sm font-medium shadow-soft transition-colors self-start sm:self-auto whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Nuovo gruppo
                </Link>
            </div>
        </WatercolorHeader>

        <!-- Filtri -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2 flex-wrap">
                <button v-for="c in filterChips" :key="c.key" @click="setStatus(c.key)"
                    :class="[activeStatus === c.key ? 'bg-sage-500 text-white border-sage-500' : 'bg-white text-stone-500 border-cream-200 hover:border-sage-200', 'px-4 py-1.5 rounded-full text-sm border transition-colors']">
                    {{ c.label }}
                </button>
            </div>
            <div class="relative">
                <input v-model="search" @keyup.enter="apply" type="text" placeholder="Cerca gruppo..."
                    class="w-full sm:w-56 pl-4 pr-9 py-2 rounded-2xl border border-cream-200 bg-white text-sm text-stone-700 placeholder-stone-400 focus:border-sage-300 focus:ring-sage-200" />
                <button @click="apply" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400">
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
                             :class="g.tone === 'sage' ? 'bg-sage-100' : g.tone === 'lavender' ? 'bg-lavender-100' : 'bg-peach-100'">
                            <IconCircle :icon="leafIcon" :tone="g.tone" size="lg" />
                        </div>
                        <h3 class="font-serif text-xl text-stone-800">{{ g.name }}</h3>
                        <p class="text-sm text-stone-500 mt-2 leading-relaxed line-clamp-3">{{ g.description }}</p>
                        <div class="flex items-center gap-3 mt-4 text-xs text-stone-400">
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
            <p class="text-stone-400">Nessun gruppo trovato. Crea il primo con "Nuovo gruppo".</p>
        </Card>
    </AppLayout>
</template>
