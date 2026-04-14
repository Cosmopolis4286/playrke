<template>
    <Head :title="`Error ${status}`" />

    <div class="error-page d-flex align-items-center justify-content-center min-vh-100 bg-light px-3">
        <div class="text-center" style="max-width: 480px; width: 100%">

            <!-- Círculo con icono -->
            <div class="error-icon-wrapper mx-auto mb-4">
                <span class="error-icon-emoji">{{ config.emoji }}</span>
                <div class="error-code text-danger">{{ status }}</div>
            </div>

            <!-- Título -->
            <h1 class="fw-bold fs-3 text-dark mb-2">{{ config.title }}</h1>

            <!-- Descripción -->
            <p class="text-muted mb-5">{{ config.description }}</p>

            <!-- Mensaje técnico (solo en dev o cuando hay mensaje explícito) -->
            <div v-if="displayMessage" class="alert alert-light border text-start mb-5 py-2 px-3 small font-monospace text-muted">
                {{ displayMessage }}
            </div>

            <!-- Acciones -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a :href="homeUrl" class="btn btn-danger px-4 py-2">
                    <i class="fas fa-home me-2"></i>Ir al inicio
                </a>
                <button type="button" class="btn btn-outline-secondary px-4 py-2" @click="goBack">
                    <i class="fas fa-arrow-left me-2"></i>Volver atrás
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
}

const ERROR_CONFIGS: Record<number, ErrorConfig> = {
    403: {
        emoji: '🔒',
        title: 'Acceso denegado',
        description: 'No tienes permiso para acceder a este recurso. Contacta al administrador si crees que es un error.',
    },
    404: {
        emoji: '😕',
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o fue movida. Verifica la URL o regresa al inicio.',
    },
    500: {
        emoji: '⚠️',
        title: 'Error del servidor',
        description: 'Ocurrió un error interno. Estamos trabajando para solucionarlo. Intenta de nuevo en unos minutos.',
    },
    503: {
        emoji: '🔧',
        title: 'Servicio no disponible',
        description: 'El servicio está temporalmente fuera de línea por mantenimiento. Vuelve en unos momentos.',
    },
}

const FALLBACK_CONFIG: ErrorConfig = {
    emoji: '❌',
    title: 'Algo salió mal',
    description: 'Ocurrió un error inesperado. Por favor intenta nuevamente.',
}

const config = computed<ErrorConfig>(() => ERROR_CONFIGS[props.status] ?? FALLBACK_CONFIG)

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
    background-color: #f8f9fa;
}

.error-icon-wrapper {
    position: relative;
    width: 160px;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.error-icon-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background-color: #fee2e2;
}

.error-icon-emoji {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 1.5rem;
    z-index: 1;
}

.error-code {
    position: relative;
    font-size: 5rem;
    font-weight: 900;
    line-height: 1;
    z-index: 1;
}
</style>
