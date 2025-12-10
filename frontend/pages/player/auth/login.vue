<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <!-- Demo Credentials Component (visible en dev por defecto) -->
    <AuthDemoCredentials v-model:modelValue="demoVisible" @fill-credentials="fillForm" />

    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 mb-4">
        </div>
        <h1 class="text-4xl font-bold text-white mb-2 font-gaming">PlayRKE</h1>
        <p class="text-pink-200 text-lg">¡Bienvenido de vuelta, Jugador!</p>
      </div>

      <!-- Formulario de login -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email o Username -->
          <div>
            <label for="login" class="block text-sm font-medium text-pink-200 mb-2">
              Email o Usuario
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:user" class="h-5 w-5 text-pink-300" />
              </div>
              <input id="login" v-model="form.login" type="text"
                class="block w-full pl-10 pr-3 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                placeholder="Email o nombre de usuario" required>
            </div>
            <p v-if="errors.login" class="mt-2 text-sm text-red-300">{{ errors.login }}</p>
          </div>

          <!-- Contraseña -->
          <div>
            <label for="password" class="block text-sm font-medium text-pink-200 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:lock" class="h-5 w-5 text-pink-300" />
              </div>
              <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                class="block w-full pl-10 pr-12 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                placeholder="Contraseña" required>
              <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center"
                @click="showPassword = !showPassword">
                <Icon :name="showPassword ? 'lucide:eye-off' : 'lucide:eye'"
                  class="h-5 w-5 text-pink-300 hover:text-pink-200 transition" />
              </button>
            </div>
            <p v-if="errors.password" class="mt-2 text-sm text-red-300">{{ errors.password }}</p>
          </div>

          <!-- Recordar sesión -->
          <div class="flex items-center justify-between">
            <label class="flex items-center">
              <input v-model="form.remember" type="checkbox"
                class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-pink-400/30 rounded bg-black/30">
              <span class="ml-2 text-sm text-pink-200">Recordar sesión</span>
            </label>

            <NuxtLink to="/player/auth/forgot-password"
              class="text-sm text-yellow-400 hover:text-yellow-300 transition font-medium">
              ¿Olvidaste tu contraseña?
            </NuxtLink>
          </div>

          <!-- Error general -->
          <div v-if="loginError" class="p-4 bg-red-500/20 border border-red-400/50 rounded-lg backdrop-blur-sm">
            <div class="flex items-start">
              <Icon name="lucide:alert-circle" class="h-5 w-5 text-red-300 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-200">{{ loginError }}</p>
            </div>
          </div>

          <!-- Botón de login -->
          <button type="submit" :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed transition transform hover:scale-105">
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Iniciando sesión...' : '🎮 Iniciar Sesión' }}</span>
          </button>
        </form>

        <!-- Registro -->
        <div class="mt-6 text-center">
          <p class="text-sm text-pink-200">
            ¿No tienes una cuenta?
            <NuxtLink to="/player/auth/register"
              class="text-yellow-400 hover:text-yellow-300 font-bold transition ml-1">
              ¡Regístrate y Gana! 🎉
            </NuxtLink>
          </p>
        </div>

        <!-- Divider -->
        <div class="mt-6 relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-pink-400/30"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-transparent text-pink-300">O continúa con</span>
          </div>
        </div>

        <!-- Social Login (futuro) -->
        <div class="mt-6 grid grid-cols-2 gap-3">
          <button type="button" disabled
            class="w-full inline-flex justify-center py-2 px-4 border border-pink-400/30 rounded-lg shadow-sm bg-black/20 text-sm font-medium text-pink-300 hover:bg-black/30 transition opacity-50 cursor-not-allowed">
            <Icon name="bi:google" class="h-5 w-5 mr-2" />
            Google
          </button>
          <button type="button" disabled
            class="w-full inline-flex justify-center py-2 px-4 border border-pink-400/30 rounded-lg shadow-sm bg-black/20 text-sm font-medium text-pink-300 hover:bg-black/30 transition opacity-50 cursor-not-allowed">
            <Icon name="bi:facebook" class="h-5 w-5 mr-2" />
            Facebook
          </button>
        </div>
        <p class="mt-2 text-xs text-center text-pink-300/70">Próximamente disponible</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: 'guest'
})

const { login } = useAuth()
const router = useRouter()

const demoVisible = ref(import.meta.env.DEV ?? false)

const form = ref({
  login: '',
  password: '',
  remember: false
})

const showPassword = ref(false)
const isLoading = ref(false)

// Errores del formulario
const errors = ref<Record<string, string>>({})
// Error general
const loginError = ref('')

/**
 * Llena el formulario con credenciales de demo
 */
function fillForm(credentials: { login?: string; email?: string; password: string; remember?: boolean }) {
  form.value.login = credentials.login || credentials.email || ''
  form.value.password = credentials.password
  form.value.remember = credentials.remember ?? true
}

/**
 * Manejo de envío del login
 */
async function handleLogin() {
  errors.value = {}
  loginError.value = ''

  // Validación mínima del cliente
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
    await login({
      login: form.value.login,
      password: form.value.password,
      remember: form.value.remember
    })

    await router.push('/player/lobby')

  } catch (error: any) {
    /**
     * IMPORTANTE:
     * $fetch NO tiene error.response
     * error.data es donde vienen los datos del backend
     */
    const data = error?.data ?? {}

    // Manejar validaciones Laravel
    if (data?.errors) {
      Object.keys(data.errors).forEach(key => {
        errors.value[key] = data.errors[key][0]
      })
      loginError.value = data?.message ?? 'Por favor verifica los datos ingresados.'
      return
    }

    // Credenciales incorrectas (401 o 422 sin errors[] )
    if (error?.status === 401 || data?.message === 'Credenciales inválidas') {
      loginError.value = 'Credenciales incorrectas. Verifica tus datos.'
      return
    }

    // Otros errores
    loginError.value = data?.message || 'Error inesperado. Por favor intenta de nuevo.'
    console.error('[Player Login] Error:', error)

  } finally {
    isLoading.value = false
  }
}

useHead({
  title: 'Iniciar Sesión - PlayRKE',
  meta: [
    { name: 'description', content: 'Inicia sesión en PlayRKE y comienza a jugar bingo online' }
  ]
})
</script>