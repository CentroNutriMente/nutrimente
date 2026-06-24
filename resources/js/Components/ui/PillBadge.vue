<script setup>
defineProps({
    // tono: sage | lavender | blush | neutral
    tone: { type: String, default: 'neutral' },
});

// Badge/contatori secondari: default su bg-cream + text-inkSoft, pillola
const tones = {
    sage:     'bg-sageLight text-sage',
    lavender: 'bg-lavenderLight text-lavender',
    blush:    'bg-blush text-blushDeep',
    peach:    'bg-blush text-blushDeep',
    neutral:  'bg-cream text-inkSoft',
};
</script>

<template>
    <span
        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium leading-none whitespace-nowrap"
        :class="tones[tone] ?? tones.neutral"
    >
        <slot />
    </span>
</template>
