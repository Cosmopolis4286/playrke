<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 via-purple-900 to-pink-900">
    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">PlayRKE</h1>
        <p class="text-gray-300">Tu Sitio Para Ganar</p>
      </div>

      <!-- Formulario de login -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email o Username -->
          <div>
            <label for="login" class="block text-sm font-medium text-gray-200 mb-2">
              Email o Usuario
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:user" class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="login"
                v-model="form.login"
                type="text"
                class="block w-full pl-10 pr-3 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Email o nombre de usuario"
                required
              >
            </div>
            <p v-if="errors.login" class="mt-2 text-sm text-red-400">{{ errors.login }}</p>
          </div>

          <!-- Contraseña -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-200 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:lock" class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="block w-full pl-10 pr-12 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Contraseña"
                required
              >
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                @click="showPassword = !showPassword"
              >
                <Icon
                  :name="showPassword ? 'lucide:eye-off' : 'lucide:eye'"
                  class="h-5 w-5 text-gray-400 hover:text-gray-300 transition"
                />
              </button>
            </div>
            <p v-if="errors.password" class="mt-2 text-sm text-red-400">{{ errors.password }}</p>
          </div>

          <!-- Recordar sesión -->
          <div class="flex items-center justify-between">
            <label class="flex items-center">
              <input
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-800"
              >
              <span class="ml-2 text-sm text-gray-300">Recordar sesión</span>
            </label>

            <NuxtLink
              to="/auth/forgot-password"
              class="text-sm text-blue-400 hover:text-blue-300 transition"
            >
              ¿Olvidaste tu contraseña?
            </NuxtLink>
          </div>

          <!-- Error general -->
          <div v-if="loginError" class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
            <p class="text-sm text-red-400">{{ loginError }}</p>
          </div>

          <!-- Botón de login -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}</span>
          </button>
        </form>

        <!-- Registro -->
        <div v-if="allowRegistration" class="mt-6 text-center">
          <p class="text-sm text-gray-300">
            ¿No tienes una cuenta?
            <NuxtLink to="/auth/register" class="text-blue-400 hover:text-blue-300 font-medium transition">
              Regístrate
            </NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de login de PlayRKE
 *
 * Permite a los usuarios autenticarse en el sistema usando email/username y contraseña.
 * Integrado con Laravel Sanctum para autenticación stateful.
 */

definePageMeta({
  layout: false,
  middleware: 'guest'
})

const { login } = useAuth()
const router = useRouter()
const config = useRuntimeConfig()

// Estado del formulario
const form = ref({
  login: '',
  password: '',
  remember: false
})

// Estado de UI
const showPassword = ref(false)
const isLoading = ref(false)
const errors = ref<Record<string, string>>({})
const loginError = ref('')
const allowRegistration = ref(true) // Configurar desde backend

/**
 * Maneja el envío del formulario de login
 */
async function handleLogin() {
  // Limpiar errores previos
  errors.value = {}
  loginError.value = ''

  // Validación básica
  if (!form.value.login.trim()) {
    errors.value.login = 'El email o usuario es requerido'
    return
  }

  if (!form.value.password) {
    errors.value.password = 'La contraseña es requerida'
    return
  }

  isLoading.value = true

  try {
    // Intentar login
    await login({
      login: form.value.login,
      password: form.value.password,
      remember: form.value.remember
    })

    // Redirigir según el rol del usuario
    // El composable useAuth manejará la redirección

  } catch (error: any) {
    // Manejar errores
    if (error.response?.status === 422) {
      // Errores de validación
      errors.value = error.response.data.errors || {}
    } else if (error.response?.status === 401) {
      loginError.value = 'Credenciales incorrectas. Por favor verifica tu email/usuario y contraseña.'
    } else {
      loginError.value = 'Ha ocurrido un error inesperado. Por favor intenta de nuevo.'
    }
    console.error('Error en login:', error)
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Iniciar Sesión - PlayRKE',
  meta: [
    { name: 'description', content: 'Inicia sesión en PlayRKE - Tu sitio para ganar' }
  ]
})
</script>
