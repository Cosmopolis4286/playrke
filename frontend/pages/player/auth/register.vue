<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 py-12">
    <div class="max-w-2xl w-full mx-4">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 mb-4">
          <Icon name="lucide:sparkles" class="h-16 w-16 text-yellow-400" />
        </div>
        <h1 class="text-4xl font-bold text-white mb-2 font-gaming">¡Únete a PlayRKE!</h1>
        <p class="text-pink-200 text-lg">Crea tu cuenta y empieza a ganar</p>
      </div>

      <!-- Formulario de registro -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Grid de 2 columnas -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
              <label for="first_name" class="block text-sm font-medium text-pink-200 mb-2">
                Nombre <span class="text-yellow-400">*</span>
              </label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                minlength="2"
                maxlength="100"
                class="block w-full px-4 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                :class="{ 'border-red-400': errors.first_name }"
                placeholder="Tu nombre"
                required
              >
              <p v-if="errors.first_name" class="mt-2 text-sm text-red-300">{{ getErrorMessage('first_name') }}</p>
            </div>

            <!-- Apellido -->
            <div>
              <label for="last_name" class="block text-sm font-medium text-pink-200 mb-2">
                Apellido <span class="text-yellow-400">*</span>
              </label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                minlength="2"
                maxlength="100"
                class="block w-full px-4 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                :class="{ 'border-red-400': errors.last_name }"
                placeholder="Tu apellido"
                required
              >
              <p v-if="errors.last_name" class="mt-2 text-sm text-red-300">{{ getErrorMessage('last_name') }}</p>
            </div>

            <!-- Username -->
            <div>
              <label for="username" class="block text-sm font-medium text-pink-200 mb-2">
                Nombre de usuario <span class="text-yellow-400">*</span>
              </label>
              <input
                id="username"
                v-model="form.username"
                type="text"
                minlength="3"
                maxlength="50"
                pattern="[a-zA-Z0-9_]+"
                class="block w-full px-4 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                :class="{ 'border-red-400': errors.username }"
                placeholder="usuario_123"
                required
                @blur="validateUsername"
              >
              <p v-if="errors.username" class="mt-2 text-sm text-red-300">{{ getErrorMessage('username') }}</p>
              <p v-else class="mt-1 text-xs text-pink-300/70">Solo letras, números y guión bajo (_)</p>
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-pink-200 mb-2">
                Email <span class="text-yellow-400">*</span>
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                maxlength="255"
                class="block w-full px-4 py-3 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                :class="{ 'border-red-400': errors.email }"
                placeholder="tu@email.com"
                required
              >
              <p v-if="errors.email" class="mt-2 text-sm text-red-300">{{ getErrorMessage('email') }}</p>
            </div>

            <!-- Contraseña -->
            <div>
              <label for="password" class="block text-sm font-medium text-pink-200 mb-2">
                Contraseña <span class="text-yellow-400">*</span>
              </label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="block w-full px-4 py-3 pr-12 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                  :class="{ 'border-red-400': errors.password }"
                  placeholder="Mínimo 8 caracteres"
                  required
                  minlength="8"
                  @input="validatePassword"
                >
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  @click="showPassword = !showPassword"
                >
                  <Icon
                    :name="showPassword ? 'lucide:eye-off' : 'lucide:eye'"
                    class="h-5 w-5 text-pink-300 hover:text-pink-200 transition"
                  />
                </button>
              </div>
              <p v-if="errors.password" class="mt-2 text-sm text-red-300">{{ getErrorMessage('password') }}</p>
              <p v-else class="mt-1 text-xs text-pink-300/70">Mínimo 8 caracteres con letras y números</p>
            </div>

            <!-- Confirmar contraseña -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-pink-200 mb-2">
                Confirmar contraseña <span class="text-yellow-400">*</span>
              </label>
              <div class="relative">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirm ? 'text' : 'password'"
                  class="block w-full px-4 py-3 pr-12 border border-pink-400/30 rounded-lg bg-black/30 text-white placeholder-pink-300/50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                  :class="{ 'border-red-400': errors.password_confirmation }"
                  placeholder="Confirma tu contraseña"
                  required
                  minlength="8"
                >
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  @click="showPasswordConfirm = !showPasswordConfirm"
                >
                  <Icon
                    :name="showPasswordConfirm ? 'lucide:eye-off' : 'lucide:eye'"
                    class="h-5 w-5 text-pink-300 hover:text-pink-200 transition"
                  />
                </button>
              </div>
              <p v-if="errors.password_confirmation" class="mt-2 text-sm text-red-300">{{ getErrorMessage('password_confirmation') }}</p>
            </div>
          </div>

          <!-- Términos y condiciones -->
          <div>
            <label class="flex items-start">
              <input
                v-model="form.terms"
                type="checkbox"
                class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-pink-400/30 rounded bg-black/30 mt-0.5"
                required
              >
              <span class="ml-2 text-sm text-pink-200">
                Acepto los
                <a href="#" class="text-yellow-400 hover:text-yellow-300 font-medium">Términos y Condiciones</a>
                y la
                <a href="#" class="text-yellow-400 hover:text-yellow-300 font-medium">Política de Privacidad</a>
                <span class="text-yellow-400">*</span>
              </span>
            </label>
            <p v-if="errors.terms" class="mt-2 text-sm text-red-300">{{ getErrorMessage('terms') }}</p>
          </div>

          <!-- Edad mínima -->
          <div>
            <label class="flex items-start">
              <input
                v-model="form.age_confirm"
                type="checkbox"
                class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-pink-400/30 rounded bg-black/30 mt-0.5"
                required
              >
              <span class="ml-2 text-sm text-pink-200">
                Confirmo que soy mayor de 18 años
                <span class="text-yellow-400">*</span>
              </span>
            </label>
            <p v-if="errors.age_confirm" class="mt-2 text-sm text-red-300">{{ getErrorMessage('age_confirm') }}</p>
          </div>

          <!-- Error general -->
          <div v-if="registerError" class="p-4 bg-red-500/20 border border-red-400/50 rounded-lg backdrop-blur-sm">
            <div class="flex items-start">
              <Icon name="lucide:alert-circle" class="h-5 w-5 text-red-300 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-200">{{ registerError }}</p>
            </div>
          </div>

          <!-- Botón de registro -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed transition transform hover:scale-105"
          >
            <Icon v-if="isLoading" name="lucide:loader-2" class="animate-spin h-5 w-5 mr-2" />
            <span>{{ isLoading ? 'Creando cuenta...' : '🎉 Crear Cuenta' }}</span>
          </button>
        </form>

        <!-- Login -->
        <div class="mt-6 text-center">
          <p class="text-sm text-pink-200">
            ¿Ya tienes una cuenta?
            <NuxtLink
              to="/player/auth/login"
              class="text-yellow-400 hover:text-yellow-300 font-bold transition ml-1"
            >
              Inicia Sesión
            </NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de registro para jugadores (Player Context)
 *
 * Diseño gaming con gradientes purple-pink-red
 * Registro público con validaciones y términos
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
  terms: false,
  age_confirm: false
})

// Estado de UI
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const isLoading = ref(false)
const errors = ref<Record<string, string | string[]>>({})
const registerError = ref('')

/**
 * Valida el nombre de usuario en tiempo real
 */
function validateUsername() {
  const username = form.value.username
  const usernameRegex = /^[a-zA-Z0-9_]+$/

  if (username.length < 3) {
    errors.value.username = 'El nombre de usuario debe tener al menos 3 caracteres'
    return false
  }

  if (!usernameRegex.test(username)) {
    errors.value.username = 'Solo puedes usar letras, números y guión bajo (_)'
    return false
  }

  delete errors.value.username
  return true
}

/**
 * Valida la contraseña
 */
function validatePassword() {
  const password = form.value.password

  if (password.length < 8) {
    errors.value.password = 'La contraseña debe tener al menos 8 caracteres'
    return false
  }

  // Verificar que tenga al menos una letra y un número
  const hasLetter = /[a-zA-Z]/.test(password)
  const hasNumber = /[0-9]/.test(password)

  if (!hasLetter || !hasNumber) {
    errors.value.password = 'La contraseña debe contener letras y números'
    return false
  }

  delete errors.value.password
  return true
}

/**
 * Obtiene el mensaje de error formateado
 */
function getErrorMessage(field: string): string {
  const error = errors.value[field]
  if (Array.isArray(error)) {
    return error[0]
  }
  return error || ''
}

/**
 * Maneja el envío del formulario de registro
 */
async function handleRegister() {
  // Limpiar errores previos
  errors.value = {}
  registerError.value = ''

  // Validación de términos
  if (!form.value.terms) {
    errors.value.terms = 'Debes aceptar los términos y condiciones'
    return
  }

  if (!form.value.age_confirm) {
    errors.value.age_confirm = 'Debes confirmar que eres mayor de 18 años'
    return
  }

  // Validación de contraseñas
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Las contraseñas no coinciden'
    return
  }

  // Validar username
  if (!validateUsername()) {
    return
  }

  // Validar password
  if (!validatePassword()) {
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

    // Redirigir al lobby
    await router.push('/player/lobby')

  } catch (error: any) {
    console.error('[Player Register] Error:', error)

    // Manejar errores
    if (error.response?.status === 422 || error.statusCode === 422) {
      // Errores de validación del backend
      const backendErrors = error.response?.data?.errors || error.data?.errors || {}

      // Convertir errores del backend al formato esperado
      Object.keys(backendErrors).forEach(key => {
        errors.value[key] = backendErrors[key]
      })

      registerError.value = error.response?.data?.message || 'Por favor verifica los datos ingresados.'
    } else if (error.response?.status === 500 || error.statusCode === 500) {
      registerError.value = 'Error del servidor. Por favor intenta de nuevo más tarde.'
    } else {
      registerError.value = error.response?.data?.message || 'Error al crear la cuenta. Por favor intenta de nuevo.'
    }
  } finally {
    isLoading.value = false
  }
}

// SEO
useHead({
  title: 'Registro - PlayRKE',
  meta: [
    { name: 'description', content: 'Regístrate en PlayRKE y comienza a jugar bingo online gratis' }
  ]
})
</script>
