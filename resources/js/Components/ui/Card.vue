<script setup>
defineProps({
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <!-- Card bianca su ground ivory: hairline border + whisper-soft shadow, raggio 22px -->
    <div class="bg-card rounded-xl2 border border-line shadow-soft" :class="padded ? 'p-6' : ''">
        <slot />
    </div>
</template>
