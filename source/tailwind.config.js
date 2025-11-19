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
            colors: {
                'propoff-red': '#af1919',
                'propoff-orange': '#f47612',
                'propoff-green': '#57d025',
                'propoff-dark-green': '#186916',
                'propoff-blue': '#1a3490',
            },
        },
    },

    plugins: [forms],
};
