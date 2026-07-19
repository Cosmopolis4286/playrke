<template>
    <Head :title="`Error ${status}`" />

    <div class="error-page">
        <div class="error-page__glow" aria-hidden="true"></div>

        <div class="error-card" role="alert" aria-live="polite">
            <!-- Bola de sorteo con el código de error -->
            <div class="error-ball-wrapper">
                <div class="error-ball" :style="ballStyle">
                    <span class="error-ball__code">{{ status }}</span>
                </div>
                <span class="error-ball__emoji" aria-hidden="true">{{ config.emoji }}</span>
            </div>

            <!-- Título -->
            <h1 class="error-title">{{ config.title }}</h1>

            <!-- Descripción -->
            <p class="error-description">{{ config.description }}</p>

            <!-- Mensaje técnico (solo en dev o cuando hay mensaje explícito) -->
            <div v-if="displayMessage" class="error-technical">
                <i class="fas fa-terminal" aria-hidden="true"></i>
                <span class="error-technical__text">{{ displayMessage }}</span>
            </div>

            <!-- Acciones -->
            <div class="error-actions">
                <a :href="homeUrl" class="error-btn error-btn--primary">
                    <i class="fas fa-home" aria-hidden="true"></i>
                    Ir al inicio
                </a>
                <button type="button" class="error-btn error-btn--ghost" @click="goBack">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Volver atrás
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
    status: number
    message?: string | null
    url?: string | null
}>()

type ErrorConfig = {
    emoji: string
    title: string
    description: string
    /** Tokens de color de columna bingo (--bc*) usados para tintar la bola */
    accent: string
}

const ERROR_CONFIGS: Record<number, ErrorConfig> = {
    403: {
        emoji: '🔒',
        title: 'Acceso denegado',
        description: 'No tienes permiso para acceder a este recurso. Contacta al administrador si crees que es un error.',
        accent: 'bc3', // dorado
    },
    404: {
        emoji: '🔍',
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o fue movida. Verifica la URL o regresa al inicio.',
        accent: 'bc0', // azul
    },
    500: {
        emoji: '⚠️',
        title: 'Error del servidor',
        description: 'Ocurrió un error interno. Estamos trabajando para solucionarlo. Intenta de nuevo en unos minutos.',
        accent: 'bc4', // rojo
    },
    503: {
        emoji: '🛠️',
        title: 'Servicio no disponible',
        description: 'El servicio está temporalmente fuera de línea por mantenimiento. Vuelve en unos momentos.',
        accent: 'bc1', // morado
    },
}

const FALLBACK_CONFIG: ErrorConfig = {
    emoji: '🎲',
    title: 'Algo salió mal',
    description: 'Ocurrió un error inesperado. Por favor intenta nuevamente.',
    accent: 'bc2', // verde
}

const config = computed<ErrorConfig>(() => ERROR_CONFIGS[props.status] ?? FALLBACK_CONFIG)

const ballStyle = computed(() => ({
    '--ball-solid': `var(--${config.value.accent}-solid)`,
    '--ball-light': `var(--${config.value.accent}-light)`,
    '--ball-dark': `var(--${config.value.accent}-dark)`,
    '--ball-glow': `var(--${config.value.accent}-glow)`,
}))

const isDev = import.meta.env.DEV

// Mostrar el mensaje técnico solo en dev, o si es un mensaje limpio (no stack trace)
const displayMessage = computed<string | null>(() => {
    if (!props.message) return null
    if (isDev) return props.message
    // En producción solo mostrar mensajes cortos (no stack traces)
    return props.message.length < 120 ? props.message : null
})

const homeUrl = computed<string>(() => {
    if (!props.url) return '/'
    try {
        const u = new URL(props.url)
        return u.pathname.startsWith('/admin') ? '/admin' : '/'
    } catch {
        return '/'
    }
})

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back()
    } else {
        window.location.href = homeUrl.value
    }
}
</script>

<style scoped>
.error-page {
    position: relative;
    min-height: 100vh;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: var(--ds-bg);
    overflow-x: hidden;
    box-sizing: border-box;
}

/* Resplandor ambiental de fondo, sutil y consistente con el tema del juego */
.error-page__glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 20% 20%, rgba(123, 63, 208, 0.16), transparent 45%),
        radial-gradient(circle at 80% 75%, rgba(26, 125, 224, 0.14), transparent 45%);
}

.error-card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 460px;
    min-width: 0;
    text-align: center;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 24px;
    padding: 2.5rem 1.5rem 2rem;
    box-shadow: var(--ds-card-hover-shadow);
    box-sizing: border-box;
    backdrop-filter: blur(6px);
}

@media (min-width: 480px) {
    .error-card {
        padding: 3rem 2rem 2.5rem;
        border-radius: 28px;
    }
}

/* ── Bola de sorteo ── */
.error-ball-wrapper {
    position: relative;
    width: 112px;
    height: 112px;
    margin: 0 auto 1.5rem;
}

@media (min-width: 480px) {
    .error-ball-wrapper {
        width: 140px;
        height: 140px;
        margin: 0 auto 1.75rem;
    }
}

.error-ball {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 32% 28%, var(--ball-light), var(--ball-solid) 55%, var(--ball-dark) 100%);
    box-shadow:
        0 0 0 6px var(--ds-surface-2),
        0 0 32px 6px var(--ball-glow),
        inset 0 -6px 14px rgba(0, 0, 0, 0.25),
        inset 0 6px 10px rgba(255, 255, 255, 0.25);
    animation: error-ball-float 3.2s ease-in-out infinite;
}

.error-ball__code {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 2.1rem;
    line-height: 1;
    color: #fff;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
}

@media (min-width: 480px) {
    .error-ball__code {
        font-size: 2.75rem;
    }
}

.error-ball__emoji {
    position: absolute;
    top: -4px;
    right: -4px;
    font-size: 1.5rem;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border-2);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

@media (min-width: 480px) {
    .error-ball__emoji {
        top: -6px;
        right: -6px;
        font-size: 1.85rem;
        width: 48px;
        height: 48px;
    }
}

@keyframes error-ball-float {
    0%, 100% { transform: translateY(0) rotate(-2deg); }
    50%      { transform: translateY(-8px) rotate(2deg); }
}

/* ── Textos ── */
.error-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--ds-text);
    margin: 0 0 0.5rem;
    word-break: break-word;
}

@media (min-width: 480px) {
    .error-title {
        font-size: 1.5rem;
    }
}

.error-description {
    font-size: 0.9rem;
    line-height: 1.5;
    color: var(--ds-text-2);
    margin: 0 0 1.5rem;
    overflow-wrap: break-word;
}

@media (min-width: 480px) {
    .error-description {
        font-size: 0.95rem;
        margin-bottom: 1.75rem;
    }
}

.error-technical {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    text-align: left;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    color: var(--ds-text-muted);
    max-height: 8rem;
    overflow-y: auto;
    min-width: 0;
}

@media (min-width: 480px) {
    .error-technical {
        margin-bottom: 1.75rem;
        font-size: 0.8rem;
    }
}

.error-technical i {
    margin-top: 0.15rem;
    color: var(--ds-text-2);
    flex-shrink: 0;
}

.error-technical__text {
    min-width: 0;
    flex: 1;
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: pre-wrap;
}

/* ── Acciones ── */
.error-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    justify-content: center;
}

@media (min-width: 480px) {
    .error-actions {
        flex-direction: row;
    }
}

.error-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
    padding: 0.7rem 1.1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    white-space: nowrap;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

@media (min-width: 480px) {
    .error-btn {
        padding: 0.7rem 1.25rem;
        font-size: 0.95rem;
    }
}

.error-btn:hover {
    transform: translateY(-2px);
}

.error-btn:focus-visible {
    outline: 2px solid var(--bc0-light);
    outline-offset: 2px;
}

.error-btn--primary {
    background: linear-gradient(135deg, var(--bc0-solid), var(--bc1-solid));
    color: #fff;
    box-shadow: 0 6px 18px rgba(26, 125, 224, 0.35);
}

.error-btn--primary:hover {
    box-shadow: 0 10px 24px rgba(26, 125, 224, 0.45);
}

.error-btn--ghost {
    background: var(--ds-surface-2);
    border-color: var(--ds-border-2);
    color: var(--ds-text);
}

.error-btn--ghost:hover {
    background: var(--ds-border);
}

@media (prefers-reduced-motion: reduce) {
    .error-ball {
        animation: none;
    }
}
</style>
