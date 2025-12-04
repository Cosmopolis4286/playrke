<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Demo Credentials Component -->
    <AuthDemoCredentials @fill-credentials="fillForm" />

    <div class="max-w-md w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-slate-800 rounded-2xl border border-slate-700 shadow-lg mb-4">
          <Icon name="lucide:shield-check" class="h-16 w-16 text-blue-500" />
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Panel Administrativo</h1>
        <p class="text-slate-400">PlayRKE - Gestión y Control</p>
      </div>

      <!-- Formulario de login -->
      <div class="bg-slate-800/50 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-slate-700">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email o Username -->
          <div>
            <label for="login" class="block text-sm font-medium text-slate-300 mb-2">
              Email o Usuario
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:user" class="h-5 w-5 text-slate-400" />
              </div>
              <input
                id="login"
                v-model="form.login"
                type="text"
                class="block w-full pl-10 pr-3 py-3 border border-slate-600 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Email o nombre de usuario"
                required
              >
            </div>
            <p v-if="errors.login" class="mt-2 text-sm text-red-400">{{ errors.login }}</p>
          </div>

          <!-- Contraseña -->
          <div>
            <label for="password" class="block text-sm font-medium text-slate-300 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Icon name="lucide:lock" class="h-5 w-5 text-slate-400" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="block w-full pl-10 pr-12 py-3 border border-slate-600 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
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
                  class="h-5 w-5 text-slate-400 hover:text-slate-300 transition"
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
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-600 rounded bg-slate-900"
              >
              <span class="ml-2 text-sm text-slate-300">Recordar sesión</span>
            </label>

            <NuxtLink
              to="/admin/auth/forgot-password"
              class="text-sm text-blue-400 hover:text-blue-300 transition"
            >
              ¿Olvidaste tu contraseña?
            </NuxtLink>
          </div>

          <!-- Error general -->
          <div v-if="loginError" class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
            <div class="flex items-start">
              <Icon name="lucide:alert-triangle" class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-300">{{ loginError }}</p>
            </div>
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

        <!-- Información de seguridad -->
        <div class="mt-6 p-4 bg-slate-700/50 rounded-lg border border-slate-600">
          <div class="flex items-start">
            <Icon name="lucide:info" class="h-5 w-5 text-slate-400 mr-2 flex-shrink-0 mt-0.5" />
            <div class="text-xs text-slate-400">
              <p class="font-medium mb-1">Acceso restringido</p>
              <p>Este panel es solo para personal autorizado. Todos los accesos son registrados.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center text-xs text-slate-500">
        <p>PlayRKE Admin Panel v1.0.0</p>
        <p class="mt-1">© 2025 Todos los derechos reservados</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de login para administradores (Admin Context)
 *
 * Diseño profesional con colores slate/gray
 * Sin registro público - solo para personal autorizado
 */

definePageMeta({
  layout: false,
  middleware: 'guest'
})

const { login } = useAuth()
const router = useRouter()

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

/**
 * Llena el formulario con credenciales de demo
 */
function fillForm(credentials: { login?: string; email?: string; password: string; remember?: boolean }) {
  // Acepta tanto 'login' como 'email' para compatibilidad
  form.value.login = credentials.login || credentials.email || ''
  form.value.password = credentials.password
  form.value.remember = credentials.remember ?? true
}

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

    // Redirigir al dashboard de administración
    await router.push('/admin')

  } catch (error: any) {
    // Manejar errores
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      loginError.value = 'Por favor verifica los datos ingresados.'
    } else if (error.response?.status === 401) {
      loginError.value = 'Credenciales incorrectas. Acceso denegado.'
    } else if (error.response?.status === 403) {
      loginError.value = 'No tienes permisos para acceder al panel administrativo.'
    } else {
      loginError.value = 'Error inesperado. Por favor intenta de nuevo.'
    }
    console.error('[Admin Login] Error:', error)
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Admin Login - PlayRKE',
  meta: [
    { name: 'description', content: 'Panel de administración de PlayRKE' },
    { name: 'robots', content: 'noindex, nofollow' } // No indexar páginas admin
  ]
})
</script>
