<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, nextTick, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    teamChannels: Array,
    colleagues: Array,
    currentUser: Object,
});

// Active channel state
const activeType = ref('team');      // 'team' | 'direct'
const activeId = ref('general');     // channel_id

const messages = ref([]);
const loading = ref(false);
const body = ref('');
const messagesEnd = ref(null);

const activeLabel = computed(() => {
    if (activeType.value === 'team') {
        return props.teamChannels.find(c => c.id === activeId.value)?.label ?? activeId.value;
    }
    return props.colleagues.find(c => c.id === Number(activeId.value))?.name ?? activeId.value;
});

async function selectChannel(type, id) {
    activeType.value = type;
    activeId.value = String(id);
    await loadMessages();
}

async function loadMessages() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('messages.load'), {
            params: { channel_type: activeType.value, channel_id: activeId.value },
        });
        messages.value = data;
        await nextTick();
        messagesEnd.value?.scrollIntoView({ behavior: 'instant' });
    } finally {
        loading.value = false;
    }
}

async function send() {
    if (!body.value.trim()) return;
    const text = body.value.trim();
    body.value = '';
    const { data } = await axios.post(route('messages.store'), {
        channel_type: activeType.value,
        channel_id: activeId.value,
        body: text,
    });
    messages.value.push(data);
    await nextTick();
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
}

function formatTime(dt) {
    const d = new Date(dt);
    const today = new Date();
    if (d.toDateString() === today.toDateString()) {
        return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' }) + ' ' +
        d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => loadMessages());
</script>

<template>
    <AppLayout title="Chat Team">
        <div class="flex h-[calc(100vh-120px)] bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Sidebar canali -->
            <div class="w-56 shrink-0 border-r border-gray-100 flex flex-col">
                <div class="px-4 pt-4 pb-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Canali team</p>
                </div>
                <div class="flex-1 overflow-y-auto space-y-0.5 px-2 pb-4">
                    <button
                        v-for="ch in teamChannels"
                        :key="ch.id"
                        @click="selectChannel('team', ch.id)"
                        :class="[
                            activeType === 'team' && activeId === ch.id
                                ? 'bg-purple-50 text-purple-700 font-medium'
                                : 'text-gray-600 hover:bg-gray-50',
                            'w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <span class="text-gray-400">#</span> {{ ch.label }}
                    </button>

                    <div class="pt-3 pb-1 px-1">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Messaggi diretti</p>
                    </div>
                    <button
                        v-for="col in colleagues"
                        :key="col.id"
                        @click="selectChannel('direct', col.id)"
                        :class="[
                            activeType === 'direct' && activeId === String(col.id)
                                ? 'bg-purple-50 text-purple-700 font-medium'
                                : 'text-gray-600 hover:bg-gray-50',
                            'w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <img :src="col.profile_photo_url" class="w-5 h-5 rounded-full object-cover shrink-0" />
                        <span class="truncate">{{ col.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Area messaggi -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Header -->
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                    <span class="font-semibold text-gray-800">{{ activeLabel }}</span>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <div v-if="loading" class="text-center text-gray-400 text-sm py-8">Caricamento…</div>
                    <div v-else-if="messages.length === 0" class="text-center text-gray-400 text-sm py-8">
                        Nessun messaggio ancora. Inizia la conversazione!
                    </div>
                    <template v-else>
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            :class="msg.sender_id === currentUser.id ? 'flex-row-reverse' : ''"
                            class="flex items-start gap-3"
                        >
                            <img :src="msg.sender_photo" class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5" />
                            <div :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'" class="flex flex-col max-w-[70%]">
                                <div class="flex items-baseline gap-1.5 mb-0.5"
                                    :class="msg.sender_id === currentUser.id ? 'flex-row-reverse' : ''">
                                    <span class="text-xs font-medium text-gray-700">{{ msg.sender_name }}</span>
                                    <span class="text-xs text-gray-400">{{ formatTime(msg.created_at) }}</span>
                                </div>
                                <div
                                    :class="msg.sender_id === currentUser.id
                                        ? 'bg-purple-600 text-white rounded-2xl rounded-tr-sm'
                                        : 'bg-gray-100 text-gray-800 rounded-2xl rounded-tl-sm'"
                                    class="px-4 py-2.5 text-sm leading-relaxed"
                                >
                                    {{ msg.body }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <div ref="messagesEnd" />
                </div>

                <!-- Input -->
                <div class="px-4 pb-4 pt-2 border-t border-gray-100">
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="body"
                            rows="1"
                            placeholder="Scrivi un messaggio…"
                            class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none"
                            @keydown.enter.exact.prevent="send"
                        />
                        <button
                            @click="send"
                            :disabled="!body.trim()"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white p-2.5 rounded-xl transition-colors shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Invio per inviare · Shift+Invio per andare a capo</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
