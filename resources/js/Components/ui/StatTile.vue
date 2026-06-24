<script setup>
import IconCircle from './IconCircle.vue';

defineProps({
    label: String,
    value: [String, Number],
    sub: String,
    delta: String,            // es. "+ 5 questo mese"
    deltaPositive: { type: Boolean, default: true },
    icon: String,
    tone: { type: String, default: 'sage' },
});
</script>

<template>
    <div class="bg-white rounded-4xl border border-cream-200 shadow-soft p-5">
        <div class="flex items-start gap-3">
            <IconCircle :icon="icon" :tone="tone" />
            <div class="min-w-0">
                <p class="text-xs text-stone-400 font-medium">{{ label }}</p>
                <p class="text-3xl font-serif font-semibold text-stone-800 leading-tight mt-0.5">{{ value }}</p>
                <p v-if="sub" class="text-xs text-stone-400">{{ sub }}</p>
            </div>
        </div>
        <p v-if="delta" class="text-xs mt-3" :class="deltaPositive ? 'text-sage-600' : 'text-peach-500'">
            {{ delta }}
        </p>
    </div>
</template>
