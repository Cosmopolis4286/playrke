<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-secondary-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="flex justify-center mb-6">
          <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
          </div>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
          ¿Olvidaste tu contraseña?
        </h2>
        <p class="text-sm text-gray-600">
          No te preocupes, te enviaremos instrucciones para restablecerla
        </p>
      </div>

      <!-- Success State -->
      <div v-if="emailSent" class="glass-card p-8 text-center space-y-6">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
          <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            ¡Revisa tu email!
          </h3>
          <p class="text-sm text-gray-600 mb-4">
            Te hemos enviado un enlace de restablecimiento a
            <strong class="text-gray-900">{{ form.email }}</strong>
          </p>
          <p class="text-xs text-gray-500">
            Si no ves el email en tu bandeja de entrada, revisa la carpeta de spam o correo no deseado.
          </p>
        </div>

        <div class="space-y-4">
          <Button
            @click="handleResend"
            variant="primary"
            :loading="isLoading"
            :disabled="resendCooldown > 0"
            class="w-full"
          >
            <template #icon>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </template>
            {{ resendCooldown > 0 ? `Reenviar en ${resendCooldown}s` : 'Reenviar email' }}
          </Button>

          <router-link to="/login" class="btn-ghost w-full inline-flex justify-center items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver al inicio de sesión
          </router-link>
        </div>
      </div>

      <!-- Form State -->
      <div v-else class="glass-card p-8 space-y-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
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
            <p class="mt-1 text-xs text-gray-500">
              Ingresa el email asociado a tu cuenta para recibir las instrucciones
            </p>
          </div>

          <!-- Submit Button -->
          <Button
            type="submit"
            :loading="isLoading"
            :disabled="isLoading || !form.email"
            class="w-full"
            variant="primary"
            size="lg"
          >
            <template #icon>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </template>
            Enviar instrucciones
          </Button>

          <!-- General Error -->
          <div v-if="getFieldError('general')" class="text-center">
            <p class="text-sm text-red-600">
              {{ getFieldError('general') }}
            </p>
          </div>
        </form>

        <!-- Back to Login -->
        <div class="text-center">
          <router-link
            to="/login"
            class="btn-ghost w-full"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver al inicio de sesión
          </router-link>
        </div>

        <!-- Help Section -->
        <div class="border-t border-gray-200 pt-6">
          <div class="text-center space-y-2">
            <p class="text-xs text-gray-500">¿Necesitas ayuda?</p>
            <div class="flex justify-center space-x-4 text-xs">
              <a href="/help" class="text-primary-600 hover:text-primary-500">Centro de Ayuda</a>
              <span class="text-gray-300">|</span>
              <a href="mailto:support@playrke.com" class="text-primary-600 hover:text-primary-500">Contactar Soporte</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/auth/useAuth'
import { useAuthValidation } from '@/composables/auth/useValidation'
import Button from '@/components/ui/Button.vue'

// Auth composables
const { 
  forgotPassword, 
  isLoading, 
  getFieldError, 
  hasFieldError,
  clearErrors 
} = useAuth()

const { forgotPasswordRules, useValidation } = useAuthValidation()
const { validateForm } = useValidation(forgotPasswordRules)

// Form state
const form = ref({
  email: ''
})

const emailSent = ref(false)
const resendCooldown = ref(0)
let resendTimer: NodeJS.Timeout | null = null

// Handle form submission
const handleSubmit = async () => {
  clearErrors()
  
  if (!validateForm(form.value)) {
    return
  }

  const success = await forgotPassword(form.value.email)
  
  if (success) {
    emailSent.value = true
    startResendCooldown()
  }
}

// Handle resend
const handleResend = async () => {
  if (resendCooldown.value > 0 || isLoading.value) {
    return
  }

  const success = await forgotPassword(form.value.email)
  
  if (success) {
    startResendCooldown()
  }
}

// Start resend cooldown timer
const startResendCooldown = () => {
  resendCooldown.value = 60 // 60 seconds cooldown
  
  resendTimer = setInterval(() => {
    resendCooldown.value--
    
    if (resendCooldown.value <= 0) {
      if (resendTimer) {
        clearInterval(resendTimer)
        resendTimer = null
      }
    }
  }, 1000)
}

// Cleanup timer on unmount
onUnmounted(() => {
  if (resendTimer) {
    clearInterval(resendTimer)
    resendTimer = null
  }
})

onMounted(() => {
  clearErrors()
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
         rounded-lg text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 
         focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 disabled:opacity-50 
         disabled:cursor-not-allowed;
}
</style>