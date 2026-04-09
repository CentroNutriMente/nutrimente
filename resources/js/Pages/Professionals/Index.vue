<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ professionals: Array });

const roleLabel = {
    admin: 'Admin',
    psicologo: 'Psicologo',
    nutrizionista: 'Nutrizionista',
    osteopata: 'Osteopata',
    collaboratore: 'Collaboratore',
};

const roleColor = {
    admin: 'bg-purple-100 text-purple-700',
    psicologo: 'bg-blue-100 text-blue-700',
    nutrizionista: 'bg-green-100 text-green-700',
    osteopata: 'bg-amber-100 text-amber-700',
    collaboratore: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <AppLayout title="Professionisti">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Professionisti</h1>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="p in professionals"
                :key="p.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-start gap-4"
            >
                <img :src="p.profile_photo_url" :alt="p.name" class="w-12 h-12 rounded-full object-cover shrink-0" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-gray-800">{{ p.title ? p.title + ' ' : '' }}{{ p.name }}</span>
                        <span :class="[roleColor[p.role] ?? 'bg-gray-100 text-gray-600', 'text-xs px-2 py-0.5 rounded-full font-medium']">
                            {{ roleLabel[p.role] ?? p.role }}
                        </span>
                    </div>
                    <div v-if="p.category" class="text-xs text-gray-400 mt-0.5 capitalize">{{ p.category }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ p.email }}</div>
                    <div class="flex items-center gap-3 mt-3">
                        <span :class="p.is_bookable ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="p.is_bookable ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'" />
                            </svg>
                            {{ p.is_bookable ? 'Prenotabile' : 'Non prenotabile' }}
                        </span>
                        <Link :href="route('professionals.show', p.id)" class="text-xs text-purple-600 hover:underline ml-auto">
                            Modifica profilo →
                        </Link>
                    </div>
                </div>
            </div>

            <div v-if="professionals.length === 0" class="col-span-full bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400 text-sm">
                Nessun professionista ancora. Invita i colleghi dal menu Team.
            </div>
        </div>
    </AppLayout>
</template>
