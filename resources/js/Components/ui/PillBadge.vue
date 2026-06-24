<script setup>
defineProps({
    // tono: sage | lavender | peach | neutral
    tone: { type: String, default: 'neutral' },
});

const tones = {
    sage:    'bg-sage-100 text-sage-700',
    lavender:'bg-lavender-100 text-lavender-600',
    peach:   'bg-peach-100 text-peach-500',
    neutral: 'bg-cream-200 text-stone-500',
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
