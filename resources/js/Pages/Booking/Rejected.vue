<script setup>
const props = defineProps({ patient_name: String });
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl p-10 max-w-md w-full text-center">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Richiesta rifiutata</h1>
            <p class="text-gray-500 text-sm mb-6">
                Hai rifiutato la richiesta di appuntamento di <strong>{{ patient_name }}</strong>.
            </p>
            <a href="/dashboard"
                class="inline-block bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                Vai al pannello
            </a>
        </div>
    </div>
</template>
