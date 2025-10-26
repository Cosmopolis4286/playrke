import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/**
 * Configuración extendida de Tailwind CSS para PlayRKE Frontend.
 *
 * Incluye temas personalizados para admin y player, componentes
 * reutilizables, animaciones gaming y paleta de colores específica.
 */

export default {
  content: [
    './components/**/*.{js,vue,ts}',
    './layers/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
    './error.vue'
  ],

  darkMode: 'class',

  theme: {
    extend: {
      // Fuentes personalizadas
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
        mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
        gaming: ['Orbitron', 'Inter', ...defaultTheme.fontFamily.sans]
      },

      // Paleta de colores extendida
      colors: {
        // Colores del sistema de diseño
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

        // Colores principales del sistema
        primary: {
          DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
          foreground: 'rgb(var(--color-primary-foreground) / <alpha-value>)',
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
          950: '#082f49'
        },
        secondary: {
          DEFAULT: 'rgb(var(--color-secondary) / <alpha-value>)',
          foreground: 'rgb(var(--color-secondary-foreground) / <alpha-value>)'
        },

        // Colores para tema admin (azul corporativo)
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

        // Colores para tema player (gaming - rojo/naranja)
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

        // Colores de gaming (verde/dorado para premios)
        gaming: {
          gold: '#ffd700',
          'gold-dark': '#b8860b',
          silver: '#c0c0c0',
          bronze: '#cd7f32',
          neon: '#00ff00',
          'neon-blue': '#00ffff',
          'neon-pink': '#ff1493'
        },

        // Colores de estado
        success: {
          50: '#f0fdf4',
          500: '#10b981',
          900: '#064e3b'
        },
        warning: {
          50: '#fffbeb',
          500: '#f59e0b',
          900: '#78350f'
        },
        error: {
          50: '#fef2f2',
          500: '#ef4444',
          900: '#7f1d1d'
        },
        info: {
          50: '#eff6ff',
          500: '#3b82f6',
          900: '#1e3a8a'
        }
      },

      // Espaciado personalizado
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem'
      },

      // Animaciones personalizadas
      animation: {
        // Animaciones básicas
        'fade-in': 'fadeIn 0.5s ease-out',
        'fade-out': 'fadeOut 0.5s ease-out',
        'slide-up': 'slideUp 0.6s ease-out',
        'slide-down': 'slideDown 0.6s ease-out',
        'slide-left': 'slideLeft 0.6s ease-out',
        'slide-right': 'slideRight 0.6s ease-out',

        // Animaciones gaming
        'float': 'float 3s ease-in-out infinite',
        'pulse-gaming': 'pulseGaming 2s ease-in-out infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
        'shake': 'shake 0.5s ease-in-out',
        'bounce-gaming': 'bounceGaming 1s ease-in-out infinite',

        // Animaciones específicas de bingo
        'ball-drop': 'ballDrop 2s ease-out',
        'carton-mark': 'cartonMark 0.3s ease-out',
        'winner-celebration': 'winnerCelebration 2s ease-out',

        // Animaciones de carga
        'spin-slow': 'spin 3s linear infinite',
        'spin-reverse': 'spinReverse 1s linear infinite',
        'loading-dots': 'loadingDots 1.5s ease-in-out infinite'
      },

      // Keyframes para animaciones
      keyframes: {
        // Básicas
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        fadeOut: {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' }
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(30px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-30px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        slideLeft: {
          '0%': { opacity: '0', transform: 'translateX(30px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' }
        },
        slideRight: {
          '0%': { opacity: '0', transform: 'translateX(-30px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' }
        },

        // Gaming
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-10px)' }
        },
        pulseGaming: {
          '0%, 100%': { transform: 'scale(1)', opacity: '1' },
          '50%': { transform: 'scale(1.05)', opacity: '0.8' }
        },
        glow: {
          '0%': { boxShadow: '0 0 5px currentColor' },
          '100%': { boxShadow: '0 0 20px currentColor, 0 0 30px currentColor' }
        },
        shake: {
          '0%, 100%': { transform: 'translateX(0)' },
          '10%, 30%, 50%, 70%, 90%': { transform: 'translateX(-5px)' },
          '20%, 40%, 60%, 80%': { transform: 'translateX(5px)' }
        },
        bounceGaming: {
          '0%, 20%, 50%, 80%, 100%': { transform: 'translateY(0)' },
          '40%': { transform: 'translateY(-20px)' },
          '60%': { transform: 'translateY(-10px)' }
        },

        // Bingo específicas
        ballDrop: {
          '0%': { transform: 'translateY(-100px) rotate(0deg)', opacity: '0' },
          '50%': { opacity: '1' },
          '100%': { transform: 'translateY(0) rotate(360deg)', opacity: '1' }
        },
        cartonMark: {
          '0%': { transform: 'scale(1)', backgroundColor: 'transparent' },
          '50%': { transform: 'scale(1.2)', backgroundColor: 'currentColor' },
          '100%': { transform: 'scale(1)', backgroundColor: 'currentColor' }
        },
        winnerCelebration: {
          '0%': { transform: 'scale(1) rotate(0deg)' },
          '25%': { transform: 'scale(1.1) rotate(-5deg)' },
          '50%': { transform: 'scale(1.2) rotate(5deg)' },
          '75%': { transform: 'scale(1.1) rotate(-5deg)' },
          '100%': { transform: 'scale(1) rotate(0deg)' }
        },

        // Utilidades
        spinReverse: {
          '0%': { transform: 'rotate(360deg)' },
          '100%': { transform: 'rotate(0deg)' }
        },
        loadingDots: {
          '0%, 20%': { transform: 'scale(1)', opacity: '1' },
          '50%': { transform: 'scale(1.5)', opacity: '0.7' },
          '100%': { transform: 'scale(1)', opacity: '1' }
        }
      },

      // Sombras personalizadas
      boxShadow: {
        'gaming': '0 0 20px rgba(59, 130, 246, 0.5)',
        'gaming-hover': '0 0 30px rgba(59, 130, 246, 0.7)',
        'admin': '0 4px 20px rgba(14, 165, 233, 0.15)',
        'player': '0 4px 20px rgba(239, 68, 68, 0.15)',
        'neon': '0 0 10px currentColor, 0 0 20px currentColor, 0 0 30px currentColor',
        'inner-gaming': 'inset 0 2px 4px rgba(0, 0, 0, 0.1)'
      },

      // Gradientes personalizados
      backgroundImage: {
        'gradient-gaming': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'gradient-admin': 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
        'gradient-player': 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        'gradient-gold': 'linear-gradient(135deg, #ffd700 0%, #b8860b 100%)',
        'gradient-neon': 'linear-gradient(90deg, #00ff00 0%, #00ffff 50%, #ff1493 100%)'
      },

      // Configuración responsive personalizada
      screens: {
        'xs': '475px',
        '3xl': '1600px',
        '4xl': '1920px'
      },

      // Bordes redondeados personalizados
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem'
      },

      // Z-index personalizado
      zIndex: {
        '60': '60',
        '70': '70',
        '80': '80',
        '90': '90',
        '100': '100'
      }
    }
  },

  plugins: [
    forms,
    typography,

    // Plugin personalizado para componentes gaming
    function({ addComponents, theme }) {
      addComponents({
        // Botones gaming
        '.btn-gaming': {
          '@apply px-6 py-3 rounded-lg font-bold text-white bg-gradient-gaming hover:shadow-gaming transition-all duration-300 transform hover:scale-105': {},
        },
        '.btn-admin': {
          '@apply px-4 py-2 rounded-md font-medium text-white bg-admin-500 hover:bg-admin-600 transition-colors duration-200': {},
        },
        '.btn-player': {
          '@apply px-4 py-2 rounded-md font-bold text-white bg-player-500 hover:bg-player-600 transition-all duration-200 transform hover:scale-105': {},
        },

        // Cards
        '.card-gaming': {
          '@apply bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-gaming': {},
        },
        '.card-admin': {
          '@apply bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-6 shadow-admin': {},
        },
        '.card-player': {
          '@apply bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 rounded-xl p-6 shadow-player': {},
        },

        // Textos gaming
        '.text-neon': {
          '@apply text-gaming-neon drop-shadow-[0_0_10px_currentColor]': {},
        },
        '.text-gold': {
          '@apply text-gaming-gold font-bold drop-shadow-[0_0_5px_rgba(255,215,0,0.5)]': {},
        },

        // Efectos de hover gaming
        '.hover-glow': {
          '@apply transition-all duration-300 hover:shadow-neon': {},
        },

        // Layout específicos
        '.admin-layout': {
          '@apply min-h-screen bg-slate-50 dark:bg-slate-900': {},
        },
        '.player-layout': {
          '@apply min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900': {},
        }
      })
    }
  ]
} satisfies Config