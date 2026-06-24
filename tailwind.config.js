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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Display serif per titoli/saluto e wordmark (mockup NutriMente)
                serif: ['Fraunces', 'Cormorant Garamond', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Sfondi caldi (crema / sabbia)
                cream: {
                    50:  '#FCFBF8',
                    100: '#F8F5EF',
                    200: '#F2EEE5',
                    300: '#EAE4D7',
                },
                // Verde salvia — colore primario (bottoni, accenti "Gestione Ansia")
                sage: {
                    50:  '#F1F3EC',
                    100: '#E4E8D8',
                    200: '#CBD3B5',
                    300: '#AEB98F',
                    400: '#94A172',
                    500: '#7C8A5E',
                    600: '#68764E',
                    700: '#535E3F',
                },
                // Lavanda — accenti secondari ("Supporto Caregiver", citazioni)
                lavender: {
                    50:  '#F3F0F9',
                    100: '#EBE5F4',
                    200: '#D9CFEA',
                    300: '#C3B4DD',
                    400: '#AB97CE',
                    500: '#9079BB',
                    600: '#75619B',
                },
                // Pesca / terracotta — accenti ("Alimentazione Consapevole")
                peach: {
                    50:  '#FBF1E8',
                    100: '#F6E4D2',
                    200: '#EECCAD',
                    300: '#E3B083',
                    400: '#D4945E',
                    500: '#BE7B4A',
                },
            },
            borderRadius: {
                '4xl': '2rem',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(80,70,55,0.04), 0 8px 24px -12px rgba(80,70,55,0.10)',
            },
        },
    },

    plugins: [forms, typography],
};
