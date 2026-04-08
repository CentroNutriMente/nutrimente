<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({ title: String });

const page = usePage();
const sidebarOpen = ref(true);

const navItems = [
    { name: 'Dashboard', href: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Pazienti', href: 'patients.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Calendario', href: 'calendar', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Intervisioni', href: 'intervisioni.index', icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z' },
    { name: 'Fatturazione', href: 'invoices.index', icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z' },
    { name: 'Messaggi', href: 'messages.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { name: 'Documenti', href: 'documents.index', icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { name: 'Workspace', href: 'workspace.index', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
];

const isActive = (routeName) => route().current(routeName.replace('.index', '.*'));

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <Head :title="title" />
        <Banner />

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-16'"
            class="flex flex-col bg-purple-900 text-white min-h-screen transition-all duration-200 shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-4 py-5 border-b border-purple-700">
                <div class="w-8 h-8 bg-purple-400 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-purple-900 font-bold text-sm">CN</span>
                </div>
                <span v-if="sidebarOpen" class="font-semibold text-sm leading-tight">Centro<br>Nutrimento</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 py-4 space-y-1 px-2">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="route(item.href)"
                    :class="[
                        isActive(item.href)
                            ? 'bg-purple-700 text-white'
                            : 'text-purple-100 hover:bg-purple-800',
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors'
                    ]"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span v-if="sidebarOpen">{{ item.name }}</span>
                </Link>
            </nav>

            <!-- User -->
            <div class="border-t border-purple-700 p-3">
                <Dropdown align="top-end" width="48">
                    <template #trigger>
                        <button class="flex items-center gap-3 w-full px-2 py-2 rounded-lg hover:bg-purple-800 transition-colors">
                            <img
                                class="w-8 h-8 rounded-full object-cover shrink-0"
                                :src="$page.props.auth.user.profile_photo_url"
                                :alt="$page.props.auth.user.name"
                            />
                            <div v-if="sidebarOpen" class="text-left min-w-0">
                                <div class="text-sm font-medium truncate">{{ $page.props.auth.user.name }}</div>
                                <div class="text-xs text-purple-300 truncate">{{ $page.props.auth.user.email }}</div>
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

            <!-- Toggle -->
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="absolute top-4 -right-3 bg-purple-700 hover:bg-purple-600 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-lg transition-colors"
                style="position: absolute"
            >
                <svg class="w-3 h-3 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header v-if="$slots.header" class="bg-white border-b border-gray-200 px-6 py-4">
                <slot name="header" />
            </header>

            <!-- Page content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
