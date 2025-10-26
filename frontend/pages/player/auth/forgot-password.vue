<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 mb-4">
          <Icon name="lucide:key-round" class="h-16 w-16 text-yellow-400" />
        </div>
        <h1 class="text-3xl font-bold text-white mb-2 font-gaming">¿Olvidaste tu contraseña?</h1>
        <p class="text-pink-200">No te preocupes, te ayudaremos a recuperarla</p>
      </div>

      <!-- Formulario -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <!-- Mensaje de éxito -->
        <div v-if="emailSent" class="mb-6 p-4 bg-green-500/20 border border-green-400/50 rounded-lg backdrop-blur-sm">
          <div class="flex items-start">
            <Icon name="lucide:check-circle" class="h-5 w-5 text-green-300 mr-2 flex-shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-green-200 mb-1">¡Email enviado!</p>
              <p class="text-sm text-green-300">
                Hemos enviado un enlace de recuperación a tu correo electrónico.
                Revisa tu bandeja de entrada y spam.
              </p>
            </div>
          </div>
        </div>

        <form v-if="!emailSent" @submit.prevent="handleForgotPassword" class="space-y-6">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-pink-200 mb-2">
              Email registrado
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:mail" class="h-5 w-5 text-pink-300" />
              </div>
              <input
                id="email"
                v-model="email"
                type="email"
                class="block w-full pl-10 pr-3 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                placeholder="tu@email.com"
                required
              >
            </div>
            <p v-if="errors.email" class="mt-2 text-sm text-red-300">{{ errors.email }}</p>
          </div>

          <!-- Error general -->
          <div v-if="resetError" class="p-4 bg-red-500/20 border border-red-400/50 rounded-lg backdrop-blur-sm">
            <div class="flex items-start">
              <Icon name="lucide:alert-circle" class="h-5 w-5 text-red-300 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-200">{{ resetError }}</p>
            </div>
          </div>

          <!-- Botón de envío -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed transition transform hover:scale-105"
          >
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Enviando...' : '📧 Enviar enlace de recuperación' }}</span>
          </button>
        </form>

        <!-- Volver al login -->
        <div class="mt-6 text-center">
          <NuxtLink
            to="/player/auth/login"
            class="inline-flex items-center text-sm text-yellow-400 hover:text-yellow-300 font-medium transition"
          >
            <Icon name="lucide:arrow-left" class="h-4 w-4 mr-1" />
            Volver al inicio de sesión
          </NuxtLink>
        </div>
      </div>

      <!-- Información adicional -->
      <div class="mt-6 text-center text-sm text-pink-300/70">
        <p>Si no recibes el email en 5 minutos, revisa tu carpeta de spam</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de recuperación de contraseña para jugadores (Player Context)
 *
 * Diseño gaming con gradientes purple-pink-red
 * Envía email de recuperación usando Laravel Password Reset
 */

definePageMeta({
  layout: false,
  middleware: 'guest'
})

const config = useRuntimeConfig()

// Estado del formulario
const email = ref('')
const isLoading = ref(false)
const emailSent = ref(false)
const errors = ref<Record<string, string>>({})
const resetError = ref('')

/**
 * Maneja el envío del formulario de recuperación
 */
async function handleForgotPassword() {
  // Limpiar errores previos
  errors.value = {}
  resetError.value = ''

  // Validación básica
  if (!email.value.trim()) {
    errors.value.email = 'El email es requerido'
    return
  }

  isLoading.value = true

  try {
    // Enviar solicitud de reset
    await $fetch(`${config.public.apiBase}/auth/forgot-password`, {
      method: 'POST',
      body: {
        email: email.value
      },
      credentials: 'include'
    })

    emailSent.value = true

  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else if (error.response?.status === 404) {
      resetError.value = 'No encontramos una cuenta con ese email.'
    } else {
      resetError.value = 'Error al enviar el email. Por favor intenta de nuevo.'
    }
    console.error('[Player ForgotPassword] Error:', error)
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Recuperar Contraseña - PlayRKE',
  meta: [
    { name: 'description', content: 'Recupera tu contraseña de PlayRKE' }
  ]
})
</script>
