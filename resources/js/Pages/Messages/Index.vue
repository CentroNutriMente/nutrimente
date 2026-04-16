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

const messages = ref([]);
const loading  = ref(false);
const body     = ref('');
const messagesEnd = ref(null);
let pollInterval  = null;

// Local unread tracking (starts from server, updated client-side)
const unread = ref({ ...props.unreadByChannel });

// Compute directChannelId deterministically
function dmId(otherId) {
    const a = Math.min(props.currentUser.id, Number(otherId));
    const b = Math.max(props.currentUser.id, Number(otherId));
    return `dm_${a}_${b}`;
}

const activeLabel = computed(() => {
    if (activeType.value === 'team') {
        return '#' + (props.teamChannels.find(c => c.id === activeId.value)?.label ?? activeId.value);
    }
    const col = props.colleagues.find(c => dmId(c.id) === activeId.value);
    return col?.name ?? activeId.value;
});

// ── Select channel ────────────────────────────────────────────────────────────
async function selectChannel(type, id) {
    const channelId = type === 'direct' ? dmId(id) : String(id);
    activeType.value = type;
    activeId.value   = channelId;

    // Mark notifications for this channel as read
    if (unread.value[channelId]) {
        delete unread.value[channelId];
        axios.post(route('messages.read-channel'), { channel_id: channelId }).catch(() => {});
    }

    await loadMessages();
    startPolling();
}

// ── Load messages ─────────────────────────────────────────────────────────────
async function loadMessages(silent = false) {
    if (!silent) loading.value = true;
    try {
        const { data } = await axios.get(route('messages.load'), {
            params: { channel_type: activeType.value, channel_id: activeId.value },
        });
        const wasAtBottom = messagesEnd.value
            ? messagesEnd.value.getBoundingClientRect().bottom <= window.innerHeight + 100
            : true;
        messages.value = data;
        if (wasAtBottom) {
            await nextTick();
            messagesEnd.value?.scrollIntoView({ behavior: 'instant' });
        }
    } catch {}
    finally { if (!silent) loading.value = false; }
}

function startPolling() {
    stopPolling();
    pollInterval = setInterval(() => loadMessages(true), 8000);
}

function stopPolling() {
    if (pollInterval) clearInterval(pollInterval);
}

// ── Send ─────────────────────────────────────────────────────────────────────
async function send() {
    if (!body.value.trim()) return;
    const text = body.value.trim();
    body.value = '';
    try {
        const { data } = await axios.post(route('messages.store'), {
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

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatTime(dt) {
    const d     = new Date(dt);
    const today = new Date();
    if (d.toDateString() === today.toDateString()) {
        return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' }) + ' ' +
        d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => { loadMessages(); startPolling(); });
onUnmounted(() => stopPolling());
</script>

<template>
    <AppLayout title="Chat Team">
        <div class="flex h-[calc(100vh-120px)] bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- ── Sidebar ─────────────────────────────────────────────────── -->
            <div class="w-60 shrink-0 border-r border-gray-100 flex flex-col bg-gray-50/50">

                <!-- Canali -->
                <div class="px-3 pt-4 pb-1">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-2 mb-1">Canali</p>
                </div>
                <div class="px-2 space-y-0.5">
                    <button
                        v-for="ch in teamChannels"
                        :key="ch.id"
                        @click="selectChannel('team', ch.id)"
                        :class="[
                            activeType === 'team' && activeId === ch.id
                                ? 'bg-purple-100 text-purple-800 font-semibold'
                                : 'text-gray-600 hover:bg-gray-100',
                            'w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <span class="flex items-center gap-1.5">
                            <span class="text-gray-400 font-mono">#</span>
                            {{ ch.label }}
                        </span>
                        <span
                            v-if="unread[ch.id]"
                            class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none min-w-[18px] text-center"
                        >{{ unread[ch.id] }}</span>
                    </button>
                </div>

                <!-- Messaggi diretti -->
                <div class="px-3 pt-5 pb-1">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-2 mb-1">Messaggi diretti</p>
                </div>
                <div class="flex-1 overflow-y-auto px-2 space-y-0.5 pb-4">
                    <button
                        v-for="col in colleagues"
                        :key="col.id"
                        @click="selectChannel('direct', col.id)"
                        :class="[
                            activeType === 'direct' && activeId === dmId(col.id)
                                ? 'bg-purple-100 text-purple-800'
                                : 'text-gray-700 hover:bg-gray-100',
                            'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm transition-colors'
                        ]"
                    >
                        <!-- Avatar with online-ish dot placeholder -->
                        <div class="relative shrink-0">
                            <img :src="col.profile_photo_url" class="w-7 h-7 rounded-full object-cover" />
                        </div>
                        <!-- Name + unread -->
                        <div class="flex-1 min-w-0 flex items-center justify-between gap-1">
                            <span
                                :class="['truncate text-sm', unread[dmId(col.id)] ? 'font-semibold text-gray-900' : 'font-medium']"
                            >{{ col.name }}</span>
                            <span
                                v-if="unread[dmId(col.id)]"
                                class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none min-w-[18px] text-center shrink-0"
                            >{{ unread[dmId(col.id)] }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ── Chat area ──────────────────────────────────────────────── -->
            <div class="flex-1 flex flex-col min-w-0">

                <!-- Header -->
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-3">
                    <div v-if="activeType === 'direct'" class="flex items-center gap-2">
                        <img
                            :src="colleagues.find(c => dmId(c.id) === activeId)?.profile_photo_url"
                            class="w-7 h-7 rounded-full object-cover"
                        />
                        <div>
                            <p class="text-sm font-semibold text-gray-800 leading-tight">
                                {{ colleagues.find(c => dmId(c.id) === activeId)?.name }}
                            </p>
                            <p class="text-xs text-gray-400">Messaggio diretto</p>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center">
                            <span class="text-purple-600 font-bold text-sm">#</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 leading-tight">
                                {{ teamChannels.find(c => c.id === activeId)?.label }}
                            </p>
                            <p class="text-xs text-gray-400">Canale team</p>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="loading" class="text-center text-gray-400 text-sm py-8">Caricamento…</div>
                    <div v-else-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 gap-3">
                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-sm">Nessun messaggio ancora. Inizia la conversazione!</p>
                    </div>
                    <template v-else>
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            class="flex items-end gap-2.5"
                            :class="msg.sender_id === currentUser.id ? 'flex-row-reverse' : ''"
                        >
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
                                        : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm'"
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
                            :placeholder="activeType === 'direct'
                                ? `Scrivi a ${colleagues.find(c => dmId(c.id) === activeId)?.name ?? ''}…`
                                : `Scrivi in #${teamChannels.find(c => c.id === activeId)?.label ?? activeId}…`"
                            class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none"
                            @keydown.enter.exact.prevent="send"
                            @keydown.shift.enter.exact="body += '\n'"
                        />
                        <button
                            @click="send"
                            :disabled="!body.trim()"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white p-2.5 rounded-xl transition-colors shrink-0"
                        >
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
