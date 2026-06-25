<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    leaders: Array,
    cadences: Array,
    modalities: Array,
});

const form = useForm({
    name: '',
    edition: '',
    description: '',
    leader_user_id: props.leaders[0]?.id ?? null,
    cadence: props.cadences[0] ?? 'settimanale',
    modality: props.modalities[0] ?? 'presenza',
    location: '',
    capacity: 12,
    next_meeting_at: '',
    status: 'attivo',
});

const submit = () => form.post(route('groups.store'));
const inputCls = 'w-full px-4 py-2.5 rounded-ctrl border border-line bg-cardWarm text-sm text-ink focus:border-sage focus:ring-sageLight';
</script>

<template>
    <AppLayout title="Nuovo gruppo">
        <div class="max-w-3xl mx-auto">
            <Link :href="route('groups.index')" class="inline-flex items-center gap-1 text-sm text-inkSoft hover:text-ink mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Torna ai gruppi
            </Link>

            <h1 class="font-serif text-3xl text-ink mb-6">Nuovo gruppo</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <Card class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink mb-1.5">Titolo del gruppo *</label>
                            <input v-model="form.name" type="text" :class="inputCls" placeholder="Es. Gruppo supporto endometriosi e adenomiosi" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Edizione</label>
                            <input v-model="form.edition" type="text" :class="inputCls" placeholder="Edizione 1 2026" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Descrizione</label>
                        <textarea v-model="form.description" rows="3" :class="inputCls" placeholder="Un percorso di gruppo per..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Conduttrice</label>
                            <select v-model="form.leader_user_id" :class="inputCls">
                                <option :value="null">—</option>
                                <option v-for="l in leaders" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Periodicità</label>
                            <select v-model="form.cadence" :class="inputCls">
                                <option v-for="c in cadences" :key="c" :value="c">{{ c.charAt(0).toUpperCase() + c.slice(1) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Modalità</label>
                            <select v-model="form.modality" :class="inputCls">
                                <option v-for="m in modalities" :key="m" :value="m">{{ m === 'presenza' ? 'In presenza' : 'Online' }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Luogo</label>
                            <input v-model="form.location" type="text" :class="inputCls" placeholder="Studio / link" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Posti totali *</label>
                            <input v-model="form.capacity" type="number" min="1" max="200" :class="inputCls" />
                            <p v-if="form.errors.capacity" class="text-xs text-red-500 mt-1">{{ form.errors.capacity }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Prossimo incontro</label>
                            <input v-model="form.next_meeting_at" type="datetime-local" :class="inputCls" />
                        </div>
                    </div>
                </Card>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('groups.index')" class="px-4 py-2.5 rounded-ctrl text-sm text-inkSoft hover:bg-cream transition-colors">Annulla</Link>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors disabled:opacity-60">
                        Crea gruppo
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
