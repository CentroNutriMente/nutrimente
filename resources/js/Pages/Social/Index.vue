<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ posts: Array });

// ── Category config ────────────────────────────────────────────────────────────
const CATEGORIES = [
    { value: 'post',       label: 'Post',        bg: 'bg-blue-500',    dot: 'bg-blue-500',    light: 'bg-blue-50 text-blue-700' },
    { value: 'video_reel', label: 'Video / Reel', bg: 'bg-purple-500',  dot: 'bg-purple-500',  light: 'bg-purple-50 text-purple-700' },
    { value: 'storia',     label: 'Storia',       bg: 'bg-pink-500',    dot: 'bg-pink-500',    light: 'bg-pink-50 text-pink-700' },
    { value: 'carousel',   label: 'Carosello',    bg: 'bg-amber-400',   dot: 'bg-amber-400',   light: 'bg-amber-50 text-amber-700' },
    { value: 'video_live', label: 'Video Live',   bg: 'bg-red-500',     dot: 'bg-red-500',     light: 'bg-red-50 text-red-700' },
    { value: 'altro',      label: 'Altro',        bg: 'bg-gray-400',    dot: 'bg-gray-400',    light: 'bg-gray-100 text-gray-700' },
];
const PLATFORMS = ['Instagram', 'Facebook', 'TikTok', 'LinkedIn', 'YouTube', 'Pinterest'];
const STATUSES  = [
    { value: 'draft',     label: 'Bozza' },
    { value: 'scheduled', label: 'Programmato' },
    { value: 'published', label: 'Pubblicato' },
];

function catConf(value) { return CATEGORIES.find(c => c.value === value) ?? CATEGORIES.at(-1); }

// ── Calendar state ─────────────────────────────────────────────────────────────
const today   = new Date();
const viewYear  = ref(today.getFullYear());
const viewMonth = ref(today.getMonth()); // 0-based

const monthLabel = computed(() =>
    new Date(viewYear.value, viewMonth.value, 1)
        .toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })
        .replace(/^\w/, c => c.toUpperCase())
);

function prevMonth() {
    if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; }
    else viewMonth.value--;
}
function nextMonth() {
    if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; }
    else viewMonth.value++;
}

// Build calendar grid (Mon–Sun weeks)
const calendarDays = computed(() => {
    const y = viewYear.value, m = viewMonth.value;
    const first = new Date(y, m, 1);
    const last  = new Date(y, m + 1, 0);

    // Monday-aligned offset (getDay: 0=Sun → we want 0=Mon)
    let offset = first.getDay() - 1;
    if (offset < 0) offset = 6;

    const days = [];
    for (let i = 0; i < offset; i++)
        days.push({ date: new Date(y, m, -offset + i + 1), inMonth: false });
    for (let d = 1; d <= last.getDate(); d++)
        days.push({ date: new Date(y, m, d), inMonth: true });
    const rem = (7 - (days.length % 7)) % 7;
    for (let d = 1; d <= rem; d++)
        days.push({ date: new Date(y, m + 1, d), inMonth: false });
    return days;
});

const calendarWeeks = computed(() => {
    const w = [];
    for (let i = 0; i < calendarDays.value.length; i += 7)
        w.push(calendarDays.value.slice(i, i + 7));
    return w;
});

function dateKey(date) {
    return date.toLocaleDateString('sv-SE'); // 'YYYY-MM-DD'
}
function isToday(date) { return dateKey(date) === dateKey(today); }

function postsFor(date) {
    const key = dateKey(date);
    return props.posts.filter(p => p.scheduled_at === key);
}

// ── Modal state ────────────────────────────────────────────────────────────────
const modal     = ref(null); // null | 'create' | 'detail' | 'edit'
const selected  = ref(null); // the post being viewed/edited
const presetDate = ref('');  // date prefilled when creating from a day click

function openCreate(date = null) {
    presetDate.value = date ? dateKey(date) : '';
    form.reset();
    form.scheduled_at = presetDate.value;
    form.status = 'scheduled';
    form.category = 'post';
    modal.value = 'create';
}
function openDetail(post) {
    selected.value = post;
    modal.value = 'detail';
}
function openEdit(post) {
    selected.value = post;
    editForm.title        = post.title;
    editForm.category     = post.category;
    editForm.content      = post.content ?? '';
    editForm.platforms    = [...(post.platforms ?? [])];
    editForm.status       = post.status;
    editForm.scheduled_at = post.scheduled_at ?? '';
    editForm.notes        = post.notes ?? '';
    modal.value = 'edit';
}
function closeModal() { modal.value = null; selected.value = null; }

// ── Create form ────────────────────────────────────────────────────────────────
const form = useForm({
    title: '', category: 'post', content: '', platforms: [],
    status: 'scheduled', scheduled_at: '', notes: '',
});
function togglePlatform(p) {
    const idx = form.platforms.indexOf(p);
    if (idx >= 0) form.platforms.splice(idx, 1);
    else form.platforms.push(p);
}
function submitCreate() {
    form.post('/social', { onSuccess: closeModal });
}

// ── Edit form ──────────────────────────────────────────────────────────────────
const editForm = useForm({
    title: '', category: 'post', content: '', platforms: [],
    status: 'scheduled', scheduled_at: '', notes: '',
});
function toggleEditPlatform(p) {
    const idx = editForm.platforms.indexOf(p);
    if (idx >= 0) editForm.platforms.splice(idx, 1);
    else editForm.platforms.push(p);
}
function submitEdit() {
    editForm.put(`/social/${selected.value.id}`, { onSuccess: closeModal });
}

// ── Delete ─────────────────────────────────────────────────────────────────────
function deletePost(id) {
    if (confirm('Eliminare questo contenuto?'))
        router.delete(`/social/${id}`, { onSuccess: closeModal });
}
</script>

<template>
    <AppLayout title="Social">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Calendario Social</h1>
                <button @click="openCreate()"
                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    + Nuovo contenuto
                </button>
            </div>
        </template>

        <!-- Legend -->
        <div class="flex flex-wrap gap-2 mb-4">
            <span v-for="cat in CATEGORIES" :key="cat.value"
                class="flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                :class="cat.light">
                <span class="w-2 h-2 rounded-full" :class="cat.dot" />
                {{ cat.label }}
            </span>
        </div>

        <!-- Calendar card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Month navigation -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <button @click="prevMonth"
                    class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-base font-semibold text-gray-800 capitalize">{{ monthLabel }}</h2>
                <button @click="nextMonth"
                    class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 border-b border-gray-100">
                <div v-for="d in ['Lun','Mar','Mer','Gio','Ven','Sab','Dom']" :key="d"
                    class="py-2 text-center text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    {{ d }}
                </div>
            </div>

            <!-- Weeks grid -->
            <div>
                <div v-for="(week, wi) in calendarWeeks" :key="wi"
                    class="grid grid-cols-7"
                    :class="wi < calendarWeeks.length - 1 ? 'border-b border-gray-100' : ''">

                    <div v-for="day in week" :key="dateKey(day.date)"
                        @click="day.inMonth && openCreate(day.date)"
                        class="min-h-[90px] p-1.5 border-r border-gray-100 last:border-r-0 cursor-pointer transition-colors"
                        :class="[
                            day.inMonth ? 'hover:bg-gray-50' : 'bg-gray-50/40',
                        ]">

                        <!-- Day number -->
                        <div class="flex justify-end mb-1">
                            <span class="text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full"
                                :class="isToday(day.date) && day.inMonth
                                    ? 'bg-purple-600 text-white font-bold'
                                    : day.inMonth ? 'text-gray-700' : 'text-gray-300'">
                                {{ day.date.getDate() }}
                            </span>
                        </div>

                        <!-- Events chips -->
                        <div class="space-y-0.5">
                            <button
                                v-for="post in postsFor(day.date)" :key="post.id"
                                @click.stop="openDetail(post)"
                                class="w-full text-left text-white text-[10px] font-medium px-1.5 py-0.5 rounded-md truncate leading-tight transition-opacity hover:opacity-90"
                                :class="catConf(post.category).bg"
                                :title="post.title">
                                {{ post.title }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── MODALS ──────────────────────────────────────────────────────────── -->
        <Teleport to="body">
        <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="closeModal">

            <!-- ── DETAIL modal ──────────────────────────────────────────────── -->
            <div v-if="modal === 'detail'" class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Header -->
                <div class="px-6 pt-5 pb-4 border-b border-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="catConf(selected.category).light">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="catConf(selected.category).dot" />
                                    {{ catConf(selected.category).label }}
                                </span>
                                <span class="text-xs text-gray-400">{{ selected.status === 'published' ? '✓ Pubblicato' : selected.status === 'scheduled' ? '⏱ Programmato' : '✎ Bozza' }}</span>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 leading-snug">{{ selected.title }}</h3>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-4 space-y-3">
                    <div v-if="selected.scheduled_at" class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ new Date(selected.scheduled_at).toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </div>
                    <div v-if="selected.platforms?.length" class="flex flex-wrap gap-1.5">
                        <span v-for="p in selected.platforms" :key="p"
                            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ p }}</span>
                    </div>
                    <div v-if="selected.content" class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap border-l-2 border-gray-100 pl-3">
                        {{ selected.content }}
                    </div>
                    <div v-if="selected.notes" class="text-sm text-gray-500 italic leading-relaxed">
                        <span class="font-medium not-italic text-gray-600">Note: </span>{{ selected.notes }}
                    </div>
                    <div class="text-xs text-gray-400">Creato da {{ selected.created_by }}</div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 px-6 pb-5">
                    <button @click="openEdit(selected)"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-2 rounded-xl transition-colors">
                        Modifica
                    </button>
                    <button @click="deletePost(selected.id)"
                        class="px-4 py-2 text-sm text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                        Elimina
                    </button>
                </div>
            </div>

            <!-- ── CREATE / EDIT modal ────────────────────────────────────────── -->
            <div v-else class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ modal === 'create' ? 'Nuovo contenuto social' : 'Modifica contenuto' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form body -->
                <div class="overflow-y-auto px-6 py-4 space-y-4 flex-1">
                    <template v-if="modal === 'create'">

                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Titolo *</label>
                            <input v-model="form.title" type="text" placeholder="Es. Post inspirazionale lunedì"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>

                        <!-- Category + Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Categoria *</label>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button v-for="cat in CATEGORIES" :key="cat.value"
                                        @click="form.category = cat.value"
                                        class="flex items-center gap-1.5 text-xs px-2 py-1.5 rounded-lg border transition-all"
                                        :class="form.category === cat.value
                                            ? cat.light + ' border-transparent font-semibold'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="cat.dot" />
                                        {{ cat.label }}
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Data</label>
                                    <input v-model="form.scheduled_at" type="date"
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Stato</label>
                                    <select v-model="form.status"
                                        class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                        <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Platforms -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">Piattaforme</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="p in PLATFORMS" :key="p"
                                    @click="togglePlatform(p)"
                                    class="text-xs px-3 py-1.5 rounded-lg border transition-all"
                                    :class="form.platforms.includes(p)
                                        ? 'bg-purple-600 text-white border-purple-600 font-medium'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                    {{ p }}
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Testo / Descrizione</label>
                            <textarea v-model="form.content" rows="3" placeholder="Testo del post o brief creativo…"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Note interne</label>
                            <textarea v-model="form.notes" rows="2" placeholder="Riferimenti, hashtag suggeriti, note di produzione…"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>
                    </template>

                    <!-- EDIT: same fields but bound to editForm -->
                    <template v-else>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Titolo *</label>
                            <input v-model="editForm.title" type="text"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Categoria *</label>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button v-for="cat in CATEGORIES" :key="cat.value"
                                        @click="editForm.category = cat.value"
                                        class="flex items-center gap-1.5 text-xs px-2 py-1.5 rounded-lg border transition-all"
                                        :class="editForm.category === cat.value
                                            ? cat.light + ' border-transparent font-semibold'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="cat.dot" />
                                        {{ cat.label }}
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Data</label>
                                    <input v-model="editForm.scheduled_at" type="date"
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Stato</label>
                                    <select v-model="editForm.status"
                                        class="w-full rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                        <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">Piattaforme</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="p in PLATFORMS" :key="p"
                                    @click="toggleEditPlatform(p)"
                                    class="text-xs px-3 py-1.5 rounded-lg border transition-all"
                                    :class="editForm.platforms.includes(p)
                                        ? 'bg-purple-600 text-white border-purple-600 font-medium'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                    {{ p }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Testo / Descrizione</label>
                            <textarea v-model="editForm.content" rows="3"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Note interne</label>
                            <textarea v-model="editForm.notes" rows="2"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 shrink-0">
                    <button @click="closeModal" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        Annulla
                    </button>
                    <button
                        v-if="modal === 'create'"
                        @click="submitCreate"
                        :disabled="form.processing || !form.title.trim()"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-6 py-2 rounded-xl transition-colors">
                        {{ form.processing ? 'Salvataggio…' : 'Aggiungi' }}
                    </button>
                    <button
                        v-else
                        @click="submitEdit"
                        :disabled="editForm.processing || !editForm.title.trim()"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-6 py-2 rounded-xl transition-colors">
                        {{ editForm.processing ? 'Salvataggio…' : 'Salva modifiche' }}
                    </button>
                </div>
            </div>

        </div>
        </Teleport>
    </AppLayout>
</template>
