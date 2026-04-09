<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({ title: String });

const sidebarOpen = ref(true);

const navItems = [
    { name: 'Dashboard',      href: 'dashboard',          icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z' },
    { name: 'Calendario',     href: 'calendar',            icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Pazienti',       href: 'patients.index',      icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { name: 'Fatturazione',   href: 'invoices.index',      icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Intervisioni',   href: 'intervisioni.index',  icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z' },
    { name: 'Documenti',      href: 'documents.index',     icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
    { name: 'Workspace',      href: 'workspace.index',     icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { name: 'GDPR & Privacy', href: 'gdpr.index',          icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { name: 'Professionisti', href: 'professionals.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { name: 'Chat Team',      href: 'messages.index',      icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
];

const isActive = (routeName) => {
    try { return route().current(routeName.replace('.index', '.*')); } catch { return false; }
};

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <Head :title="title" />
        <Banner />

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'w-56' : 'w-16'"
            class="relative flex flex-col bg-white border-r border-gray-200 min-h-screen transition-all duration-200 shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-100">
                <div class="w-9 h-9 bg-purple-600 rounded-xl flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
                    </svg>
                </div>
                <div v-if="sidebarOpen" class="min-w-0">
                    <div class="font-bold text-gray-900 text-sm leading-tight">Centro NutriMente</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide">Studio Associato</div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="route(item.href)"
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
                    <span v-if="sidebarOpen">{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Riduci / Espandi -->
            <div class="border-t border-gray-100 p-2">
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

            <!-- User avatar -->
            <div class="border-t border-gray-100 p-3">
                <Dropdown align="top-end" width="48">
                    <template #trigger>
                        <button class="flex items-center gap-2 w-full px-2 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <img
                                class="w-7 h-7 rounded-full object-cover shrink-0"
                                :src="$page.props.auth.user.profile_photo_url"
                                :alt="$page.props.auth.user.name"
                            />
                            <div v-if="sidebarOpen" class="text-left min-w-0">
                                <div class="text-xs font-medium text-gray-700 truncate">{{ $page.props.auth.user.name }}</div>
                            </div>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.show')">Profilo</DropdownLink>
                        <div class="border-t border-gray-100" />
                        <form @submit.prevent="logout">
                            <DropdownLink as="button">Esci</DropdownLink>
                        </form>
                    </template>
                </Dropdown>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header v-if="$slots.header" class="bg-white border-b border-gray-200 px-8 py-4">
                <slot name="header" />
            </header>
            <main class="flex-1 p-8 bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>
