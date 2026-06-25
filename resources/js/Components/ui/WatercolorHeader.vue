<script setup>
// Intestazione: due wash watercolor sfocate dietro + ramo botanico nell'angolo.
// Usa le helper .wash di tokens.css e lo sprite (#bot-branch / #bot-dots).
defineProps({
    branch: { type: Boolean, default: true },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-xl2">
        <!-- Wash watercolor (sfocate, bassa opacità) -->
        <div class="pointer-events-none absolute inset-0 -z-10">
            <span class="wash wash--lavender" style="top:-120px;left:18%;width:460px;height:320px;"></span>
            <span class="wash wash--blush"    style="top:-90px;left:46%;width:420px;height:300px;"></span>
            <span class="wash wash--sage"     style="top:-130px;right:8%;width:360px;height:300px;opacity:.7;"></span>
        </div>

        <!-- Ramo botanico nell'angolo (piccolo e sparso) -->
        <svg v-if="branch" class="pointer-events-none absolute -top-1 right-3 w-44 h-32 hidden sm:block" style="color:var(--sage);opacity:.7" aria-hidden="true">
            <use href="#bot-branch" />
        </svg>
        <svg class="pointer-events-none absolute top-6 left-1/3 w-16 h-8 hidden sm:block" style="color:var(--lav-soft);opacity:.8" aria-hidden="true">
            <use href="#bot-dots" />
        </svg>

        <div class="relative">
            <slot />
        </div>
    </div>
</template>
