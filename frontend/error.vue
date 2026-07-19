<!--
  Página de error global.

  Maneja todos los errores de la aplicación con diseño
  adaptivo según el contexto (admin/player).
-->

<template>
  <div :class="errorLayoutClass">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 overflow-x-hidden">
      <div class="max-w-md w-full min-w-0 text-center" role="alert" aria-live="polite">
        <!-- Icono de error -->
        <div class="mb-6 sm:mb-8">
          <Icon
            :name="errorIcon"
            :class="errorIconClass"
            aria-hidden="true"
          />
        </div>

        <!-- Código de error -->
        <h1 :class="errorTitleClass">
          {{ error.statusCode }}
        </h1>

        <!-- Mensaje de error -->
        <h2 :class="errorSubtitleClass">
          {{ errorTitle }}
        </h2>

        <p :class="errorDescriptionClass">
          {{ errorMessage }}
        </p>

        <!-- Botones de acción -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center mt-6 sm:mt-8">
          <button
            @click="handleError"
            :class="primaryButtonClass"
          >
            <Icon name="lucide:refresh-cw" class="w-4 h-4 mr-2 shrink-0" aria-hidden="true" />
            Intentar Nuevamente
          </button>

          <button
            @click="goHome"
            :class="secondaryButtonClass"
          >
            <Icon name="lucide:home" class="w-4 h-4 mr-2 shrink-0" aria-hidden="true" />
            Ir al Inicio
          </button>
        </div>

        <!-- Información de debugging (solo en desarrollo) -->
        <div v-if="isDevelopment" class="mt-8 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg text-left min-w-0">
          <details class="text-sm">
            <summary class="cursor-pointer font-medium text-red-800 dark:text-red-200 mb-2">
              Información de Debug
            </summary>
            <div class="text-red-700 dark:text-red-300 space-y-2 min-w-0">
              <p class="break-words"><strong>URL:</strong> {{ error.url || 'N/A' }}</p>
              <p class="break-words"><strong>Status:</strong> {{ error.statusCode }}</p>
              <p class="break-words"><strong>Message:</strong> {{ error.statusMessage }}</p>
              <div v-if="error.stack" class="min-w-0">
                <p><strong>Stack:</strong></p>
                <pre class="text-xs bg-red-100 dark:bg-red-800 p-2 rounded overflow-auto whitespace-pre-wrap break-words max-h-64">{{ error.stack }}</pre>
              </div>
            </div>
          </details>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Props del error
interface ErrorProps {
  error: {
    statusCode: number
    statusMessage: string
    url?: string
    stack?: string
  }
}

const props = defineProps<ErrorProps>()

// Configuración de la página
definePageMeta({
  layout: false
})

// Variables del entorno
const isDevelopment = process.dev

// Detectar contexto actual
const context = useState('app.context', () => 'player')

// Computed para clases dinámicas según el contexto
const errorLayoutClass = computed(() => {
  return context.value === 'admin'
    ? 'admin-layout'
    : 'player-layout'
})

const errorIconClass = computed(() => {
  const baseClass = 'w-16 h-16 sm:w-24 sm:h-24 mx-auto'
  return context.value === 'admin'
    ? `${baseClass} text-red-500`
    : `${baseClass} text-red-400`
})

const errorTitleClass = computed(() => {
  const baseClass = 'text-6xl sm:text-8xl font-bold mb-4 break-words'
  return context.value === 'admin'
    ? `${baseClass} text-slate-900 dark:text-white`
    : `${baseClass} text-white font-gaming`
})

const errorSubtitleClass = computed(() => {
  const baseClass = 'text-xl sm:text-2xl font-semibold mb-4 break-words'
  return context.value === 'admin'
    ? `${baseClass} text-slate-800 dark:text-slate-200`
    : `${baseClass} text-white font-gaming`
})

const errorDescriptionClass = computed(() => {
  const baseClass = 'text-base sm:text-lg mb-8 break-words'
  return context.value === 'admin'
    ? `${baseClass} text-slate-600 dark:text-slate-400`
    : `${baseClass} text-slate-300`
})

const primaryButtonClass = computed(() => {
  return context.value === 'admin'
    ? 'btn-admin'
    : 'btn-gaming'
})

const secondaryButtonClass = computed(() => {
  return context.value === 'admin'
    ? 'btn-secondary'
    : 'btn-ghost'
})

// Computed para contenido dinámico
const errorIcon = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return 'lucide:search-x'
    case 403:
      return 'lucide:shield-x'
    case 500:
      return 'lucide:server-crash'
    default:
      return 'lucide:alert-triangle'
  }
})

const errorTitle = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return '¡Página no encontrada!'
    case 403:
      return '¡Acceso denegado!'
    case 500:
      return '¡Error del servidor!'
    default:
      return '¡Algo salió mal!'
  }
})

const errorMessage = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return 'La página que buscas no existe o ha sido movida.'
    case 403:
      return 'No tienes permisos para acceder a este recurso.'
    case 500:
      return 'Ocurrió un error interno en el servidor. Inténtalo más tarde.'
    default:
      return props.error.statusMessage || 'Ha ocurrido un error inesperado.'
  }
})

// SEO dinámico
useHead({
  title: computed(() => `Error ${props.error.statusCode} - PlayRKE`),
  meta: [
    {
      name: 'description',
      content: computed(() => `Error ${props.error.statusCode}: ${errorMessage.value}`)
    }
  ]
})

// Métodos
const handleError = async () => {
  await clearError({ redirect: '/' })
}

const goHome = () => {
  const homeRoute = context.value === 'admin' ? '/admin' : '/player'
  navigateTo(homeRoute)
}

// Logging del error
onMounted(() => {
  console.error('[Error Page]', {
    statusCode: props.error.statusCode,
    statusMessage: props.error.statusMessage,
    url: props.error.url,
    context: context.value
  })
})
</script>