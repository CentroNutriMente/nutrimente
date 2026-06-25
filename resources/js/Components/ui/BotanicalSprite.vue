<script setup>
// Sprite line-art (da assets/botanicals.svg). Montato una volta per pagina;
// i motivi si referenziano con <use href="#bot-..."> e si ricolorano via `color`.
</script>

<template>
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <defs>
            <symbol id="bot-sprig" viewBox="0 0 100 36">
                <g fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 31 C 32 29 66 18 97 4"/>
                    <g stroke-width="1.1">
                        <path d="M20 27 C 22 19 28 16 33 16 C 30 21 26 25 20 27 Z" transform="rotate(-4 20 27)"/>
                        <path d="M30 23 C 28 30 24 34 19 35 C 21 28 24 24 30 23 Z"/>
                        <path d="M42 19 C 44 11 50 9 55 9 C 52 14 48 17 42 19 Z"/>
                        <path d="M52 15 C 50 22 46 25 42 26 C 43 19 47 16 52 15 Z"/>
                        <path d="M65 11 C 67 5 72 3 77 3 C 74 7 71 10 65 11 Z"/>
                        <path d="M76 7 C 74 13 71 16 67 16 C 68 10 71 8 76 7 Z"/>
                        <path d="M88 5 C 91 1 95 0 97 0 C 95 3 92 5 88 5 Z"/>
                    </g>
                </g>
            </symbol>
            <symbol id="bot-branch" viewBox="0 0 184 134">
                <g fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M176 8 C 122 30 70 62 20 128"/>
                    <g stroke-width="1">
                        <path d="M152 22 C 158 11 168 8 176 9 C 169 17 161 22 152 22 Z"/>
                        <path d="M150 24 C 146 35 137 41 128 42 C 132 31 140 25 150 24 Z"/>
                        <path d="M128 39 C 134 28 143 25 151 26 C 145 34 137 38 128 39 Z"/>
                        <path d="M126 41 C 122 52 113 57 105 58 C 109 47 117 42 126 41 Z"/>
                        <path d="M105 56 C 111 45 119 42 127 43 C 121 51 113 55 105 56 Z"/>
                        <path d="M103 58 C 99 69 91 74 83 75 C 87 64 94 59 103 58 Z"/>
                        <path d="M83 73 C 89 63 96 60 104 61 C 99 69 91 72 83 73 Z"/>
                        <path d="M81 75 C 77 86 70 90 62 91 C 66 81 73 76 81 75 Z"/>
                        <path d="M61 89 C 67 80 73 77 80 78 C 75 85 68 88 61 89 Z"/>
                        <path d="M59 91 C 56 101 49 105 42 106 C 46 96 52 92 59 91 Z"/>
                        <path d="M41 104 C 47 96 53 93 59 94 C 54 101 48 103 41 104 Z"/>
                        <path d="M39 106 C 36 115 30 119 24 120 C 28 111 33 107 39 106 Z"/>
                    </g>
                </g>
            </symbol>
            <symbol id="bot-lavender" viewBox="0 0 50 100">
                <g stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" fill="none">
                    <path d="M25 96 C 23 66 25 44 25 20"/>
                    <path d="M24 72 C 17 71 11 66 9 60 C 17 61 23 65 24 72 Z"/>
                    <path d="M26 66 C 33 65 39 60 41 54 C 33 55 27 59 26 66 Z"/>
                </g>
                <g fill="currentColor">
                    <ellipse cx="25" cy="14" rx="3.1" ry="5"/>
                    <ellipse cx="18" cy="22" rx="3" ry="4.6"/><ellipse cx="32" cy="22" rx="3" ry="4.6"/>
                    <ellipse cx="20" cy="31" rx="3" ry="4.6"/><ellipse cx="30" cy="31" rx="3" ry="4.6"/>
                    <ellipse cx="18" cy="40" rx="2.8" ry="4.4"/><ellipse cx="32" cy="40" rx="2.8" ry="4.4"/>
                    <ellipse cx="22" cy="48" rx="2.6" ry="4"/><ellipse cx="28" cy="48" rx="2.6" ry="4"/>
                </g>
            </symbol>
            <symbol id="bot-sprout" viewBox="0 0 76 90">
                <g fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M38 86 L38 38"/>
                    <path d="M37 56 C 30 54 25 47 24 40 C 32 43 37 49 37 56 Z"/>
                    <path d="M39 56 C 46 54 51 47 52 40 C 44 43 39 49 39 56 Z"/>
                    <path d="M37 46 C 32 44 28 39 27 34 C 33 36 37 41 37 46 Z"/>
                    <path d="M39 46 C 44 44 48 39 49 34 C 43 36 39 41 39 46 Z"/>
                    <path d="M38 40 C 35 35 35 28 38 24 C 41 28 41 35 38 40 Z"/>
                </g>
                <g fill="currentColor">
                    <circle cx="38" cy="20" r="1.6"/><circle cx="28" cy="14" r="1.3"/>
                    <circle cx="48" cy="15" r="1.3"/><circle cx="22" cy="24" r="1.1"/>
                    <circle cx="54" cy="25" r="1.1"/><circle cx="33" cy="9" r="1"/>
                    <circle cx="44" cy="8" r="1"/><circle cx="38" cy="28" r="1.1"/>
                </g>
            </symbol>
            <symbol id="bot-dots" viewBox="0 0 90 90">
                <g fill="currentColor">
                    <circle cx="14" cy="76" r="3"/><circle cx="28" cy="70" r="2.4"/>
                    <circle cx="22" cy="58" r="2"/><circle cx="40" cy="64" r="2.6"/>
                    <circle cx="36" cy="50" r="1.8"/><circle cx="52" cy="56" r="2.2"/>
                    <circle cx="48" cy="42" r="1.6"/><circle cx="62" cy="48" r="2"/>
                    <circle cx="58" cy="34" r="1.5"/><circle cx="72" cy="40" r="1.8"/>
                    <circle cx="68" cy="26" r="1.3"/><circle cx="80" cy="30" r="1.5"/>
                    <circle cx="78" cy="18" r="1.1"/><circle cx="86" cy="22" r="1.2"/>
                </g>
            </symbol>
            <symbol id="bot-heart" viewBox="0 0 132 46">
                <g fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 12 C 34 30 60 8 84 20"/>
                    <path d="M104 16 C 101 10 92 10 92 17 C 92 24 104 30 104 30 C 104 30 116 24 116 17 C 116 10 107 10 104 16 Z"/>
                </g>
            </symbol>
        </defs>
    </svg>
</template>
