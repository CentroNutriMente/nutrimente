<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    sara: Object, // { name, title, category, bio, photo, slug, phone, session_duration_minutes, session_price, areas: [] }
});

const formUrl = `/prenota/${props.sara.slug}`;

// ── Areas of work (fallback if none provided) ────────────────────────────────
const areas = props.sara.areas?.length
    ? props.sara.areas
    : ['Supporto psicologico', 'Percorsi di consapevolezza', 'Gestione dello stress'];

// Small decorative icon set, cycled across the activity cards.
const areaIcons = [
    'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', // heart
    'M12 3v18m9-9H3', // plus
    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', // check-circle
    'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9', // globe
    'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', // book
    'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', // home
];

// ── Studio carousel ──────────────────────────────────────────────────────────
// Drop real photos in public/studio/ named studio-1.jpg … to replace placeholders.
const studio = [
    { src: '/studio/studio-1.jpg', caption: "La sala d'attesa", hint: 'Uno spazio accogliente in cui sentirsi a proprio agio' },
    { src: '/studio/studio-2.jpg', caption: 'Lo studio',          hint: 'Un ambiente riservato e protetto per i colloqui' },
    { src: '/studio/studio-3.jpg', caption: "L'ingresso",         hint: 'Facile da raggiungere, pensato per accoglierti' },
];
const imgFailed = reactive({});
const current = ref(0);
let autoTimer = null;

const next = () => { current.value = (current.value + 1) % studio.length; };
const prev = () => { current.value = (current.value - 1 + studio.length) % studio.length; };
const goTo = (i) => { current.value = i; };

const startAuto = () => { stopAuto(); autoTimer = setInterval(next, 5000); };
const stopAuto = () => { if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } };

// ── Smooth scroll + scroll-reveal ────────────────────────────────────────────
let observer = null;

const scrollTo = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

onMounted(async () => {
    startAuto();
    await nextTick();
    observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-visible'); observer.unobserve(e.target); } }),
        { threshold: 0.12 },
    );
    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
});

onUnmounted(() => { stopAuto(); observer?.disconnect(); });

const year = new Date().getFullYear();
</script>

<template>
    <div class="landing min-h-screen bg-[#FBFAFF] text-[#4A4458] antialiased">

        <!-- ── Top nav ─────────────────────────────────────────────────────── -->
        <nav class="fixed top-0 inset-x-0 z-30 backdrop-blur-md bg-white/75 border-b border-[#EFE9FB]">
            <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 group">
                    <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#A98CE6] to-[#8B6FD6] text-white font-serif text-lg shadow-sm">S</span>
                    <span class="font-semibold tracking-tight text-[#3D2B63]">{{ sara.title }} {{ sara.name }}</span>
                </a>
                <div class="flex items-center gap-2">
                    <a href="/mia-area"
                       class="hidden sm:inline-flex text-sm text-[#6B6577] hover:text-[#3D2B63] px-3 py-1.5 rounded-xl hover:bg-[#F3EEFC] transition-colors font-medium">
                        Area clienti
                    </a>
                    <!-- Dashboard / professional access — kept on top as requested -->
                    <a href="/login"
                       class="text-sm font-semibold text-white bg-[#9575E0] hover:bg-[#7E5DD1] px-4 py-2 rounded-xl transition-colors shadow-sm shadow-[#9575E0]/30">
                        Accesso professionisti
                    </a>
                </div>
            </div>
        </nav>

        <!-- ── Hero (first sight) ──────────────────────────────────────────── -->
        <header class="relative overflow-hidden pt-16">
            <!-- soft lilac glow backdrop -->
            <div class="pointer-events-none absolute -top-40 -right-40 w-[42rem] h-[42rem] rounded-full bg-gradient-to-br from-[#EAD9FB] via-[#F0E7FC] to-transparent blur-3xl opacity-70"></div>
            <div class="pointer-events-none absolute -bottom-48 -left-32 w-[36rem] h-[36rem] rounded-full bg-gradient-to-tr from-[#E7DEFA] to-transparent blur-3xl opacity-60"></div>

            <div class="relative max-w-6xl mx-auto px-5 py-16 sm:py-24 grid lg:grid-cols-[1.05fr_0.95fr] gap-12 lg:gap-8 items-center">
                <!-- text -->
                <div class="reveal order-2 lg:order-1">
                    <span class="inline-flex items-center gap-2 text-xs font-semibold tracking-wide uppercase text-[#7E5DD1] bg-[#EFE7FB] rounded-full px-3 py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#9575E0]"></span>
                        {{ sara.category || 'Psicologa' }}
                    </span>
                    <h1 class="mt-5 font-serif text-4xl sm:text-5xl lg:text-[3.4rem] leading-[1.05] tracking-tight text-[#2F2150]">
                        {{ sara.title }} {{ sara.name }}
                    </h1>
                    <p class="mt-5 text-lg leading-relaxed text-[#6B6577] max-w-xl">
                        Uno spazio accogliente e protetto in cui sentirsi compresi e sostenuti.
                        Ascolto autentico e competenza clinica, per accompagnarti verso una
                        maggiore consapevolezza e benessere.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a :href="formUrl"
                           class="group inline-flex items-center gap-2 rounded-2xl bg-[#9575E0] hover:bg-[#7E5DD1] text-white font-semibold px-6 py-3.5 shadow-lg shadow-[#9575E0]/30 transition-all hover:-translate-y-0.5">
                            Scheda di primo contatto
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <button type="button" @click="scrollTo('attivita')"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white text-[#5E4593] font-semibold px-6 py-3.5 ring-1 ring-[#E2D6F7] hover:ring-[#C9B6EE] hover:bg-[#FAF7FE] transition-all">
                            Scopri di più
                        </button>
                    </div>

                    <div class="mt-8 flex items-center gap-6 text-sm text-[#8A8499]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#9575E0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ sara.session_duration_minutes || 50 }} min a seduta
                        </div>
                        <div class="w-px h-4 bg-[#E5DDF4]"></div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#9575E0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            In studio &amp; online
                        </div>
                    </div>
                </div>

                <!-- photo -->
                <div class="reveal order-1 lg:order-2 flex justify-center lg:justify-end">
                    <div class="relative">
                        <div class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-br from-[#D9C6F5] to-[#F2EAFC] blur-2xl opacity-70"></div>
                        <div class="relative w-64 sm:w-80 aspect-[4/5] rounded-[2.25rem] overflow-hidden ring-1 ring-white/70 shadow-2xl shadow-[#9575E0]/25 bg-gradient-to-br from-[#EFE7FB] to-[#E3D6F6]">
                            <img v-if="!imgFailed.hero" :src="sara.photo" :alt="sara.name"
                                 @error="imgFailed.hero = true"
                                 class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full grid place-items-center">
                                <span class="font-serif text-7xl text-[#9575E0]/70">
                                    {{ (sara.name || 'S').split(' ').map(w => w[0]).join('').slice(0,2) }}
                                </span>
                            </div>
                        </div>
                        <!-- floating credential chip -->
                        <div class="absolute -bottom-4 -left-4 sm:-left-6 bg-white rounded-2xl shadow-xl shadow-[#9575E0]/15 px-4 py-3 ring-1 ring-[#F0EAFB]">
                            <div class="text-xs text-[#8A8499]">Ordine Psicologi</div>
                            <div class="text-sm font-semibold text-[#3D2B63]">Albo A — Emilia-Romagna</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- scroll cue -->
            <div class="relative flex justify-center pb-10">
                <button type="button" @click="scrollTo('chi-sono')" class="text-[#B7A8DD] hover:text-[#9575E0] transition-colors animate-bounce">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </button>
            </div>
        </header>

        <!-- ── Chi sono ────────────────────────────────────────────────────── -->
        <section id="chi-sono" class="py-20 sm:py-28">
            <div class="max-w-3xl mx-auto px-5 text-center reveal">
                <h2 class="font-serif text-3xl sm:text-4xl text-[#2F2150] tracking-tight">Chi sono</h2>
                <div class="mx-auto mt-4 w-16 h-1 rounded-full bg-gradient-to-r from-[#C4ABEE] to-[#9575E0]"></div>
                <p class="mt-8 text-lg leading-relaxed text-[#615B70]">{{ sara.bio }}</p>
            </div>
        </section>

        <!-- ── Attività (offered activities) ───────────────────────────────── -->
        <section id="attivita" class="py-20 sm:py-28 bg-gradient-to-b from-white to-[#F5F0FC]">
            <div class="max-w-6xl mx-auto px-5">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <h2 class="font-serif text-3xl sm:text-4xl text-[#2F2150] tracking-tight">Di cosa mi occupo</h2>
                    <div class="mx-auto mt-4 w-16 h-1 rounded-full bg-gradient-to-r from-[#C4ABEE] to-[#9575E0]"></div>
                    <p class="mt-6 text-[#6B6577]">Percorsi costruiti su misura, nel rispetto della tua unicità.</p>
                </div>

                <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="(area, i) in areas" :key="i"
                         class="reveal group bg-white rounded-3xl p-7 ring-1 ring-[#F0EAFB] shadow-sm hover:shadow-xl hover:shadow-[#9575E0]/10 hover:-translate-y-1 transition-all"
                         :style="{ transitionDelay: (i % 3 * 80) + 'ms' }">
                        <div class="grid place-items-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#EFE7FB] to-[#E1D2F6] text-[#7E5DD1] group-hover:from-[#9575E0] group-hover:to-[#7E5DD1] group-hover:text-white transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="areaIcons[i % areaIcons.length]"/></svg>
                        </div>
                        <h3 class="mt-5 font-semibold text-lg text-[#33264F] leading-snug">{{ area }}</h3>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Lo studio (carousel) ────────────────────────────────────────── -->
        <section id="studio" class="py-20 sm:py-28">
            <div class="max-w-5xl mx-auto px-5">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <h2 class="font-serif text-3xl sm:text-4xl text-[#2F2150] tracking-tight">Lo studio</h2>
                    <div class="mx-auto mt-4 w-16 h-1 rounded-full bg-gradient-to-r from-[#C4ABEE] to-[#9575E0]"></div>
                    <p class="mt-6 text-[#6B6577]">Lo spazio dove ti accolgo, pensato per metterti a tuo agio.</p>
                </div>

                <div class="mt-12 reveal" @mouseenter="stopAuto" @mouseleave="startAuto">
                    <div class="relative rounded-[2rem] overflow-hidden shadow-2xl shadow-[#9575E0]/20 ring-1 ring-[#EFE9FB] aspect-[16/9] bg-[#EFE7FB]">
                        <transition-group name="fade-slide" tag="div">
                            <div v-for="(s, i) in studio" :key="i" v-show="current === i" class="absolute inset-0">
                                <img v-if="!imgFailed[i]" :src="s.src" :alt="s.caption" @error="imgFailed[i] = true"
                                     class="w-full h-full object-cover" />
                                <!-- elegant placeholder until real photos are added -->
                                <div v-else class="w-full h-full grid place-items-center bg-gradient-to-br from-[#EADDFA] via-[#F1E9FC] to-[#E0D0F4]">
                                    <svg class="w-16 h-16 text-[#B69FE6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/55 to-transparent p-6 sm:p-8">
                                    <div class="text-white font-serif text-2xl">{{ s.caption }}</div>
                                    <div class="text-white/80 text-sm mt-1">{{ s.hint }}</div>
                                </div>
                            </div>
                        </transition-group>

                        <!-- arrows -->
                        <button type="button" @click="prev" aria-label="Precedente"
                                class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full bg-white/85 hover:bg-white text-[#5E4593] shadow-lg transition-all hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="next" aria-label="Successivo"
                                class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full bg-white/85 hover:bg-white text-[#5E4593] shadow-lg transition-all hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- dots -->
                    <div class="flex justify-center gap-2.5 mt-6">
                        <button v-for="(s, i) in studio" :key="i" @click="goTo(i)" :aria-label="`Vai alla slide ${i + 1}`"
                                class="h-2.5 rounded-full transition-all"
                                :class="current === i ? 'w-8 bg-[#9575E0]' : 'w-2.5 bg-[#D9CCF1] hover:bg-[#BFA9EB]'"></button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Primo contatto (final CTA) ──────────────────────────────────── -->
        <section id="primo-contatto" class="pb-24">
            <div class="max-w-5xl mx-auto px-5">
                <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#7E5DD1] via-[#8B6FD6] to-[#A98CE6] px-8 sm:px-14 py-14 sm:py-20 text-center shadow-2xl shadow-[#9575E0]/30">
                    <div class="pointer-events-none absolute -top-24 -right-16 w-80 h-80 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 -left-16 w-80 h-80 rounded-full bg-white/10 blur-2xl"></div>

                    <h2 class="relative font-serif text-3xl sm:text-4xl text-white tracking-tight">Facciamo il primo passo insieme</h2>
                    <p class="relative mt-4 text-white/85 text-lg max-w-xl mx-auto leading-relaxed">
                        Compila la scheda di primo contatto: ti ricontatterò io stessa per conoscerci
                        e capire come posso esserti utile, senza alcun impegno.
                    </p>

                    <div class="relative mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a :href="formUrl"
                           class="group inline-flex items-center gap-2 rounded-2xl bg-white text-[#5E4593] font-semibold px-8 py-4 shadow-lg hover:-translate-y-0.5 transition-all">
                            Compila la scheda di primo contatto
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a v-if="sara.phone" :href="`https://wa.me/39${sara.phone}`" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-semibold px-6 py-4 ring-1 ring-white/30 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-.957z"/></svg>
                            Scrivimi su WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ──────────────────────────────────────────────────────── -->
        <footer class="border-t border-[#EFE9FB] bg-white">
            <div class="max-w-6xl mx-auto px-5 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-[#8A8499]">
                <span>© {{ year }} {{ sara.title }} {{ sara.name }}</span>
                <div class="flex items-center gap-5">
                    <a href="/mia-area" class="hover:text-[#5E4593] transition-colors">Area clienti</a>
                    <a href="/login" class="hover:text-[#5E4593] transition-colors">Accesso professionisti</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* serif display for headings — system serif stack, no extra fonts to load */
.landing :where(.font-serif) { font-family: 'Georgia', 'Times New Roman', serif; }

/* scroll-reveal */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.22,.61,.36,1), transform .7s cubic-bezier(.22,.61,.36,1); }
.reveal.is-visible { opacity: 1; transform: none; }

/* carousel cross-fade */
.fade-slide-enter-active, .fade-slide-leave-active { transition: opacity .7s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; }

@media (prefers-reduced-motion: reduce) {
    .reveal { opacity: 1; transform: none; transition: none; }
    .animate-bounce { animation: none; }
}
</style>
