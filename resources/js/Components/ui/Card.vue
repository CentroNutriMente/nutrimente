<script setup>
defineProps({
    // padding interno
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="bg-white rounded-4xl border border-cream-200 shadow-soft"
        :class="padded ? 'p-6' : ''"
    >
        <slot />
    </div>
</template>
