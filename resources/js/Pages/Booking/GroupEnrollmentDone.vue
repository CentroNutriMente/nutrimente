<script setup>
import BotanicalSprite from '@/Components/ui/BotanicalSprite.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    name: String,
});
</script>

<template>
    <Head title="Richiesta inviata" />
    <div class="min-h-screen bg-cream flex flex-col">
        <BotanicalSprite />

        <!-- Top nav -->
        <nav class="border-b border-line">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-center">
                <a href="/" class="flex items-center gap-2 text-ink">
                    <svg class="w-6 h-8 text-sage" aria-hidden="true"><use href="#bot-sprout" /></svg>
                    <span class="font-serif text-xl lowercase">nutrimente</span>
                </a>
            </div>
        </nav>

        <!-- Contenuto centrale -->
        <main class="flex-1 flex items-center justify-center px-4 py-12">
            <div class="relative w-full max-w-lg text-center overflow-hidden">
                <!-- Wash watercolor -->
                <div class="pointer-events-none absolute inset-0 -z-10">
                    <span class="wash wash--lavender" style="top:-40px;left:20%;width:300px;height:240px;"></span>
                    <span class="wash wash--sage" style="top:0;right:18%;width:260px;height:220px;opacity:.7;"></span>
                </div>

                <!-- Cerchio con spunta + botanica -->
                <div class="relative mx-auto w-24 h-24 rounded-full bg-sageLight flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg class="absolute -right-3 -top-2 w-12 h-12 text-lavender" aria-hidden="true"><use href="#bot-lavender" /></svg>
                </div>

                <h1 class="font-serif text-4xl text-ink leading-tight">
                    Richiesta inviata<span v-if="name">, {{ name }}</span>!
                </h1>
                <p class="text-inkSoft mt-4 leading-relaxed max-w-md mx-auto">
                    Grazie per il tuo interesse. Abbiamo ricevuto la tua richiesta d'iscrizione al gruppo:
                    <span class="text-ink font-medium">verrai ricontattata/o a breve</span> per i dettagli e la conferma del posto.
                </p>
                <p class="text-inkMuted text-sm mt-3">
                    Puoi chiudere questa pagina in tranquillità.
                </p>

                <div class="mt-8">
                    <a href="/" class="inline-flex items-center gap-2 px-6 py-3 rounded-ctrl bg-sage hover:bg-sageDeep text-white shadow-btn text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 4l9 6.5M5 9.5V19a1 1 0 0 0 1 1h3v-5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5h3a1 1 0 0 0 1-1V9.5"/></svg>
                        Torna alla home
                    </a>
                </div>
            </div>
        </main>
    </div>
</template>
