<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    professionals: Array,
    tasks: Array,
});

// ── Active tab ───────────────────────────────────────────────────────────────
const activeUserId = ref(props.professionals[0]?.id ?? null);

const currentTasks = computed(() =>
    props.tasks.filter(t => t.user_id === activeUserId.value)
);
const open = computed(() => currentTasks.value.filter(t => !t.completed));
const done = computed(() => currentTasks.value.filter(t =>  t.completed));

// ── New task form ─────────────────────────────────────────────────────────────
const addForm = useForm({ user_id: null, title: '', notes: '', due_date: '', priority: 'medium' });
const showAddForm = ref(false);

function openAdd() {
    addForm.user_id  = activeUserId.value;
    addForm.title    = '';
    addForm.notes    = '';
    addForm.due_date = '';
    addForm.priority = 'medium';
    showAddForm.value = true;
}

function submitAdd() {
    if (!addForm.title.trim()) return;
    addForm.user_id = activeUserId.value;
    addForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => { showAddForm.value = false; },
    });
}

// ── Inline edit ──────────────────────────────────────────────────────────────
const editing = ref(null);
const editForm = useForm({ title: '', notes: '', due_date: '', priority: 'medium' });

function startEdit(task) {
    editing.value     = task.id;
    editForm.title    = task.title;
    editForm.notes    = task.notes ?? '';
    editForm.due_date = task.due_date ?? '';
    editForm.priority = task.priority;
}

function saveEdit(task) {
    editForm.put(route('tasks.update', task.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

function cancelEdit() { editing.value = null; }

// ── Toggle complete ───────────────────────────────────────────────────────────
function toggleComplete(task) {
    router.put(route('tasks.update', task.id), { completed: !task.completed }, { preserveScroll: true });
}

// ── Delete ────────────────────────────────────────────────────────────────────
function deleteTask(task) {
    if (!confirm('Eliminare questa attività?')) return;
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const priorityConfig = {
    high:   { label: 'Alta',  dot: 'bg-red-500',   badge: 'bg-red-50 text-red-600'   },
    medium: { label: 'Media', dot: 'bg-amber-400',  badge: 'bg-amber-50 text-amber-600' },
    low:    { label: 'Bassa', dot: 'bg-blue-400',   badge: 'bg-blue-50 text-blue-600'  },
};

function isOverdue(due) {
    if (!due) return false;
    return new Date(due) < new Date(new Date().toDateString());
}

function fmtDate(d) {
    if (!d) return '';
    return new Date(d + 'T00:00:00').toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AppLayout title="Workspace">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Workspace</h1>
        </template>

        <div class="max-w-3xl space-y-5">

            <!-- Professional tabs -->
            <div v-if="professionals.length" class="flex gap-1.5 flex-wrap">
                <button
                    v-for="p in professionals"
                    :key="p.id"
                    @click="activeUserId = p.id; showAddForm = false; editing = null"
                    :class="[
                        activeUserId === p.id
                            ? 'bg-purple-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 border border-gray-200 hover:border-purple-300 hover:text-purple-700',
                        'px-4 py-2 rounded-xl text-sm font-medium transition-colors'
                    ]"
                >
                    {{ p.name }}
                </button>
            </div>

            <!-- Empty state if no professionals -->
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400 text-sm">
                Nessun professionista trovato.
            </div>

            <!-- Task card -->
            <div v-if="activeUserId" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                <!-- Card header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">
                            {{ professionals.find(p => p.id === activeUserId)?.name }}
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ open.length }} {{ open.length === 1 ? 'attività aperta' : 'attività aperte' }}
                            <span v-if="done.length"> · {{ done.length }} completate</span>
                        </p>
                    </div>
                    <button
                        @click="openAdd"
                        class="flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium px-3 py-2 rounded-lg transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuova attività
                    </button>
                </div>

                <!-- Add form -->
                <div v-if="showAddForm" class="border-b border-purple-100 bg-purple-50/50 px-5 py-4 space-y-3">
                    <input
                        v-model="addForm.title"
                        @keydown.enter.prevent="submitAdd"
                        @keydown.escape="showAddForm = false"
                        autofocus
                        placeholder="Titolo attività…"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300"
                    />
                    <textarea
                        v-model="addForm.notes"
                        rows="2"
                        placeholder="Note (opzionale)…"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none"
                    />
                    <div class="flex gap-3 flex-wrap">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-gray-500">Scadenza</label>
                            <input v-model="addForm.due_date" type="date"
                                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-gray-500">Priorità</label>
                            <select v-model="addForm.priority"
                                class="rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                <option value="high">Alta</option>
                                <option value="medium">Media</option>
                                <option value="low">Bassa</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end pt-1">
                        <button @click="showAddForm = false"
                            class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                            Annulla
                        </button>
                        <button @click="submitAdd"
                            :disabled="!addForm.title.trim() || addForm.processing"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                            Aggiungi
                        </button>
                    </div>
                </div>

                <!-- Empty open tasks -->
                <div v-if="open.length === 0 && !showAddForm" class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <p class="text-sm text-gray-400">Nessuna attività aperta. Tutto fatto!</p>
                </div>

                <!-- Open task list -->
                <ul class="divide-y divide-gray-50">
                    <li v-for="task in open" :key="task.id">

                        <!-- View mode -->
                        <div v-if="editing !== task.id"
                            class="flex items-start gap-3 px-5 py-3.5 group hover:bg-gray-50/70 transition-colors">
                            <!-- Checkbox -->
                            <button @click="toggleComplete(task)"
                                class="mt-0.5 w-5 h-5 rounded-full border-2 border-gray-300 hover:border-purple-500 flex items-center justify-center shrink-0 transition-colors" />

                            <!-- Body -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-800">{{ task.title }}</span>
                                    <span
                                        :class="['w-2 h-2 rounded-full shrink-0', priorityConfig[task.priority].dot]"
                                        :title="priorityConfig[task.priority].label"
                                    />
                                </div>
                                <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                    <span v-if="task.due_date"
                                        :class="['text-xs flex items-center gap-1', isOverdue(task.due_date) ? 'text-red-500 font-medium' : 'text-gray-400']">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ fmtDate(task.due_date) }}
                                        <span v-if="isOverdue(task.due_date)" class="text-red-400"> · scaduta</span>
                                    </span>
                                    <span v-if="task.notes" class="text-xs text-gray-400 truncate max-w-xs italic">
                                        {{ task.notes }}
                                    </span>
                                </div>
                            </div>

                            <!-- Hover actions -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                <button @click="startEdit(task)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition-colors"
                                    title="Modifica">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button @click="deleteTask(task)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    title="Elimina">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Edit mode -->
                        <div v-else class="px-5 py-4 bg-purple-50/40 border-l-4 border-purple-400 space-y-3">
                            <input v-model="editForm.title"
                                @keydown.escape="cancelEdit"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-300"
                            />
                            <textarea v-model="editForm.notes" rows="2" placeholder="Note…"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 resize-none" />
                            <div class="flex gap-3 flex-wrap">
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs text-gray-500">Scadenza</label>
                                    <input v-model="editForm.due_date" type="date"
                                        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs text-gray-500">Priorità</label>
                                    <select v-model="editForm.priority"
                                        class="rounded-xl border border-gray-200 px-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white">
                                        <option value="high">Alta</option>
                                        <option value="medium">Media</option>
                                        <option value="low">Bassa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2 justify-end pt-1">
                                <button @click="cancelEdit"
                                    class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                                    Annulla
                                </button>
                                <button @click="saveEdit(task)" :disabled="editForm.processing"
                                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                    Salva
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Completed section (collapsible) -->
                <details v-if="done.length > 0" class="border-t border-gray-100 group/details">
                    <summary class="px-5 py-3 text-xs font-medium text-gray-400 cursor-pointer hover:text-gray-600 select-none flex items-center gap-1.5 list-none">
                        <svg class="w-3.5 h-3.5 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ done.length }} {{ done.length === 1 ? 'completata' : 'completate' }}
                    </summary>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="task in done" :key="task.id"
                            class="flex items-center gap-3 px-5 py-3 group hover:bg-gray-50 transition-colors">
                            <button @click="toggleComplete(task)"
                                class="w-5 h-5 rounded-full bg-purple-100 border-2 border-purple-300 flex items-center justify-center shrink-0 hover:bg-purple-200 transition-colors">
                                <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <span class="flex-1 text-sm text-gray-400 line-through truncate">{{ task.title }}</span>
                            <button @click="deleteTask(task)"
                                class="p-1.5 rounded-lg text-gray-300 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                </details>

            </div>
        </div>
    </AppLayout>
</template>
