<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    teamChannels:    Array,
    colleagues:      Array,
    unreadByChannel: Object,
    currentUser:     Object,
});

// ── Channel state ─────────────────────────────────────────────────────────────
const activeType = ref('team');
const activeId   = ref('general');

const messages    = ref([]);
const loading     = ref(false);
const loadError   = ref('');
const body        = ref('');
const messagesEnd = ref(null);

// Live unread counts — start from server value, updated by polling
const unread = ref({ ...(props.unreadByChannel ?? {}) });

let msgPollInterval    = null;
let unreadPollInterval = null;

// ── Helpers ───────────────────────────────────────────────────────────────────
function dmId(otherId) {
    const a = Math.min(props.currentUser.id, Number(otherId));
    const b = Math.max(props.currentUser.id, Number(otherId));
    return `dm_${a}_${b}`;
}

function formatTime(dt) {
    const d     = new Date(dt);
    const today = new Date();
    if (d.toDateString() === today.toDateString())
        return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' }) + ' ' +
           d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

const activeColleague = computed(() =>
    props.colleagues.find(c => dmId(c.id) === activeId.value)
);
const activeChannel = computed(() =>
    props.teamChannels.find(c => c.id === activeId.value)
);

// ── Select channel ─────────────────────────────────────────────────────────────
async function selectChannel(type, id) {
    const channelId = type === 'direct' ? dmId(id) : String(id);
    activeType.value = type;
    activeId.value   = channelId;
    await loadMessages();
    markRead(type, channelId);
}

// ── Load messages ──────────────────────────────────────────────────────────────
async function loadMessages(silent = false) {
    if (!silent) {
        loading.value   = true;
        loadError.value = '';
    }
    try {
        const params = { channel_type: activeType.value, channel_id: activeId.value };
        const { data } = await axios.get('/messages/load', { params });
        const atBottom = !messagesEnd.value ||
            messagesEnd.value.getBoundingClientRect().bottom <= window.innerHeight + 100;
        messages.value = Array.isArray(data) ? data : [];
        if (atBottom) {
            await nextTick();
            messagesEnd.value?.scrollIntoView({ behavior: 'instant' });
        }
    } catch (e) {
        if (!silent) loadError.value = `Errore ${e?.response?.status ?? ''}: ${e?.response?.data?.message ?? e?.message ?? 'impossibile caricare i messaggi'}`;
    } finally {
        if (!silent) loading.value = false;
    }
}

// ── Mark channel as read ───────────────────────────────────────────────────────
function markRead(channelType, channelId) {
    if (unread.value[channelId]) {
        delete unread.value[channelId];
        axios.post('/messages/read-channel', {
            channel_type: channelType,
            channel_id:   channelId,
        }).catch(() => {});
    }
}

// ── Poll unread counts ─────────────────────────────────────────────────────────
async function pollUnread() {
    try {
        const { data } = await axios.get('/messages/unread');
        // Merge: don't re-add badge for the currently open channel
        const fresh = data.unread ?? {};
        delete fresh[activeId.value];
        unread.value = { ...fresh };
    } catch {}
}

// ── Send message ───────────────────────────────────────────────────────────────
async function send() {
    if (!body.value.trim()) return;
    const text = body.value.trim();
    body.value = '';
    try {
        const { data } = await axios.post('/messages', {
            channel_type: activeType.value,
            channel_id:   activeId.value,
            body:         text,
        });
        messages.value.push(data);
        await nextTick();
        messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
    } catch {
        body.value = text;
    }
}

// ── Lifecycle ──────────────────────────────────────────────────────────────────
onMounted(() => {
    loadMessages();
    markRead(activeType.value, activeId.value);
    msgPollInterval    = setInterval(() => loadMessages(true), 6000);
    unreadPollInterval = setInterval(pollUnread, 8000);
});

onUnmounted(() => {
    clearInterval(msgPollInterval);
    clearInterval(unreadPollInterval);
});
</script>

<template>
    <AppLayout title="Chat Team">
        <div class="flex h-[calc(100vh-120px)] bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- ── Sidebar ─────────────────────────────────────────────────── -->
            <div class="w-60 shrink-0 border-r border-gray-100 flex flex-col bg-gray-50/60">

                <!-- Canali -->
                <div class="px-3 pt-4 pb-1">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-2 mb-1">Canali</p>
                </div>
                <div class="px-2 space-y-0.5">
                    <button
                        v-for="ch in teamChannels" :key="ch.id"
                        @click="selectChannel('team', ch.id)"
                        :class="[
                            activeType === 'team' && activeId === ch.id
                                ? 'bg-purple-100 text-purple-800 font-semibold'
                                : unread[ch.id] ? 'text-gray-900 font-semibold hover:bg-gray-100'
                                               : 'text-gray-500 hover:bg-gray-100',
                            'w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <span class="flex items-center gap-1.5 truncate">
                            <span class="font-mono text-gray-400">#</span>{{ ch.label }}
                        </span>
                        <span v-if="unread[ch.id]"
                            class="shrink-0 bg-red-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center">
                            {{ unread[ch.id] > 9 ? '9+' : unread[ch.id] }}
                        </span>
                    </button>
                </div>

                <!-- Messaggi diretti -->
                <div class="px-3 pt-5 pb-1">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-2 mb-1">Messaggi diretti</p>
                </div>
                <div class="flex-1 overflow-y-auto px-2 space-y-0.5 pb-4">
                    <button
                        v-for="col in colleagues" :key="col.id"
                        @click="selectChannel('direct', col.id)"
                        :class="[
                            activeType === 'direct' && activeId === dmId(col.id)
                                ? 'bg-purple-100 text-purple-800'
                                : 'hover:bg-gray-100',
                            'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <img :src="col.profile_photo_url" class="w-7 h-7 rounded-full object-cover shrink-0" />
                        <span :class="['flex-1 truncate text-left text-sm',
                            unread[dmId(col.id)] ? 'font-bold text-gray-900' : 'font-medium text-gray-700']">
                            {{ col.name }}
                        </span>
                        <span v-if="unread[dmId(col.id)]"
                            class="shrink-0 bg-red-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center">
                            {{ unread[dmId(col.id)] > 9 ? '9+' : unread[dmId(col.id)] }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ── Chat area ──────────────────────────────────────────────── -->
            <div class="flex-1 flex flex-col min-w-0">

                <!-- Header -->
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3 min-h-[57px]">
                    <template v-if="activeType === 'direct' && activeColleague">
                        <img :src="activeColleague.profile_photo_url" class="w-8 h-8 rounded-full object-cover" />
                        <div>
                            <p class="text-sm font-semibold text-gray-800 leading-tight">{{ activeColleague.name }}</p>
                            <p class="text-xs text-gray-400">Messaggio diretto</p>
                        </div>
                    </template>
                    <template v-else-if="activeChannel">
                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                            <span class="text-purple-600 font-bold">#</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 leading-tight">{{ activeChannel.label }}</p>
                            <p class="text-xs text-gray-400">Canale team</p>
                        </div>
                    </template>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="loadError" class="text-center text-sm text-red-500 py-4 bg-red-50 rounded-xl px-4">{{ loadError }}</div>
                    <div v-if="loading" class="text-center text-sm text-gray-400 py-10">Caricamento…</div>
                    <div v-else-if="messages.length === 0 && !loadError"
                        class="flex flex-col items-center justify-center h-full gap-3 text-gray-400">
                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-sm">Nessun messaggio. Inizia la conversazione!</p>
                    </div>
                    <template v-else>
                        <div v-for="msg in messages" :key="msg.id"
                            class="flex items-end gap-2.5"
                            :class="msg.sender_id === currentUser.id ? 'flex-row-reverse' : ''">
                            <img :src="msg.sender_photo" class="w-7 h-7 rounded-full object-cover shrink-0" />
                            <div class="flex flex-col max-w-[70%]"
                                :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'">
                                <div class="flex items-baseline gap-1.5 mb-1"
                                    :class="msg.sender_id === currentUser.id ? 'flex-row-reverse' : ''">
                                    <span class="text-xs font-medium text-gray-600">{{ msg.sender_name }}</span>
                                    <span class="text-[11px] text-gray-400">{{ formatTime(msg.created_at) }}</span>
                                </div>
                                <div class="px-3.5 py-2.5 text-sm leading-relaxed"
                                    :class="msg.sender_id === currentUser.id
                                        ? 'bg-purple-600 text-white rounded-2xl rounded-br-sm'
                                        : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm'">
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
                            :placeholder="activeType === 'direct' && activeColleague
                                ? `Scrivi a ${activeColleague.name}…`
                                : `Scrivi in #${activeChannel?.label ?? activeId}…`"
                            class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none"
                            @keydown.enter.exact.prevent="send"
                        />
                        <button @click="send" :disabled="!body.trim()"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white p-2.5 rounded-xl transition-colors shrink-0">
                            <svg class="w-4 h-4 rotate-90" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">↵ Invio per inviare · Shift+↵ per andare a capo</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
