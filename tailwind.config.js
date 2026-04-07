import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // --- NEW: Register the Eduvidra Color Palette ---
            colors: {
                'eduvidra': {
                    'blue': '#1A4BFA',   // The Primary Tech Blue (Logo Pulse)
                    'accent': '#3B82F6', // The Light Accent Blue (Active Fields)
                    'slate': '#111827',  // The Main Text Slate Gray (Titles)
                    'gray': '#9CA3AF',   // The Input Placeholder Gray
                },
            },
        },
    },

    plugins: [forms],
};
