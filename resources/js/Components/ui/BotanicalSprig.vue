<script setup>
// Motivo botanico via sprite (<use href="#bot-...">). Ricolora con `color`/text-*.
// Richiede <BotanicalSprite> montato nella pagina.
defineProps({
    id:    { type: String, default: 'bot-sprig' },   // bot-sprig | bot-branch | bot-lavender | bot-sprout | bot-dots | bot-heart
    klass: { type: String, default: 'w-8 h-8 text-sage' },
});
</script>

<template>
    <svg :class="klass" aria-hidden="true">
        <use :href="'#' + id" />
    </svg>
</template>
