<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
import Banner from '@/Components/Banner.vue';
import axios from 'axios';

defineProps({ title: String });

// Su mobile parte chiuso, su desktop aperto
const sidebarOpen = ref(false);

onMounted(() => {
    sidebarOpen.value = window.innerWidth >= 768;
});

const navItems = [
    { name: 'Dashboard',      href: 'dashboard',          icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z' },
    { name: 'Calendario',     href: 'calendar',            icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Pazienti',       href: 'patients.index',      icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { name: 'Referti',        href: 'reports.index',       icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Fatturazione',   href: 'invoices.index',      icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Intervisioni',   href: 'intervisioni.index',  icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z' },
    { name: 'Documenti',      href: 'documents.index',     icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
    { name: 'Workspace',      href: 'workspace.index',     icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { name: 'GDPR & Privacy', href: 'gdpr.index',          icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { name: 'Professionisti', href: 'professionals.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { name: 'Chat Team',      href: 'messages.index',      icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
];

const isActive = (routeName) => {
    try {
        if (routeName === 'reports.index') {
            return route().current('reports.*') || route().current('report-templates.*');
        }
        return route().current(routeName.replace('.index', '.*'));
    } catch { return false; }
};

const logout = () => router.post(route('logout'));
const closeSidebar = () => { if (window.innerWidth < 768) sidebarOpen.value = false; };

// ── Notifications ────────────────────────────────────────────────────────────
const notifications   = ref([]);
const unreadCount     = ref(page.props.notif_unread ?? 0); // seeded from server on every page load
const bellOpen        = ref(false);
let   pollInterval    = null;

async function fetchNotifications() {
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.notifications ?? [];
        unreadCount.value   = data.unread_count  ?? 0;
    } catch (e) {
        console.error('[notifications] fetch error', e);
    }
}

async function markAllRead() {
    try {
        await axios.post('/notifications/read-all');
        notifications.value = notifications.value.map(n => ({ ...n, read: true }));
        unreadCount.value   = 0;
    } catch (e) {
        console.error('[notifications] markAllRead error', e);
    }
}

function toggleBell() {
    bellOpen.value = !bellOpen.value;
    if (bellOpen.value && unreadCount.value > 0) markAllRead();
}

function closeBell(e) {
    if (!e.target.closest('[data-bell]')) bellOpen.value = false;
}

const notifIcon = {
    message:      'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    task_assigned:'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    task_due:     'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    intervisione: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
};

const notifColor = {
    message:      'text-blue-500 bg-blue-50',
    task_assigned:'text-purple-500 bg-purple-50',
    task_due:     'text-amber-500 bg-amber-50',
    intervisione: 'text-green-500 bg-green-50',
};

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30000);
    document.addEventListener('click', closeBell);
    // Keep badge in sync whenever Inertia navigates to a new page
    router.on('finish', () => {
        unreadCount.value = page.props.notif_unread ?? unreadCount.value;
    });
});

onUnmounted(() => {
    clearInterval(pollInterval);
    document.removeEventListener('click', closeBell);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <Head :title="title" />
        <Banner />

        <!-- Overlay mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/40 z-30 md:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0 w-56' : '-translate-x-full w-56 md:translate-x-0 md:w-16',
            ]"
            class="fixed md:relative z-40 flex flex-col bg-white border-r border-gray-200 h-full min-h-screen transition-all duration-200 shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-100">
                <div class="w-9 h-9 bg-purple-600 rounded-xl flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
                    </svg>
                </div>
                <div :class="sidebarOpen ? 'opacity-100' : 'opacity-0 md:opacity-0'" class="min-w-0 transition-opacity">
                    <div class="font-bold text-gray-900 text-sm leading-tight whitespace-nowrap">Centro NutriMente</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide">Studio Associato</div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="route(item.href)"
                    @click="closeSidebar"
                    :class="[
                        isActive(item.href)
                            ? 'bg-purple-50 text-purple-700 font-semibold'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800',
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors'
                    ]"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 md:hidden'" class="whitespace-nowrap transition-opacity">{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Riduci / Espandi (solo desktop) -->
            <div class="hidden md:block border-t border-gray-100 p-2">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-600 text-sm transition-colors"
                >
                    <svg class="w-4 h-4 shrink-0 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span v-if="sidebarOpen" class="text-xs">Riduci</span>
                </button>
            </div>

            <!-- User + logout -->
            <div class="border-t border-gray-100 p-3 space-y-1">
                <Link :href="route('profile.show')" @click="closeSidebar"
                    class="flex items-center gap-2 w-full px-2 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <img
                        class="w-7 h-7 rounded-full object-cover shrink-0"
                        :src="$page.props.auth.user.profile_photo_url"
                        :alt="$page.props.auth.user.name"
                    />
                    <div :class="sidebarOpen ? 'opacity-100' : 'opacity-0 md:hidden'" class="text-left min-w-0 transition-opacity">
                        <div class="text-xs font-medium text-gray-700 truncate">{{ $page.props.auth.user.name }}</div>
                    </div>
                </Link>
                <button @click="logout"
                    class="flex items-center gap-2 w-full px-2 py-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 md:hidden'" class="text-xs transition-opacity">Esci</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar (header + hamburger) -->
            <header class="bg-white border-b border-gray-200 px-4 md:px-8 py-3 md:py-4 flex items-center gap-3 sticky top-0 z-20">
                <!-- Hamburger mobile -->
                <button
                    class="md:hidden p-2 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors shrink-0"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1 min-w-0">
                    <slot name="header" />
                </div>

                <!-- Bell -->
                <div class="relative shrink-0" data-bell>
                    <button
                        @click.stop="toggleBell"
                        class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                        title="Notifiche"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
                        >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                    </button>

                    <!-- Dropdown -->
                    <div
                        v-if="bellOpen"
                        class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl border border-gray-200 shadow-xl z-50 overflow-hidden"
                        data-bell
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-800">Notifiche</span>
                            <button
                                v-if="notifications.some(n => !n.read)"
                                @click="markAllRead"
                                class="text-xs text-purple-600 hover:underline"
                            >Segna tutte lette</button>
                        </div>

                        <!-- List -->
                        <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                            <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                                Nessuna notifica
                            </div>
                            <div
                                v-for="n in notifications"
                                :key="n.id"
                                :class="['flex items-start gap-3 px-4 py-3 transition-colors', n.read ? 'bg-white' : 'bg-purple-50/50']"
                            >
                                <!-- Icon -->
                                <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5', notifColor[n.type] ?? 'text-gray-400 bg-gray-100']">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="notifIcon[n.type] ?? notifIcon.task_assigned" />
                                    </svg>
                                </div>
                                <!-- Text -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 leading-snug">{{ n.title }}</p>
                                    <p v-if="n.body" class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.body }}</p>
                                    <p class="text-[11px] text-gray-400 mt-1">{{ n.created_at }}</p>
                                </div>
                                <!-- Unread dot -->
                                <div v-if="!n.read" class="w-2 h-2 bg-purple-500 rounded-full shrink-0 mt-1.5" />
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8 bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>
