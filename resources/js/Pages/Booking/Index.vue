<script setup>
const props = defineProps({ professionals: Array });

const roleLabel = {
    psicologo:     'Psicologo',
    nutrizionista: 'Nutrizionista',
    osteopata:     'Osteopata',
    collaboratore: 'Collaboratore',
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Header / Hero -->
        <header class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-5xl mx-auto px-4 py-8 flex flex-col items-center text-center gap-3">
                <!-- Logo placeholder -->
                <div class="w-20 h-20 rounded-2xl bg-purple-600 flex items-center justify-center shadow-lg mb-2">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">NutriMente</h1>
                <p class="text-gray-500 max-w-xl">Il tuo centro di riferimento per la salute psicologica e nutrizionale. Scegli il professionista e prenota il tuo appuntamento online.</p>
            </div>
        </header>

        <!-- Professionals grid -->
        <main class="max-w-5xl mx-auto px-4 py-12">
            <h2 class="text-xl font-semibold text-gray-700 mb-6">I nostri professionisti</h2>

            <div v-if="professionals.length === 0" class="text-center text-gray-400 py-16">
                Nessun professionista disponibile al momento.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a
                    v-for="p in professionals"
                    :key="p.id"
                    :href="`/prenota/${p.slug}`"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col items-center text-center gap-3 hover:shadow-md hover:-translate-y-0.5 transition-all cursor-pointer group"
                >
                    <img :src="p.photo" :alt="p.name"
                        class="w-24 h-24 rounded-full object-cover ring-2 ring-purple-100 group-hover:ring-purple-300 transition-all" />

                    <div>
                        <div class="font-semibold text-gray-800 text-lg">
                            {{ p.title ? p.title + ' ' : '' }}{{ p.name }}
                        </div>
                        <div class="text-sm text-purple-600 font-medium mt-0.5">
                            {{ p.category ?? roleLabel[p.role] ?? p.role }}
                        </div>
                    </div>

                    <p v-if="p.bio" class="text-sm text-gray-400 line-clamp-3 leading-relaxed">{{ p.bio }}</p>

                    <span class="mt-auto text-xs font-semibold text-purple-600 group-hover:underline">
                        Prenota appuntamento →
                    </span>
                </a>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-100 mt-16">
            <div class="max-w-5xl mx-auto px-4 py-6 flex items-center justify-between text-xs text-gray-400">
                <span>© {{ new Date().getFullYear() }} NutriMente</span>
                <a href="/login" class="hover:text-gray-600">Accesso professionisti</a>
            </div>
        </footer>
    </div>
</template>
