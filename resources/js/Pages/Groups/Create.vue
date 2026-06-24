<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import IconCircle from '@/Components/ui/IconCircle.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
    leaders: Array,
    cadences: Array,
    modalities: Array,
});

const form = useForm({
    category: props.categories[0]?.key ?? 'ansia',
    name: '',
    description: '',
    leader_user_id: props.leaders[0]?.id ?? null,
    cadence: props.cadences[0] ?? 'settimanale',
    modality: props.modalities[0] ?? 'presenza',
    location: '',
    capacity: 12,
    next_meeting_at: '',
    status: 'attivo',
});

const leafIcon = 'M12 3c1.5 2 4 3.5 4 7a4 4 0 1 1-8 0c0-3.5 2.5-5 4-7Z';
const submit = () => form.post(route('groups.store'));
const inputCls = 'w-full px-4 py-2.5 rounded-2xl border border-cream-200 bg-white text-sm text-stone-700 focus:border-sage-300 focus:ring-sage-200';
</script>

<template>
    <AppLayout title="Nuovo gruppo">
        <div class="max-w-3xl mx-auto">
            <Link :href="route('groups.index')" class="inline-flex items-center gap-1 text-sm text-stone-400 hover:text-stone-600 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Torna ai gruppi
            </Link>

            <h1 class="font-serif text-3xl text-stone-800 mb-6">Nuovo gruppo</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Categoria -->
                <Card>
                    <label class="block text-sm font-medium text-stone-600 mb-3">Categoria</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <button type="button" v-for="c in categories" :key="c.key" @click="form.category = c.key"
                            :class="['flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-colors text-center',
                                form.category === c.key ? 'border-sage-400 bg-sage-50' : 'border-cream-200 hover:border-sage-200']">
                            <IconCircle :icon="leafIcon" :tone="c.tone" />
                            <span class="text-sm font-medium text-stone-700">{{ c.label }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.category" class="text-xs text-red-500 mt-2">{{ form.errors.category }}</p>
                </Card>

                <Card class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Nome del gruppo *</label>
                        <input v-model="form.name" type="text" :class="inputCls" placeholder="Es. Gestione Ansia — gruppo del martedì" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-600 mb-1.5">Descrizione</label>
                        <textarea v-model="form.description" rows="3" :class="inputCls" placeholder="Un percorso di gruppo per..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Conduttore</label>
                            <select v-model="form.leader_user_id" :class="inputCls">
                                <option :value="null">—</option>
                                <option v-for="l in leaders" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Periodicità</label>
                            <select v-model="form.cadence" :class="inputCls">
                                <option v-for="c in cadences" :key="c" :value="c">{{ c.charAt(0).toUpperCase() + c.slice(1) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Modalità</label>
                            <select v-model="form.modality" :class="inputCls">
                                <option v-for="m in modalities" :key="m" :value="m">{{ m === 'presenza' ? 'In presenza' : 'Online' }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Luogo</label>
                            <input v-model="form.location" type="text" :class="inputCls" placeholder="Studio / link" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Posti totali *</label>
                            <input v-model="form.capacity" type="number" min="1" max="200" :class="inputCls" />
                            <p v-if="form.errors.capacity" class="text-xs text-red-500 mt-1">{{ form.errors.capacity }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 mb-1.5">Prossimo incontro</label>
                            <input v-model="form.next_meeting_at" type="datetime-local" :class="inputCls" />
                        </div>
                    </div>
                </Card>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('groups.index')" class="px-4 py-2.5 rounded-2xl text-sm text-stone-500 hover:bg-cream-200 transition-colors">Annulla</Link>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-2xl bg-sage-500 hover:bg-sage-600 text-white text-sm font-medium shadow-soft transition-colors disabled:opacity-60">
                        Crea gruppo
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
