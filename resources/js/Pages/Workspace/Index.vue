<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Workspace">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Workspace</h1>
        </template>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <p class="font-medium text-gray-500 mb-1">Workspace in arrivo</p>
            <p class="text-sm">Qui troverai il calendario social, i post del team e gli strumenti di marketing.</p>
        </div>
    </AppLayout>
</template>
