import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            // Token = CSS custom properties di tokens.css (sorgente unica)
            colors: {
                bg:            'var(--bg)',
                cream:         'var(--bg)',        // ground ivory (alias usato nelle pagine)
                card:          'var(--card)',
                cardWarm:      'var(--card-warm)',
                ink:           'var(--ink)',
                inkSoft:       'var(--ink-soft)',
                inkMuted:      'var(--ink-muted)',
                line:          'var(--line)',
                sage:          'var(--sage)',
                sageDeep:      'var(--sage-deep)',
                sageLight:     'var(--sage-tint)',
                lavender:      'var(--lav)',
                lavSoft:       'var(--lav-soft)',
                lavenderLight: 'var(--lav-tint)',
                blush:         'var(--blush-tint)',  // sfondo well/pill
                blushWarm:     'var(--blush)',
                blushDeep:     'var(--warn)',         // testo su blush / "in attesa"
                ok:            'var(--ok)',
                okBg:          'var(--ok-bg)',
                warn:          'var(--warn)',
                warnBg:        'var(--warn-bg)',
                info:          'var(--info)',
                infoBg:        'var(--info-bg)',
            },
            fontFamily: {
                serif: ["'Cormorant Garamond'", 'Georgia', 'serif'],
                sans:  ["'Mulish'", ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xl2:  '22px', // card
                ctrl: '12px', // bottoni/input
            },
            boxShadow: {
                soft: 'var(--shadow)',
                btn:  'var(--shadow-btn)',
            },
        },
    },

    plugins: [forms, typography],
};
