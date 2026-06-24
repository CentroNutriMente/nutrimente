<script setup>
import IconCircle from './IconCircle.vue';

defineProps({
    label: String,
    value: [String, Number],
    sub: String,
    delta: String,
    deltaPositive: { type: Boolean, default: true },
    icon: String,
    tone: { type: String, default: 'sage' },
});
</script>

<template>
    <div class="bg-white rounded-xl2 border border-line p-5">
        <div class="flex items-start gap-3">
            <IconCircle :icon="icon" :tone="tone" />
            <div class="min-w-0">
                <p class="text-xs text-inkSoft font-medium">{{ label }}</p>
                <p class="text-3xl font-serif font-semibold text-ink leading-tight mt-0.5">{{ value }}</p>
                <p v-if="sub" class="text-xs text-inkSoft">{{ sub }}</p>
            </div>
        </div>
        <p v-if="delta" class="text-xs mt-3" :class="deltaPositive ? 'text-sage' : 'text-blushDeep'">
            {{ delta }}
        </p>
    </div>
</template>
