<template>
  <div class="glass-card p-8 space-y-6">
    <!-- Header -->
    <div class="text-center">
      <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <h3 class="text-2xl font-bold text-gray-900 mb-2">
        Autenticación de Dos Factores
      </h3>
      <p class="text-sm text-gray-600">
        Ingresa el código de 6 dígitos desde tu aplicación de autenticación
      </p>
    </div>

    <!-- 2FA Form -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Code Input -->
      <div>
        <label for="code" class="block text-sm font-medium text-gray-700 mb-3 text-center">
          Código de Verificación
        </label>
        
        <!-- Code Digits Input -->
        <div class="flex justify-center space-x-2 mb-4">
          <input
            v-for="(digit, index) in codeDigits"
            :key="index"
            :ref="el => digitRefs[index] = el"
            v-model="codeDigits[index]"
            type="text"
            inputmode="numeric"
            maxlength="1"
            :class="[
              'w-12 h-14 text-center text-xl font-bold border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors',
              error ? 'border-red-500' : 'border-gray-300 focus:border-primary-500'
            ]"
            @input="handleDigitInput(index, $event)"
            @keydown="handleKeyDown(index, $event)"
            @paste="handlePaste"
            :disabled="loading"
          />
        </div>

        <!-- Error Message -->
        <div v-if="error" class="text-center">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Code Display (hidden input for form submission) -->
        <input
          v-model="code"
          type="hidden"
          name="code"
        />
      </div>

      <!-- Submit Button -->
      <Button
        type="submit"
        :loading="loading"
        :disabled="loading || !isCodeComplete"
        class="w-full"
        variant="primary"
        size="lg"
      >
        <template #icon>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </template>
        Verificar Código
      </Button>

      <!-- Back to Login -->
      <button
        type="button"
        @click="$emit('back')"
        class="w-full btn-ghost"
        :disabled="loading"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver al inicio de sesión
      </button>
    </form>

    <!-- Help Text -->
    <div class="text-center space-y-2">
      <p class="text-xs text-gray-500">
        ¿No puedes acceder a tu aplicación de autenticación?
      </p>
      <button
        type="button"
        class="text-xs text-primary-600 hover:text-primary-500 font-medium"
        @click="showRecoveryOptions = true"
      >
        Usar código de recuperación
      </button>
    </div>

    <!-- Recovery Codes Modal -->
    <RecoveryCodesModal 
      v-if="showRecoveryOptions"
      @close="showRecoveryOptions = false"
      @verify="handleRecoveryVerification"
      :loading="loading"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'
import Button from '@/components/ui/Button.vue'
import RecoveryCodesModal from './RecoveryCodesModal.vue'

interface Props {
  loading?: boolean
  error?: string
}

interface Emits {
  (e: 'verify', code: string): void
  (e: 'back'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: ''
})

const emit = defineEmits<Emits>()

// State
const codeDigits = ref<string[]>(new Array(6).fill(''))
const digitRefs = ref<(HTMLInputElement | null)[]>([])
const showRecoveryOptions = ref(false)

// Computed
const code = computed(() => codeDigits.value.join(''))
const isCodeComplete = computed(() => code.value.length === 6 && /^\d{6}$/.test(code.value))

// Methods
const handleDigitInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement
  const value = input.value.replace(/[^0-9]/g, '') // Only allow numbers
  
  if (value.length > 1) {
    // Handle multiple digits (paste)
    const digits = value.slice(0, 6).split('')
    digits.forEach((digit, i) => {
      if (index + i < 6) {
        codeDigits.value[index + i] = digit
      }
    })
    
    // Focus next empty field or last field
    const nextIndex = Math.min(index + digits.length, 5)
    focusDigit(nextIndex)
  } else {
    codeDigits.value[index] = value
    
    // Auto-focus next field
    if (value && index < 5) {
      focusDigit(index + 1)
    }
  }
}

const handleKeyDown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !codeDigits.value[index] && index > 0) {
    // Focus previous field on backspace if current is empty
    focusDigit(index - 1)
  } else if (event.key === 'ArrowLeft' && index > 0) {
    focusDigit(index - 1)
  } else if (event.key === 'ArrowRight' && index < 5) {
    focusDigit(index + 1)
  }
}

const handlePaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const pasteData = event.clipboardData?.getData('text')
  
  if (pasteData) {
    const digits = pasteData.replace(/[^0-9]/g, '').slice(0, 6).split('')
    digits.forEach((digit, index) => {
      if (index < 6) {
        codeDigits.value[index] = digit
      }
    })
    
    // Focus last filled digit
    focusDigit(Math.min(digits.length - 1, 5))
  }
}

const focusDigit = (index: number) => {
  nextTick(() => {
    const input = digitRefs.value[index]
    if (input) {
      input.focus()
      input.select()
    }
  })
}

const handleSubmit = () => {
  if (isCodeComplete.value) {
    emit('verify', code.value)
  }
}

const handleRecoveryVerification = (recoveryCode: string) => {
  emit('verify', recoveryCode)
  showRecoveryOptions.value = false
}

// Auto-submit when code is complete
const autoSubmitTimer = ref<NodeJS.Timeout>()

const checkAutoSubmit = () => {
  if (autoSubmitTimer.value) {
    clearTimeout(autoSubmitTimer.value)
  }
  
  if (isCodeComplete.value) {
    autoSubmitTimer.value = setTimeout(() => {
      handleSubmit()
    }, 500) // Small delay for better UX
  }
}

// Watch for code completion
import { watch } from 'vue'
watch(code, checkAutoSubmit)

// Focus first input on mount
onMounted(() => {
  focusDigit(0)
})

// Clear timer on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (autoSubmitTimer.value) {
    clearTimeout(autoSubmitTimer.value)
  }
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

/* Input focus animation */
input[type="text"] {
  transition: all 0.2s ease-in-out;
}

input[type="text"]:focus {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Number input styling */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="text"] {
  -moz-appearance: textfield;
}
</style>