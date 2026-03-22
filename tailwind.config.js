/**
 * Tailwind CSS - Configuração do GuiaWP
 *
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.2 - 2026-03-12
 */
/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  safelist: [
    'xl:grid-cols-[minmax(0,1fr)_180px_180px_auto_auto]',
  ],
  content: [
    './templates/**/*.php',
    './includes/**/*.php',
    './assets/js/**/*.js',
    '../../themes/guiawp-reset/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        'primary': 'var(--gcep-color-primary, #0052cc)',
        'background-light': '#f5f7f8',
        'background-dark': '#0f1723',
      },
      fontFamily: {
        'display': ['Inter', 'sans-serif'],
      },
      borderRadius: {
        DEFAULT: '0.25rem',
        lg: '0.5rem',
        xl: '0.75rem',
        '2xl': '1rem',
        full: '9999px',
      },
      boxShadow: {
        soft: '0 2px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -2px rgba(0,0,0,0.05)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ],
};
