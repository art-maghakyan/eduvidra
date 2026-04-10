import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
            colors: {
                'eduvidra': {
                    'blue': '#1A4BFA',   // The Primary Tech Blue (Logo Pulse)
                    'accent': '#3B82F6', // The Light Accent Blue (Active Fields)
                    'slate': '#111827',  // The Main Text Slate Gray (Titles)
                    'gray': '#9CA3AF',   // The Input Placeholder Gray
                },

                // Template token compatibility (Play Tailwind)
                // Lets classes like `text-primary`, `text-dark`, `dark:bg-dark-2` work.
                primary: '#3758f9',
                'blue-dark': '#1b44c8',
                dark: '#111928',
                'dark-2': '#1f2a37',
                'dark-3': '#374151',
                'dark-4': '#4b5563',
                'dark-5': '#6b7280',
                'dark-6': '#9ca3af',
                'dark-7': '#d1d5db',
                'dark-8': '#e5e7eb',
                'body-color': '#637381',
                'body-secondary': '#8899a8',
                stroke: '#dfe4ea',
                'gray-1': '#f9fafb',
                'gray-2': '#f3f4f6',
                'gray-7': '#ced4da',
            },
            borderRadius: {
                xs: '0.125rem',
            },
            zIndex: {
                9999: '9999',
            },
            boxShadow: {
                // Template token compatibility (Play Tailwind)
                input: '0px 7px 20px rgba(0, 0, 0, 0.03)',
                form: '0px 1px 55px -11px rgba(0, 0, 0, 0.01)',
                pricing: '0px 0px 40px 0px rgba(0, 0, 0, 0.08)',
                testimonial: '0px 10px 20px 0px rgba(92, 115, 160, 0.07)',
                1: '0px 1px 3px 0px rgba(166, 175, 195, 0.4)',
                2: '0px 5px 12px 0px rgba(0, 0, 0, 0.1)',
            },
        },
    },

    plugins: [forms],
};
