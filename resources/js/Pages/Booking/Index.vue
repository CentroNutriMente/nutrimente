<script setup>
const props = defineProps({ professionals: Array });
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Top nav bar -->
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-10">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="/logo.jpeg" alt="NutriMente" class="h-9 w-auto" />
                </a>
                <div class="flex items-center gap-2">
                    <a href="/mia-area"
                        class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors font-medium">
                        Area pazienti
                    </a>
                    <a href="/login"
                        class="text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 px-4 py-1.5 rounded-xl transition-colors">
                        Accesso professionisti
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-4 py-12 flex flex-col items-center text-center gap-4">
                <img src="/logo.jpeg" alt="NutriMente" class="h-36 w-auto mb-2" />
                <h1 class="text-4xl font-bold text-gray-900 tracking-tight">Centro NutriMente</h1>
                <p class="text-lg text-gray-500 max-w-2xl leading-relaxed">
                    Uno spazio di cura che mette al centro la persona nella sua interezza.
                </p>
            </div>
        </header>

        <!-- Mission / About -->
        <section class="bg-white border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-4 py-12 text-center space-y-5">
                <h2 class="text-2xl font-bold text-gray-800">Chi siamo</h2>
                <p class="text-gray-600 leading-relaxed">
                    Centro NutriMente è uno studio associato multidisciplinare che integra psicologia clinica, nutrizione
                    e benessere in un unico percorso di cura. Crediamo che ogni persona meriti un ascolto autentico e un
                    intervento professionale costruito su misura, in grado di rispondere sia alla dimensione emotiva che
                    a quella fisica della salute.
                </p>
                <p class="text-gray-600 leading-relaxed">
                    Il nostro team è composto da professionisti qualificati che collaborano attivamente tra loro, con
                    l'obiettivo di offrirti un supporto completo, integrato e profondamente rispettoso della tua unicità.
                    Perché prendersi cura di sé non è un lusso: è un diritto.
                </p>
                <div class="flex justify-center gap-8 pt-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">3</div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Professionisti</div>
                    </div>
                    <div class="w-px bg-gray-100"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">100%</div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Online &amp; In presenza</div>
                    </div>
                    <div class="w-px bg-gray-100"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">∞</div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">Cura personalizzata</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Professionals -->
        <main class="max-w-5xl mx-auto px-4 py-14">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Il nostro team</h2>
            <p class="text-center text-gray-400 text-sm mb-10">Scegli il professionista e prenota direttamente online.</p>

            <div v-if="professionals.length === 0" class="text-center text-gray-400 py-16">
                Nessun professionista disponibile al momento.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <a
                    v-for="p in professionals"
                    :key="p.id"
                    :href="`/prenota/${p.slug}`"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-7 flex flex-col items-center text-center gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all cursor-pointer group"
                >
                    <img :src="p.photo" :alt="p.name"
                        class="w-28 h-28 rounded-full object-cover ring-2 ring-purple-100 group-hover:ring-purple-300 transition-all" />

                    <div>
                        <div class="font-bold text-gray-900 text-lg leading-snug">
                            {{ p.title ? p.title + ' ' : '' }}{{ p.name.split(' ').slice(-1)[0] }}
                        </div>
                        <div class="text-sm text-purple-600 font-medium mt-1">
                            {{ p.category }}
                        </div>
                    </div>

                    <p v-if="p.bio" class="text-sm text-gray-500 line-clamp-4 leading-relaxed text-left">{{ p.bio }}</p>

                    <span class="mt-auto inline-flex items-center gap-1 text-xs font-semibold text-purple-600 group-hover:underline">
                        Scopri e prenota
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-100 mt-8">
            <div class="max-w-5xl mx-auto px-4 py-6 flex items-center justify-between text-xs text-gray-400">
                <span>© {{ new Date().getFullYear() }} Centro NutriMente</span>
                <div class="flex items-center gap-4">
                    <a href="/mia-area" class="hover:text-gray-600">Area pazienti</a>
                    <a href="/login" class="hover:text-gray-600">Accesso professionisti</a>
                </div>
            </div>
        </footer>
    </div>
</template>
