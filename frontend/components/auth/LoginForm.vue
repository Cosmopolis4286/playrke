<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-secondary-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="flex justify-center mb-6">
          <div class="w-16 h-16 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
          Iniciar Sesión
        </h2>
        <p class="text-sm text-gray-600">
          Accede a tu cuenta de PlayRKE
        </p>
      </div>

      <!-- Login Form -->
      <div class="glass-card p-8 space-y-6" v-if="!twoFactorChallenge && !requiresEmailVerification">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Correo Electrónico
            </label>
            <div class="relative">
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                :class="[
                  'input-field pl-10',
                  hasFieldError('email') ? 'border-red-500 focus:border-red-500' : ''
                ]"
                placeholder="tu@email.com"
                :disabled="isLoading"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
              </div>
            </div>
            <p v-if="getFieldError('email')" class="mt-1 text-sm text-red-600">
              {{ getFieldError('email') }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                :class="[
                  'input-field pl-10 pr-10',
                  hasFieldError('password') ? 'border-red-500 focus:border-red-500' : ''
                ]"
                placeholder="••••••••"
                :disabled="isLoading"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                :disabled="isLoading"
              >
                <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
            <p v-if="getFieldError('password')" class="mt-1 text-sm text-red-600">
              {{ getFieldError('password') }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                :disabled="isLoading"
              >
              <label for="remember" class="ml-2 block text-sm text-gray-700">
                Mantener sesión iniciada
              </label>
            </div>
            <router-link
              to="/forgot-password"
              class="text-sm text-primary-600 hover:text-primary-500 font-medium"
            >
              ¿Olvidaste tu contraseña?
            </router-link>
          </div>

          <!-- Submit Button -->
          <Button
            type="submit"
            :loading="isLoading"
            :disabled="isLoading"
            class="w-full"
            variant="primary"
            size="lg"
          >
            <template #icon>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
            </template>
            Iniciar Sesión
          </Button>

          <!-- General Error -->
          <div v-if="getFieldError('general')" class="text-center">
            <p class="text-sm text-red-600">
              {{ getFieldError('general') }}
            </p>
          </div>
        </form>

        <!-- Divider -->
        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">¿No tienes cuenta?</span>
          </div>
        </div>

        <!-- Register Link -->
        <div class="text-center">
          <router-link
            to="/register"
            class="btn-ghost w-full"
          >
            Crear cuenta nueva
          </router-link>
        </div>
      </div>

      <!-- 2FA Challenge -->
      <TwoFactorChallenge
        v-else-if="twoFactorChallenge"
        @verify="handle2FAVerification"
        @back="handleBackToLogin"
        :loading="isLoading"
        :error="getFieldError('code')"
      />

      <!-- Email Verification Required -->
      <EmailVerificationRequired
        v-else-if="requiresEmailVerification"
        :email="form.email"
        @resend="handleResendVerification"
        @back="handleBackToLogin"
        :loading="isLoading"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/auth/useAuth'
import { useAuthValidation } from '@/composables/auth/useValidation'
import Button from '@/components/ui/Button.vue'
import TwoFactorChallenge from './TwoFactorChallenge.vue'
import EmailVerificationRequired from './EmailVerificationRequired.vue'

// Auth composables
const { 
  login, 
  verify2FA, 
  resendEmailVerification,
  isLoading, 
  twoFactorChallenge, 
  requiresEmailVerification,
  getFieldError, 
  hasFieldError,
  clearAuthState,
  clearErrors
} = useAuth()

const { loginRules, useValidation } = useAuthValidation()
const { validateField, validateForm } = useValidation(loginRules)

// Form state
const form = ref({
  email: '',
  password: '',
  remember: false
})

const showPassword = ref(false)

// Handle login submission
const handleLogin = async () => {
  clearErrors()
  
  if (!validateForm(form.value)) {
    return
  }

  await login(form.value)
}

// Handle 2FA verification
const handle2FAVerification = async (code: string) => {
  await verify2FA(code)
}

// Handle resend email verification
const handleResendVerification = async () => {
  await resendEmailVerification()
}

// Handle back to login
const handleBackToLogin = () => {
  clearAuthState()
}

// Field validation on blur
const validateOnBlur = (field: string, value: any) => {
  validateField(field, value)
}

onMounted(() => {
  clearAuthState()
})
</script>

<style scoped>
.glass-card {
  @apply bg-white/80 backdrop-blur-lg shadow-xl rounded-2xl border border-white/20;
}

.input-field {
  @apply w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 
         focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500
         transition-colors duration-200;
}

.input-field:disabled {
  @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}

.btn-ghost {
  @apply inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium 
         rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 focus:outline-none focus:ring-2 
         focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200 disabled:opacity-50 
         disabled:cursor-not-allowed;
}
</style>