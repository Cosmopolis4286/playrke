<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-slate-800 rounded-2xl border border-slate-700 shadow-lg mb-4">
          <Icon name="lucide:key-round" class="h-16 w-16 text-blue-500" />
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">Recuperar Contraseña</h1>
        <p class="text-slate-400">Panel Administrativo</p>
      </div>

      <!-- Formulario -->
      <div class="bg-slate-800/50 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-slate-700">
        <!-- Mensaje de éxito -->
        <div v-if="emailSent" class="mb-6 p-4 bg-green-500/10 border border-green-500/50 rounded-lg">
          <div class="flex items-start">
            <Icon name="lucide:check-circle" class="h-5 w-5 text-green-400 mr-2 flex-shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-green-300 mb-1">Email enviado</p>
              <p class="text-sm text-green-400">
                Hemos enviado un enlace de recuperación a tu correo electrónico corporativo.
              </p>
            </div>
          </div>
        </div>

        <form v-if="!emailSent" @submit.prevent="handleForgotPassword" class="space-y-6">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-slate-300 mb-2">
              Email corporativo
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:mail" class="h-5 w-5 text-slate-400" />
              </div>
              <input
                id="email"
                v-model="email"
                type="email"
                class="block w-full pl-10 pr-3 py-3 border border-slate-600 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="admin@playrke.com"
                required
              >
            </div>
            <p v-if="errors.email" class="mt-2 text-sm text-red-400">{{ errors.email }}</p>
          </div>

          <!-- Error general -->
          <div v-if="resetError" class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
            <div class="flex items-start">
              <Icon name="lucide:alert-triangle" class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-300">{{ resetError }}</p>
            </div>
          </div>

          <!-- Botón de envío -->
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
            to="/admin/auth/login"
            class="inline-flex items-center text-sm text-blue-400 hover:text-blue-300 transition"
          >
            <Icon name="lucide:arrow-left" class="h-4 w-4 mr-1" />
            Volver al inicio de sesión
          </NuxtLink>
        </div>

        <!-- Información de seguridad -->
        <div class="mt-6 p-4 bg-slate-700/50 rounded-lg border border-slate-600">
          <div class="flex items-start">
            <Icon name="lucide:shield-alert" class="h-5 w-5 text-slate-400 mr-2 flex-shrink-0 mt-0.5" />
            <div class="text-xs text-slate-400">
              <p class="font-medium mb-1">Nota de seguridad</p>
              <p>Si no solicitaste este cambio, contacta inmediatamente al departamento de IT.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de recuperación de contraseña para administradores (Admin Context)
 *
 * Diseño profesional con colores slate/gray
 * Envía email de recuperación a correos corporativos
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
      resetError.value = 'No encontramos una cuenta de administrador con ese email.'
    } else if (error.response?.status === 403) {
      resetError.value = 'Acceso denegado. Contacta al departamento de IT.'
    } else {
      resetError.value = 'Error al enviar el email. Por favor intenta de nuevo.'
    }
    console.error('[Admin ForgotPassword] Error:', error)
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Recuperar Contraseña - Admin',
  meta: [
    { name: 'description', content: 'Recuperar contraseña del panel administrativo' },
    { name: 'robots', content: 'noindex, nofollow' }
  ]
})
</script>
