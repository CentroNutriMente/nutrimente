<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ professionals: Array });

const showModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'psicologo',
    category: '',
});

function submit() {
    form.post(route('professionals.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

const roles = [
    { value: 'psicologo', label: 'Psicologo' },
    { value: 'nutrizionista', label: 'Nutrizionista' },
    { value: 'osteopata', label: 'Osteopata' },
    { value: 'collaboratore', label: 'Collaboratore' },
];

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
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Professionisti</h1>
                <button @click="showModal = true"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                    + Aggiungi professionista
                </button>
            </div>
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
                Nessun professionista ancora.
            </div>
        </div>

        <!-- Modal aggiungi professionista -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-semibold text-gray-800 text-lg">Aggiungi professionista</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nome completo *</label>
                        <input v-model="form.name" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Email *</label>
                        <input v-model="form.email" type="email" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Password temporanea *</label>
                        <input v-model="form.password" type="password" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Ruolo *</label>
                        <select v-model="form.role" class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Categoria (opzionale)</label>
                        <input v-model="form.category" type="text" placeholder="es. Psicologo clinico" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showModal = false" class="text-sm text-gray-500 px-4 py-2 rounded-xl hover:bg-gray-50">Annulla</button>
                        <button @click="submit" :disabled="form.processing"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium px-6 py-2 rounded-xl">
                            {{ form.processing ? 'Salvataggio…' : 'Aggiungi' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
