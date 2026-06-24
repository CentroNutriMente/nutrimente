<script setup>
defineProps({
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <!-- Soft = bordo sottile + spazi bianchi, niente ombre marcate -->
    <div class="bg-white rounded-xl2 border border-line" :class="padded ? 'p-6' : ''">
        <slot />
    </div>
</template>
