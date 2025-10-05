<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 via-purple-900 to-pink-900 py-12">
    <div class="max-w-2xl w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">PlayRKE</h1>
        <p class="text-gray-300">Crea tu cuenta y empieza a ganar</p>
      </div>

      <!-- Formulario de registro -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Grid de 2 columnas -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
              <label for="first_name" class="block text-sm font-medium text-gray-200 mb-2">
                Nombre <span class="text-red-400">*</span>
              </label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Tu nombre"
                required
              >
              <p v-if="errors.first_name" class="mt-2 text-sm text-red-400">{{ errors.first_name }}</p>
            </div>

            <!-- Apellido -->
            <div>
              <label for="last_name" class="block text-sm font-medium text-gray-200 mb-2">
                Apellido <span class="text-red-400">*</span>
              </label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Tu apellido"
                required
              >
              <p v-if="errors.last_name" class="mt-2 text-sm text-red-400">{{ errors.last_name }}</p>
            </div>

            <!-- Username -->
            <div>
              <label for="username" class="block text-sm font-medium text-gray-200 mb-2">
                Nombre de usuario <span class="text-red-400">*</span>
              </label>
              <input
                id="username"
                v-model="form.username"
                type="text"
                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="Usuario único"
                required
              >
              <p v-if="errors.username" class="mt-2 text-sm text-red-400">{{ errors.username }}</p>
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-200 mb-2">
                Email <span class="text-red-400">*</span>
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="tu@email.com"
                required
              >
              <p v-if="errors.email" class="mt-2 text-sm text-red-400">{{ errors.email }}</p>
            </div>

            <!-- Contraseña -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-200 mb-2">
                Contraseña <span class="text-red-400">*</span>
              </label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="block w-full px-4 py-3 pr-12 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                  placeholder="Mínimo 8 caracteres"
                  required
                  minlength="8"
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

            <!-- Confirmar contraseña -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-200 mb-2">
                Confirmar contraseña <span class="text-red-400">*</span>
              </label>
              <div class="relative">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirm ? 'text' : 'password'"
                  class="block w-full px-4 py-3 pr-12 border border-gray-600 rounded-lg bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                  placeholder="Repite tu contraseña"
                  required
                >
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  @click="showPasswordConfirm = !showPasswordConfirm"
                >
                  <Icon
                    :name="showPasswordConfirm ? 'lucide:eye-off' : 'lucide:eye'"
                    class="h-5 w-5 text-gray-400 hover:text-gray-300 transition"
                  />
                </button>
              </div>
              <p v-if="errors.password_confirmation" class="mt-2 text-sm text-red-400">{{ errors.password_confirmation }}</p>
            </div>
          </div>

          <!-- Términos y condiciones -->
          <div>
            <label class="flex items-start">
              <input
                v-model="form.terms"
                type="checkbox"
                class="h-4 w-4 mt-1 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-800"
                required
              >
              <span class="ml-2 text-sm text-gray-300">
                Acepto los
                <NuxtLink to="/terms" target="_blank" class="text-blue-400 hover:text-blue-300 transition">
                  términos y condiciones
                </NuxtLink>
                y la
                <NuxtLink to="/privacy" target="_blank" class="text-blue-400 hover:text-blue-300 transition">
                  política de privacidad
                </NuxtLink>
              </span>
            </label>
          </div>

          <!-- Error general -->
          <div v-if="registerError" class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
            <p class="text-sm text-red-400">{{ registerError }}</p>
          </div>

          <!-- Botón de registro -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Creando cuenta...' : 'Crear Cuenta' }}</span>
          </button>
        </form>

        <!-- Login -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-300">
            ¿Ya tienes una cuenta?
            <NuxtLink to="/auth/login" class="text-blue-400 hover:text-blue-300 font-medium transition">
              Inicia sesión
            </NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de registro de PlayRKE
 *
 * Permite a nuevos usuarios crear una cuenta en el sistema.
 * Integrado con Laravel Sanctum para registro y autenticación automática.
 */

definePageMeta({
  layout: false,
  middleware: 'guest'
})

const { register } = useAuth()
const router = useRouter()

// Estado del formulario
const form = ref({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false
})

// Estado de UI
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const isLoading = ref(false)
const errors = ref<Record<string, string>>({})
const registerError = ref('')

/**
 * Maneja el envío del formulario de registro
 */
async function handleRegister() {
  // Limpiar errores previos
  errors.value = {}
  registerError.value = ''

  // Validación de contraseñas
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Las contraseñas no coinciden'
    return
  }

  if (!form.value.terms) {
    registerError.value = 'Debes aceptar los términos y condiciones'
    return
  }

  isLoading.value = true

  try {
    // Intentar registro
    await register({
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      username: form.value.username,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation
    })

    // El composable useAuth manejará la redirección

  } catch (error: any) {
    // Manejar errores
    if (error.response?.status === 422) {
      // Errores de validación
      const responseErrors = error.response.data.errors || {}
      errors.value = responseErrors

      // Mensaje general si hay errores
      if (Object.keys(responseErrors).length > 0) {
        registerError.value = 'Por favor corrige los errores del formulario'
      }
    } else {
      registerError.value = 'Ha ocurrido un error inesperado. Por favor intenta de nuevo.'
    }
    console.error('Error en registro:', error)
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Registro - PlayRKE',
  meta: [
    { name: 'description', content: 'Crea tu cuenta en PlayRKE y empieza a ganar' }
  ]
})
</script>
