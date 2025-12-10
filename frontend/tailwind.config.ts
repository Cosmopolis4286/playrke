import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

export default <Config>{
  content: [
    './components/**/*.{js,vue,ts}',
    './layers/**/*.{js,vue,ts}',
    './layouts/**/*.{js,vue,ts}',
    './pages/**/*.{js,vue,ts}',
    './composables/**/*.{js,ts}',
    './plugins/**/*.{js,ts}',
    './app.{js,ts,vue}',
    './error.{js,ts,vue}',

    // **Compatibilidad con Nuxt UI / Ex Nuxt UI**
    './node_modules/@nuxt/ui/**/*.{js,vue,ts}',
    './node_modules/@nuxt/ui-pro/**/*.{js,vue,ts}',

    // Si usas iconos Nuxt
    './node_modules/@nuxt/icon/**/*.{js,ts}',

    // MDX / render
    './content/**/*.{md,mdx}',
  ],

  darkMode: 'class',

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
        mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
        gaming: ['Orbitron', 'Inter', ...defaultTheme.fontFamily.sans],
      },

      colors: {
        border: 'rgb(var(--color-border) / <alpha-value>)',
        input: 'rgb(var(--color-input) / <alpha-value>)',
        ring: 'rgb(var(--color-ring) / <alpha-value>)',
        background: 'rgb(var(--color-background) / <alpha-value>)',
        foreground: 'rgb(var(--color-foreground) / <alpha-value>)',

        muted: {
          DEFAULT: 'rgb(var(--color-muted) / <alpha-value>)',
          foreground: 'rgb(var(--color-muted-foreground) / <alpha-value>)'
        },

        accent: {
          DEFAULT: 'rgb(var(--color-accent) / <alpha-value>)',
          foreground: 'rgb(var(--color-accent-foreground) / <alpha-value>)'
        },

        destructive: {
          DEFAULT: 'rgb(var(--color-destructive) / <alpha-value>)',
          foreground: 'rgb(var(--color-destructive-foreground) / <alpha-value>)'
        },

        card: {
          DEFAULT: 'rgb(var(--color-background) / <alpha-value>)',
          foreground: 'rgb(var(--color-foreground) / <alpha-value>)'
        },

        popover: {
          DEFAULT: 'rgb(var(--color-background) / <alpha-value>)',
          foreground: 'rgb(var(--color-foreground) / <alpha-value>)'
        },

        // Primary
        primary: {
          DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
          foreground: 'rgb(var(--color-primary-foreground) / <alpha-value>)',
        },

        secondary: {
          DEFAULT: 'rgb(var(--color-secondary) / <alpha-value>)',
          foreground: 'rgb(var(--color-secondary-foreground) / <alpha-value>)',
        },

        // Admin theme
        admin: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e'
        },

        // Player theme
        player: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d'
        },

        gaming: {
          gold: '#ffd700',
          'gold-dark': '#b8860b',
          silver: '#c0c0c0',
          bronze: '#cd7f32',
          neon: '#00ff00',
          'neon-blue': '#00ffff',
          'neon-pink': '#ff1493'
        },

        success: { 50: '#f0fdf4', 500: '#10b981', 900: '#064e3b' },
        warning: { 50: '#fffbeb', 500: '#f59e0b', 900: '#78350f' },
        error: { 50: '#fef2f2', 500: '#ef4444', 900: '#7f1d1d' },
        info: { 50: '#eff6ff', 500: '#3b82f6', 900: '#1e3a8a' }
      },

      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem'
      },

      animation: {
        'fade-in': 'fadeIn .5s ease-out',
        'fade-out': 'fadeOut .5s ease-out',
        'slide-up': 'slideUp .6s ease-out',
        'slide-down': 'slideDown .6s ease-out',
        'float': 'float 3s ease-in-out infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
        'spin-slow': 'spin 3s linear infinite',
      },

      keyframes: {
        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
        fadeOut: { '0%': { opacity: '1' }, '100%': { opacity: '0' } },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(30px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' }
        },
        glow: {
          '0%': { boxShadow: '0 0 5px currentColor' },
          '100%': { boxShadow: '0 0 20px currentColor' }
        }
      },

      boxShadow: {
        gaming: '0 0 20px rgba(59,130,246,.5)',
        admin: '0 4px 20px rgba(14,165,233,.15)',
        player: '0 4px 20px rgba(239,68,68,.15)'
      },

      backgroundImage: {
        'gradient-gaming': 'linear-gradient(135deg,#667eea,#764ba2)',
        'gradient-admin': 'linear-gradient(135deg,#0ea5e9,#0284c7)',
        'gradient-player': 'linear-gradient(135deg,#ef4444,#dc2626)',
      },

      screens: {
        xs: '475px',
        '3xl': '1600px',
        '4xl': '1920px'
      },

      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem'
      }
    }
  },

  plugins: [forms, typography]
}
