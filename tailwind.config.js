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
            // Token del template dashboard di riferimento
            colors: {
                cream:         '#FBF7F2',
                sage:          '#7C8B6F',
                sageLight:     '#E9EEE3',
                lavender:      '#B9A8D9',
                lavenderLight: '#EFEAF8',
                blush:         '#F3DCD4',
                blushDeep:     '#C98A74', // testo/accento su blush (derivato)
                ink:           '#2E2A26',
                inkSoft:       '#6B645C',
                line:          '#ECE6DD',
            },
            fontFamily: {
                // Titoli/saluti in serif, corpo in Inter
                serif: ["'Cormorant Garamond'", 'Georgia', 'serif'],
                sans:  ["'Inter'", ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xl2: '1.25rem', // 20px — card
            },
        },
    },

    plugins: [forms, typography],
};
