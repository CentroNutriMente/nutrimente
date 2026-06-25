<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import BotanicalSprig from '@/Components/ui/BotanicalSprig.vue';
import BotanicalSprite from '@/Components/ui/BotanicalSprite.vue';
import axios from 'axios';

const page = usePage();

defineProps({ title: String });

// Su mobile parte chiuso, su desktop aperto
const sidebarOpen = ref(false);
const moreOpen = ref(false);

onMounted(() => {
    sidebarOpen.value = window.innerWidth >= 768;
});

// Nav primaria (mockup). href = nome route Laravel.
const navItems = [
    { name: 'Dashboard',         href: 'dashboard',           match: 'dashboard',                 icon: 'M3 10.5 12 4l9 6.5M5 9.5V19a1 1 0 0 0 1 1h3v-5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5h3a1 1 0 0 0 1-1V9.5' },
    { name: 'Pazienti',          href: 'patients.index',      match: 'patients.*',                icon: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0' },
    { name: 'Gruppi',            href: 'groups.index',        match: 'groups.*',                  icon: 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a6 6 0 0 1 12 0M15 14a6 6 0 0 1 6 6' },
    { name: 'Agenda',            href: 'calendar',            match: 'calendar',                  icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z' },
    { name: 'Cartelle cliniche', href: 'reports.index',       match: 'reports.*',                 icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z' },
    { name: 'Fatture',           href: 'invoices.index',      match: 'invoices.*',                icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z' },
    { name: 'Moduli e documenti',href: 'documents.index',     match: 'documents.*',               icon: 'M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2Z' },
    { name: 'Risorse',           href: 'workspace.index',     match: 'workspace.*',               icon: 'M4 6h16M4 12h16M4 18h7' },
    { name: 'Impostazioni',      href: 'profile.show',        match: 'profile.*',                 icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' },
];

// Voci secondarie (preservano l'accesso a tutte le sezioni esistenti)
const moreItems = [
    { name: 'Richieste',            href: 'contact-requests.inbox',      match: 'contact-requests.*' },
    { name: 'Intervisioni',         href: 'intervisioni.index',          match: 'intervisioni.*' },
    { name: 'Gestione Referti',     href: 'report-templates.index',      match: 'report-templates.*' },
    { name: 'Gestione Questionari', href: 'questionnaire-templates.index', match: 'questionnaire-templates.*' },
    { name: 'Chat Team',            href: 'messages.index',              match: 'messages.*' },
    { name: 'Social',               href: 'social.index',                match: 'social.*' },
    { name: 'Professionisti',       href: 'professionals.index',         match: 'professionals.*' },
    { name: 'GDPR & Privacy',       href: 'gdpr.index',                  match: 'gdpr.*' },
];

const has = (name) => {
    try { route(name); return true; } catch { return false; }
};

const isActive = (match) => {
    try { return route().current(match); } catch { return false; }
};

const logout = () => router.post(route('logout'));
const closeSidebar = () => { if (window.innerWidth < 768) sidebarOpen.value = false; };

// ── Notifications (logica invariata) ─────────────────────────────────────────
const notifications = ref([]);
const unreadCount    = ref(page.props.notif_unread ?? 0);
const bellOpen       = ref(false);
let   pollInterval   = null;

async function fetchNotifications() {
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.notifications ?? [];
        unreadCount.value   = data.unread_count  ?? 0;
    } catch (e) { console.error('[notifications] fetch error', e); }
}

async function markAllRead() {
    try {
        await axios.post('/notifications/read-all');
        notifications.value = notifications.value.map(n => ({ ...n, read: true }));
        unreadCount.value = 0;
    } catch (e) { console.error('[notifications] markAllRead error', e); }
}

function toggleBell() {
    bellOpen.value = !bellOpen.value;
    if (bellOpen.value && unreadCount.value > 0) markAllRead();
}

function closeBell(e) { if (!e.target.closest('[data-bell]')) bellOpen.value = false; }

function notificationTarget(n) {
    if (n.type === 'message' && n.data?.channel_type && n.data?.channel_id) {
        return route('messages.index', { channel_type: n.data.channel_type, channel_id: n.data.channel_id });
    }
    if (n.type === 'appointment_cancelled' && n.data?.patient_id) {
        return route('patients.show', n.data.patient_id);
    }
    if (['contact_request_new', 'contact_request_assigned', 'contact_request_rejected'].includes(n.type)) {
        return route('contact-requests.inbox');
    }
    if (n.type === 'group_enrollment_new') {
        return n.data?.group_id ? route('groups.show', n.data.group_id) : route('groups.index');
    }
    return null;
}

function openNotification(n) {
    const target = notificationTarget(n);
    if (!target) return;
    bellOpen.value = false;
    router.visit(target);
}

const notifIcon = {
    message:               'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z',
    task_assigned:         'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4',
    task_due:              'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    intervisione:          'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    appointment_cancelled: 'M6 18 18 6M6 6l12 12',
    contact_request_new:       'M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z',
    contact_request_assigned:  'M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z',
    contact_request_rejected:  'M6 18 18 6M6 6l12 12',
    group_enrollment_new:      'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a6 6 0 0 1 12 0M15 14a6 6 0 0 1 6 6',
};

const notifColor = {
    message:               'text-blue-500 bg-blue-50',
    task_assigned:         'text-sage bg-sageLight',
    task_due:              'text-amber-500 bg-amber-50',
    intervisione:          'text-sage bg-sageLight',
    appointment_cancelled: 'text-red-500 bg-red-50',
    contact_request_new:       'text-lavender bg-lavenderLight',
    contact_request_assigned:  'text-blue-500 bg-blue-50',
    contact_request_rejected:  'text-amber-500 bg-amber-50',
    group_enrollment_new:      'text-sage bg-sageLight',
};

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30000);
    document.addEventListener('click', closeBell);
    router.on('finish', () => { unreadCount.value = page.props.notif_unread ?? unreadCount.value; });
});

onUnmounted(() => {
    clearInterval(pollInterval);
    document.removeEventListener('click', closeBell);
});
</script>

<template>
    <div class="min-h-screen bg-cream flex">
        <Head :title="title" />
        <BotanicalSprite />
        <Banner />

        <!-- Overlay mobile -->
        <div v-if="sidebarOpen" class="fixed inset-0 bg-black/30 z-30 md:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed top-0 left-0 md:sticky md:top-0 z-40 w-64 h-screen flex flex-col bg-cream border-r border-line transition-transform duration-200 shrink-0"
        >
            <!-- Logo / wordmark -->
            <div class="px-6 pt-7 pb-5">
                <Link :href="route('dashboard')" class="flex flex-col items-center text-center gap-1">
                    <BotanicalSprig id="bot-sprout" klass="w-7 h-9 text-sage" />
                    <div class="font-serif text-2xl leading-none text-ink lowercase tracking-wide">nutrimente</div>
                    <div class="text-[9px] text-inkSoft uppercase tracking-[0.18em] leading-tight mt-0.5">
                        Studio di<br>psicologia
                    </div>
                </Link>
            </div>

            <!-- Nav primaria -->
            <nav class="flex-1 overflow-y-auto px-3 space-y-1">
                <Link
                    v-for="item in navItems.filter(i => has(i.href))"
                    :key="item.href"
                    :href="route(item.href)"
                    @click="closeSidebar"
                    :class="[
                        isActive(item.match)
                            ? 'bg-sageLight text-sageDeep font-semibold'
                            : 'text-inkSoft hover:bg-cream hover:text-ink',
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-ctrl text-sm transition-colors'
                    ]"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span class="whitespace-nowrap">{{ item.name }}</span>
                </Link>

                <!-- Altro -->
                <button
                    @click="moreOpen = !moreOpen"
                    class="flex items-center gap-3 w-full px-3.5 py-2.5 rounded-ctrl text-sm text-inkSoft hover:bg-cream hover:text-ink transition-colors"
                >
                    <svg class="w-[18px] h-[18px] shrink-0 transition-transform" :class="moreOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>Altro</span>
                </button>
                <div v-if="moreOpen" class="pl-3 space-y-0.5 pb-2">
                    <Link
                        v-for="item in moreItems.filter(i => has(i.href))"
                        :key="item.href"
                        :href="route(item.href)"
                        @click="closeSidebar"
                        :class="[
                            isActive(item.match) ? 'text-sage font-medium' : 'text-inkSoft hover:text-ink',
                            'block px-3.5 py-2 rounded-xl text-[13px] transition-colors hover:bg-cream'
                        ]"
                    >{{ item.name }}</Link>
                </div>
            </nav>

            <!-- User -->
            <div class="border-t border-line p-3">
                <div class="flex items-center gap-2.5">
                    <Link :href="route('profile.show')" @click="closeSidebar" class="flex items-center gap-2.5 flex-1 min-w-0 px-1.5 py-1.5 rounded-ctrl hover:bg-cream transition-colors">
                        <img class="w-9 h-9 rounded-full object-cover shrink-0 ring-2 ring-white" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />
                        <div class="text-left min-w-0">
                            <div class="text-sm font-semibold text-ink truncate">{{ $page.props.auth.user.name }}</div>
                            <div class="text-[11px] text-inkSoft truncate">Nutrimente</div>
                        </div>
                    </Link>
                    <button @click="logout" title="Esci" class="p-2 rounded-xl text-inkSoft hover:bg-red-50 hover:text-red-500 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar: hamburger (mobile) + slot header + bell -->
            <header class="px-4 md:px-8 pt-4 md:pt-6 flex items-start gap-3">
                <button class="md:hidden p-2 -ml-1 rounded-xl text-inkSoft hover:bg-cream transition-colors shrink-0" @click="sidebarOpen = !sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1 min-w-0">
                    <slot name="header" />
                </div>

                <!-- Bell -->
                <div class="relative shrink-0" data-bell>
                    <button @click.stop="toggleBell" class="relative p-2.5 rounded-ctrl text-inkSoft bg-white border border-line hover:text-sage transition-colors" title="Notifiche">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                        <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-blushDeep text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                    </button>

                    <div v-if="bellOpen" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-3xl border border-line shadow-xl z-50 overflow-hidden" data-bell>
                        <div class="flex items-center justify-between px-4 py-3 border-b border-line">
                            <span class="text-sm font-semibold text-ink">Notifiche</span>
                            <button v-if="notifications.some(n => !n.read)" @click="markAllRead" class="text-xs text-sage hover:underline">Segna tutte lette</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-line">
                            <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-inkSoft">Nessuna notifica</div>
                            <div v-for="n in notifications" :key="n.id" @click="openNotification(n)"
                                :class="['flex items-start gap-3 px-4 py-3 transition-colors', n.read ? 'bg-white' : 'bg-lavenderLight', notificationTarget(n) ? 'cursor-pointer hover:bg-cream' : '']">
                                <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5', notifColor[n.type] ?? 'text-inkSoft bg-cream']">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="notifIcon[n.type] ?? notifIcon.task_assigned" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-ink leading-snug">{{ n.title }}</p>
                                    <p v-if="n.body" class="text-xs text-inkSoft mt-0.5 line-clamp-2">{{ n.body }}</p>
                                    <p class="text-[11px] text-inkSoft mt-1">{{ n.created_at }}</p>
                                </div>
                                <div v-if="!n.read" class="w-2 h-2 bg-lavender rounded-full shrink-0 mt-1.5" />
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:px-8 md:py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
