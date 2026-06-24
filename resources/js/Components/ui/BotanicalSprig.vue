<script setup>
// Piccolo rametto line-art da affiancare ai titoli.
defineProps({
    klass: { type: String, default: 'w-8 h-8 text-sage' },
});
</script>

<template>
    <svg :class="klass" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.3"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 42c4-14 14-26 34-34" />
        <path d="M16 30c-3-2-6-1-9 1 2 3 5 4 8 2" />
        <path d="M22 23c-2-3-1-6 1-9 3 2 4 5 2 8" />
        <path d="M28 17c0-3 2-6 5-8 1 3 0 7-3 9" />
        <path d="M33 13c2-2 5-3 8-3-1 3-3 5-6 6" />
    </svg>
</template>
