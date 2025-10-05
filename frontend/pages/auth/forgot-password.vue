<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 via-purple-900 to-pink-900">
    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">PlayRKE</h1>
        <p class="text-gray-300">Recupera tu contraseña</p>
      </div>

      <!-- Formulario -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <!-- Mensaje de éxito -->
        <div v-if="emailSent" class="mb-6 p-4 bg-green-500/10 border border-green-500/50 rounded-lg">
          <div class="flex items-start">
            <Icon name="lucide:check-circle" class="h-5 w-5 text-green-400 mt-0.5 mr-3" />
            <div>
              <p class="text-sm text-green-400 font-medium">Email enviado exitosamente</p>
              <p class="text-sm text-gray-300 mt-1">
                Revisa tu correo electrónico. Te hemos enviado un enlace para restablecer tu contraseña.
              </p>
            </div>
          </div>
        </div>

        <!-- Formulario -->
        <form v-else @submit.prevent="handleForgotPassword" class="space-y-6">
          <div>
            <p class="text-sm text-gray-300 mb-6">
              Ingresa tu dirección de email y te enviaremos un enlace para restablecer tu contraseña.
            </p>

            <label for="email" class="block text-sm font-medium text-gray-200 mb-2">
              Email
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:mail" class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="block w-full pl-10 pr-3 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="tu@email.com"
                required
              >
            </div>
            <p v-if="errors.email" class="mt-2 text-sm text-red-400">{{ errors.email }}</p>
          </div>

          <!-- Error general -->
          <div v-if="errorMessage" class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
            <p class="text-sm text-red-400">{{ errorMessage }}</p>
          </div>

          <!-- Botón -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Enviando...' : 'Enviar enlace de recuperación' }}</span>
          </button>
        </form>

        <!-- Volver al login -->
        <div class="mt-6 text-center">
          <NuxtLink
            to="/auth/login"
            class="inline-flex items-center text-sm text-blue-400 hover:text-blue-300 transition"
          >
            <Icon name="lucide:arrow-left" class="h-4 w-4 mr-2" />
            Volver al inicio de sesión
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de recuperación de contraseña
 *
 * Permite a los usuarios solicitar un enlace para restablecer su contraseña.
 */

definePageMeta({
  layout: false,
  middleware: 'guest'
})

const config = useRuntimeConfig()

// Estado del formulario
const form = ref({
  email: ''
})

// Estado de UI
const isLoading = ref(false)
const emailSent = ref(false)
const errors = ref<Record<string, string>>({})
const errorMessage = ref('')

/**
 * Maneja el envío del formulario
 */
async function handleForgotPassword() {
  // Limpiar errores previos
  errors.value = {}
  errorMessage.value = ''

  if (!form.value.email) {
    errors.value.email = 'El email es requerido'
    return
  }

  isLoading.value = true

  try {
    const response = await $fetch(`${config.public.apiBase}/forgot-password`, {
      method: 'POST',
      body: {
        email: form.value.email
      },
      credentials: 'include'
    })

    // Mostrar mensaje de éxito
    emailSent.value = true

  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else if (error.response?.status === 404) {
      errorMessage.value = 'No encontramos una cuenta con ese email'
    } else {
      errorMessage.value = 'Ha ocurrido un error. Por favor intenta de nuevo.'
    }
    console.error('Error en forgot password:', error)
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
