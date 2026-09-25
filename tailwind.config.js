import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    blue: {
                        50: '#eef3fc',
                        100: '#d7e3f7',
                        200: '#adc4ee',
                        300: '#7fa2e3',
                        400: '#4d7bd6',
                        500: '#2e5cc4',
                        600: '#1f45a3',
                        700: '#1a3a8a',
                        800: '#152e6e',
                        900: '#11255a',
                    },
                    turquoise: {
                        50: '#e6fbfc',
                        100: '#c1f4f7',
                        200: '#8ee7ed',
                        300: '#57d5df',
                        400: '#2bc0cd',
                        500: '#17aab8',
                        600: '#128896',
                        700: '#106d79',
                        800: '#0f5761',
                        900: '#0d4650',
                    },
                    gold: {
                        50: '#fef8e9',
                        100: '#fdedc0',
                        200: '#fbda85',
                        300: '#f8c34a',
                        400: '#f2a71b',
                        500: '#e08e0d',
                        600: '#bd6f0b',
                        700: '#98550e',
                        800: '#7b4412',
                        900: '#673a14',
                    },
                },
            },
        },
    },

    plugins: [forms],
};
