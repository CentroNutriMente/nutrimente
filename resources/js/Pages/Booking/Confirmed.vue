<script setup>
const props = defineProps({ booking: Object });
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl p-10 max-w-md w-full text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Appuntamento confermato!</h1>
            <p class="text-gray-500 text-sm mb-6">
                Hai confermato l'appuntamento con
                <strong>{{ booking.patient_name }} {{ booking.patient_surname }}</strong>
                per il {{ new Date(booking.requested_date).toLocaleDateString('it-IT', {weekday:'long', day:'numeric', month:'long', year:'numeric'}) }}
                alle {{ booking.requested_time }}.
            </p>
            <p class="text-xs text-gray-400">
                È stata inviata una email di conferma a <strong>{{ booking.patient_email }}</strong>.
            </p>
            <a href="/dashboard"
                class="mt-6 inline-block bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                Vai al pannello
            </a>
        </div>
    </div>
</template>
