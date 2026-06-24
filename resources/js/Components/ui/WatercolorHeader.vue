<script setup>
// Banda d'intestazione con sfumature watercolor (lavanda/blush/sage) + ramo botanico.
defineProps({
    branch: { type: Boolean, default: true },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-xl2">
        <!-- Wash watercolor -->
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-20 left-1/4 w-72 h-72 rounded-full blur-3xl opacity-50"
                 style="background: radial-gradient(circle at 30% 30%, #EFEAF8 0%, transparent 70%)"></div>
            <div class="absolute -top-16 left-1/2 w-72 h-72 rounded-full blur-3xl opacity-50"
                 style="background: radial-gradient(circle at 50% 40%, #F3DCD4 0%, transparent 70%)"></div>
            <div class="absolute -top-24 right-10 w-64 h-64 rounded-full blur-3xl opacity-50"
                 style="background: radial-gradient(circle at 60% 40%, #E9EEE3 0%, transparent 70%)"></div>
            <svg class="absolute top-6 left-1/3 w-24 h-10 text-lavender/70" viewBox="0 0 100 40" fill="currentColor" aria-hidden="true">
                <circle cx="8" cy="20" r="2"/><circle cx="24" cy="10" r="2"/><circle cx="40" cy="24" r="2"/>
                <circle cx="58" cy="8" r="2"/><circle cx="74" cy="22" r="2"/><circle cx="92" cy="14" r="2"/>
            </svg>
        </div>

        <svg v-if="branch" class="pointer-events-none absolute -top-2 right-2 w-40 h-40 text-sage/50 hidden sm:block"
             viewBox="0 0 160 160" fill="none" stroke="currentColor" stroke-width="1.1"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M150 8c-30 6-58 24-78 50C58 78 48 102 44 128"/>
            <path d="M120 26c6-6 14-9 23-9-2 8-8 14-16 16"/>
            <path d="M101 44c5-7 6-15 4-23 7 4 11 12 9 20"/>
            <path d="M84 66c2-8 0-16-5-22 8 1 14 8 15 16"/>
            <path d="M70 90c0-8-4-15-10-19 7-1 15 4 17 12"/>
            <path d="M58 116c-3-7-9-12-16-13 5-5 14-4 19 3"/>
        </svg>

        <div class="relative">
            <slot />
        </div>
    </div>
</template>
