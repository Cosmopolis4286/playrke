<template>
  <div class="glass-card p-8 text-center space-y-6">
    <!-- Icon -->
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100">
      <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    </div>

    <!-- Header -->
    <div>
      <h3 class="text-2xl font-bold text-gray-900 mb-2">
        Verifica tu email
      </h3>
      <p class="text-sm text-gray-600 mb-4">
        Te hemos enviado un enlace de verificación a
        <strong class="text-gray-900">{{ email }}</strong>
      </p>
      <p class="text-xs text-gray-500">
        Haz clic en el enlace del email para activar tu cuenta
      </p>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 rounded-lg p-4 text-left space-y-2">
      <h4 class="text-sm font-medium text-blue-900">¿No encuentras el email?</h4>
      <ul class="text-xs text-blue-800 space-y-1">
        <li class="flex items-start">
          <span class="w-1 h-1 bg-blue-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
          Revisa tu carpeta de spam o correo no deseado
        </li>
        <li class="flex items-start">
          <span class="w-1 h-1 bg-blue-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
          Asegúrate de que el email esté escrito correctamente
        </li>
        <li class="flex items-start">
          <span class="w-1 h-1 bg-blue-400 rounded-full mt-1.5 mr-2 flex-shrink-0"></span>
          Espera unos minutos, a veces puede tardar en llegar
        </li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="space-y-4">
      <!-- Resend Button -->
      <Button
        @click="handleResend"
        variant="primary"
        :loading="loading"
        :disabled="resendCooldown > 0 || loading"
        class="w-full"
      >
        <template #icon>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </template>
        {{ resendCooldown > 0 ? `Reenviar en ${resendCooldown}s` : 'Reenviar email de verificación' }}
      </Button>

      <!-- Success message after resend -->
      <div v-if="resendSuccess" class="text-sm text-green-600 bg-green-50 rounded-lg p-3">
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Email reenviado correctamente
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-lg p-3">
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ error }}
        </div>
      </div>

      <!-- Change Email -->
      <button
        @click="showChangeEmail = !showChangeEmail"
        class="text-sm text-gray-600 hover:text-gray-800 transition-colors"
        :disabled="loading"
      >
        ¿Necesitas cambiar el email?
      </button>

      <!-- Change Email Form -->
      <div v-if="showChangeEmail" class="space-y-4 border-t border-gray-200 pt-4">
        <div class="text-left">
          <label for="newEmail" class="block text-xs font-medium text-gray-700 mb-1">
            Nuevo Email
          </label>
          <input
            id="newEmail"
            v-model="newEmail"
            type="email"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="nuevo@email.com"
            :disabled="loading"
          >
        </div>
        <div class="flex space-x-2">
          <Button
            @click="handleChangeEmail"
            variant="primary"
            size="sm"
            :loading="loading"
            :disabled="!newEmail || loading"
            class="flex-1"
          >
            Cambiar Email
          </Button>
          <Button
            @click="showChangeEmail = false"
            variant="ghost"
            size="sm"
            :disabled="loading"
            class="flex-1"
          >
            Cancelar
          </Button>
        </div>
      </div>

      <!-- Back to Login -->
      <button
        @click="$emit('back')"
        class="btn-ghost w-full"
        :disabled="loading"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver al inicio de sesión
      </button>
    </div>

    <!-- Help Section -->
    <div class="border-t border-gray-200 pt-4">
      <p class="text-xs text-gray-500 mb-2">¿Sigues teniendo problemas?</p>
      <div class="flex justify-center space-x-4 text-xs">
        <a href="/help" class="text-primary-600 hover:text-primary-500">Centro de Ayuda</a>
        <span class="text-gray-300">|</span>
        <a href="mailto:support@playrke.com" class="text-primary-600 hover:text-primary-500">Contactar Soporte</a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import Button from '@/components/ui/Button.vue'

interface Props {
  email: string
  loading?: boolean
}

interface Emits {
  (e: 'resend'): void
  (e: 'changeEmail', newEmail: string): void
  (e: 'back'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()

// Component state
const resendCooldown = ref(0)
const resendSuccess = ref(false)
const error = ref('')
const showChangeEmail = ref(false)
const newEmail = ref('')

let resendTimer: NodeJS.Timeout | null = null
let successTimer: NodeJS.Timeout | null = null

// Handle resend email
const handleResend = () => {
  if (resendCooldown.value > 0 || props.loading) {
    return
  }

  error.value = ''
  resendSuccess.value = false
  
  emit('resend')
  
  // Start cooldown and show success message
  startResendCooldown()
  showResendSuccess()
}

// Handle change email
const handleChangeEmail = () => {
  if (!newEmail.value || props.loading) {
    return
  }

  error.value = ''
  emit('changeEmail', newEmail.value)
  
  // Reset form
  showChangeEmail.value = false
  newEmail.value = ''
}

// Start resend cooldown
const startResendCooldown = () => {
  resendCooldown.value = 60 // 60 seconds

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

// Show success message temporarily
const showResendSuccess = () => {
  resendSuccess.value = true
  
  successTimer = setTimeout(() => {
    resendSuccess.value = false
  }, 5000)
}

// Set error message
const setError = (message: string) => {
  error.value = message
  resendSuccess.value = false
}

// Cleanup timers
onUnmounted(() => {
  if (resendTimer) {
    clearInterval(resendTimer)
    resendTimer = null
  }
  
  if (successTimer) {
    clearTimeout(successTimer)
    successTimer = null
  }
})

// Expose methods for parent component
defineExpose({
  setError,
  startResendCooldown,
  showResendSuccess
})
</script>

<style scoped>
.glass-card {
  @apply bg-white/80 backdrop-blur-lg shadow-xl rounded-2xl border border-white/20;
}

.btn-ghost {
  @apply inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium 
         rounded-lg text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 
         focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 disabled:opacity-50 
         disabled:cursor-not-allowed;
}
</style>