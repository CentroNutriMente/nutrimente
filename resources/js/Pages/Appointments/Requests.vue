<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { router } from '@inertiajs/vue3';

defineProps({
    requests: Array,
});

const confirm = (r) => {
    if (window.confirm(`Confermare l'appuntamento con ${r.name} il ${r.requested_date} alle ${r.requested_time}?`)) {
        router.post(route('appointment-requests.confirm', r.id), {}, { preserveScroll: true });
    }
};

const reject = (r) => {
    if (window.confirm(`Rifiutare la richiesta di ${r.name}?`)) {
        router.post(route('appointment-requests.reject', r.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Richieste di appuntamento">
        <template #header>
            <h1 class="font-serif text-2xl text-ink">Richieste di appuntamento</h1>
            <p class="text-sm text-inkSoft mt-1">Richieste inviate dai pazienti dalla loro area personale.</p>
        </template>

        <Card v-if="!requests.length" class="text-center py-12">
            <p class="text-sm text-inkSoft">Nessuna richiesta di appuntamento in attesa.</p>
        </Card>

        <div v-else class="space-y-3">
            <Card v-for="r in requests" :key="r.id" class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-ink">{{ r.name }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blush text-blushDeep">
                            {{ r.requested_date }} · {{ r.requested_time }}
                        </span>
                    </div>
                    <div class="text-sm text-inkSoft mt-1 space-x-3">
                        <span v-if="r.email">✉️ {{ r.email }}</span>
                        <span v-if="r.phone">📞 {{ r.phone }}</span>
                    </div>
                    <p v-if="r.notes" class="text-sm text-inkSoft mt-2 italic">"{{ r.notes }}"</p>
                    <p v-if="r.created_at" class="text-xs text-gray-400 mt-1">Ricevuta il {{ r.created_at }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="confirm(r)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-ctrl text-sm font-medium bg-sage text-white hover:bg-sageDeep transition-colors">
                        Conferma
                    </button>
                    <button @click="reject(r)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-ctrl text-sm border border-line text-inkSoft hover:bg-red-50 hover:text-red-500 transition-colors">
                        Rifiuta
                    </button>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
