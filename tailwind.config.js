import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
const {blackA, green, mauve, slate} = require('@radix-ui/colors');

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './vendor/laravel/jetstream/**/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue'
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans]
      },
      colors: {
        ...blackA,
        ...green,
        ...mauve,
        ...slate
      },
      keyframes: {
        hide: {
          from: {opacity: 1},
          to: {opacity: 0}
        },
        slideIn: {
          from: {transform: 'translateX(calc(100% + var(--viewport-padding)))'},
          to: {transform: 'translateX(0)'}
        },
        swipeOut: {
          from: {transform: 'translateX(var(--radix-toast-swipe-end-x))'},
          to: {transform: 'translateX(calc(100% + var(--viewport-padding)))'}
        }
      },
      animation: {
        hide: 'hide 100ms ease-in',
        slideIn: 'slideIn 150ms cubic-bezier(0.16, 1, 0.3, 1)',
        swipeOut: 'swipeOut 100ms ease-out'
      }
    }
  },

  plugins: [forms, typography]
};
