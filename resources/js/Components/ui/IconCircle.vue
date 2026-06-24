<script setup>
defineProps({
    // tono cromatico del cerchio pastello
    tone: { type: String, default: 'sage' },
    icon: { type: String, required: true },
    size: { type: String, default: 'md' }, // sm | md | lg
});

// Cerchio pastello + icona in tono pieno coordinato (mai colore pieno su bianco)
const tones = {
    sage:     'bg-sageLight text-sage',
    lavender: 'bg-lavenderLight text-lavender',
    blush:    'bg-blush text-blushDeep',
    peach:    'bg-blush text-blushDeep',
    neutral:  'bg-cream text-inkSoft',
};

const sizes = { sm: 'w-9 h-9', md: 'w-11 h-11', lg: 'w-14 h-14' };
const iconSizes = { sm: 'w-4 h-4', md: 'w-5 h-5', lg: 'w-6 h-6' };
</script>

<template>
    <div class="rounded-full flex items-center justify-center shrink-0" :class="[tones[tone] ?? tones.sage, sizes[size]]">
        <svg :class="iconSizes[size]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
            <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
        </svg>
    </div>
</template>
