import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                primary: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
                    darker: 'rgb(var(--color-primary-darker) / <alpha-value>)',
                    light: 'rgb(var(--color-primary-light) / <alpha-value>)',
                },
                success: {
                    DEFAULT: 'rgb(var(--color-success) / <alpha-value>)',
                    dark: 'rgb(var(--color-success-dark) / <alpha-value>)',
                },
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                danger: {
                    DEFAULT: 'rgb(var(--color-danger) / <alpha-value>)',
                    dark: 'rgb(var(--color-danger-dark) / <alpha-value>)',
                },
                background: 'rgb(var(--color-background) / <alpha-value>)',
                foreground: 'rgb(var(--color-foreground) / <alpha-value>)',
                muted: {
                    DEFAULT: 'rgb(var(--color-muted) / <alpha-value>)',
                    foreground: 'rgb(var(--color-muted-foreground) / <alpha-value>)',
                },
                border: 'rgb(var(--color-border) / <alpha-value>)',
                input: 'rgb(var(--color-input) / <alpha-value>)',
                ring: 'rgb(var(--color-ring) / <alpha-value>)',
            },
            borderRadius: {
                card: 'var(--radius-card)',
                btn: 'var(--radius-btn)',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                'card-hover': 'var(--shadow-card-hover)',
            },
            spacing: {
                component: 'var(--spacing-component)',
                section: 'var(--spacing-section)',
            },
        },
    },

    plugins: [forms],
};
