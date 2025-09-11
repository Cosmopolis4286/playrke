<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-secondary-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="flex justify-center mb-6">
          <div class="w-16 h-16 bg-gradient-to-r from-secondary-500 to-primary-500 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
          Crear Cuenta
        </h2>
        <p class="text-sm text-gray-600">
          Únete a PlayRKE y comienza a disfrutar
        </p>
      </div>

      <!-- Registration Form -->
      <div class="glass-card p-8 space-y-6">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Progress Steps -->
          <div class="flex items-center justify-center mb-8">
            <div class="flex items-center space-x-4">
              <div :class="['flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium', 
                           currentStep >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600']">
                1
              </div>
              <div :class="['w-16 h-1 rounded-full', currentStep >= 2 ? 'bg-primary-600' : 'bg-gray-200']"></div>
              <div :class="['flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium',
                           currentStep >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600']">
                2
              </div>
              <div :class="['w-16 h-1 rounded-full', currentStep >= 3 ? 'bg-primary-600' : 'bg-gray-200']"></div>
              <div :class="['flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium',
                           currentStep >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600']">
                3
              </div>
            </div>
          </div>

          <!-- Step 1: Personal Information -->
          <div v-if="currentStep === 1" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 text-center mb-6">Información Personal</h3>
            
            <!-- Username -->
            <div>
              <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                Nombre de Usuario *
              </label>
              <div class="relative">
                <input
                  id="username"
                  v-model="form.username"
                  type="text"
                  autocomplete="username"
                  required
                  :class="[
                    'input-field pl-10',
                    hasFieldError('username') ? 'border-red-500 focus:border-red-500' : ''
                  ]"
                  placeholder="usuario123"
                  :disabled="isLoading"
                  @blur="validateField('username', form.username)"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </div>
              <p v-if="getFieldError('username')" class="mt-1 text-sm text-red-600">
                {{ getFieldError('username') }}
              </p>
            </div>

            <!-- Name Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                  Nombre *
                </label>
                <input
                  id="firstName"
                  v-model="form.first_name"
                  type="text"
                  autocomplete="given-name"
                  required
                  :class="[
                    'input-field',
                    hasFieldError('first_name') ? 'border-red-500 focus:border-red-500' : ''
                  ]"
                  placeholder="Juan"
                  :disabled="isLoading"
                  @blur="validateField('first_name', form.first_name)"
                >
                <p v-if="getFieldError('first_name')" class="mt-1 text-sm text-red-600">
                  {{ getFieldError('first_name') }}
                </p>
              </div>

              <div>
                <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                  Apellido *
                </label>
                <input
                  id="lastName"
                  v-model="form.last_name"
                  type="text"
                  autocomplete="family-name"
                  required
                  :class="[
                    'input-field',
                    hasFieldError('last_name') ? 'border-red-500 focus:border-red-500' : ''
                  ]"
                  placeholder="Pérez"
                  :disabled="isLoading"
                  @blur="validateField('last_name', form.last_name)"
                >
                <p v-if="getFieldError('last_name')" class="mt-1 text-sm text-red-600">
                  {{ getFieldError('last_name') }}
                </p>
              </div>
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Correo Electrónico *
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
                  placeholder="juan@email.com"
                  :disabled="isLoading"
                  @blur="validateField('email', form.email)"
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
          </div>

          <!-- Step 2: Security & Location -->
          <div v-else-if="currentStep === 2" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 text-center mb-6">Seguridad y Ubicación</h3>

            <!-- Password Fields -->
            <div class="grid grid-cols-1 gap-6">
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                  Contraseña *
                </label>
                <div class="relative">
                  <input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    :class="[
                      'input-field pl-10 pr-10',
                      hasFieldError('password') ? 'border-red-500 focus:border-red-500' : ''
                    ]"
                    placeholder="••••••••"
                    :disabled="isLoading"
                    @blur="validateField('password', form.password)"
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
                
                <!-- Password Strength Indicator -->
                <div v-if="form.password" class="mt-2">
                  <div class="flex items-center space-x-2">
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                      <div 
                        :class="[
                          'h-2 rounded-full transition-all duration-300',
                          passwordStrength.color
                        ]" 
                        :style="{ width: passwordStrength.width }"
                      ></div>
                    </div>
                    <span :class="['text-xs font-medium', passwordStrength.textColor]">
                      {{ passwordStrength.label }}
                    </span>
                  </div>
                </div>
              </div>

              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                  Confirmar Contraseña *
                </label>
                <div class="relative">
                  <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    :class="[
                      'input-field pl-10 pr-10',
                      hasFieldError('password_confirmation') ? 'border-red-500 focus:border-red-500' : ''
                    ]"
                    placeholder="••••••••"
                    :disabled="isLoading"
                    @blur="validateField('password_confirmation', form.password_confirmation)"
                  >
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <button
                    type="button"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    :disabled="isLoading"
                  >
                    <svg v-if="showPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
                <p v-if="getFieldError('password_confirmation')" class="mt-1 text-sm text-red-600">
                  {{ getFieldError('password_confirmation') }}
                </p>
              </div>
            </div>

            <!-- Country Selection -->
            <div>
              <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                País *
              </label>
              <select
                id="country"
                v-model="form.country_id"
                required
                :class="[
                  'input-field',
                  hasFieldError('country_id') ? 'border-red-500 focus:border-red-500' : ''
                ]"
                :disabled="isLoading"
                @blur="validateField('country_id', form.country_id)"
              >
                <option value="">Selecciona tu país</option>
                <option v-for="country in countries" :key="country.id" :value="country.id">
                  {{ country.name }}
                </option>
              </select>
              <p v-if="getFieldError('country_id')" class="mt-1 text-sm text-red-600">
                {{ getFieldError('country_id') }}
              </p>
            </div>

            <!-- Phone Number (Optional) -->
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                Teléfono (Opcional)
              </label>
              <input
                id="phone"
                v-model="form.phone_number"
                type="tel"
                autocomplete="tel"
                :class="[
                  'input-field',
                  hasFieldError('phone_number') ? 'border-red-500 focus:border-red-500' : ''
                ]"
                placeholder="+1234567890"
                :disabled="isLoading"
                @blur="validateField('phone_number', form.phone_number)"
              >
              <p v-if="getFieldError('phone_number')" class="mt-1 text-sm text-red-600">
                {{ getFieldError('phone_number') }}
              </p>
            </div>
          </div>

          <!-- Step 3: Final Details -->
          <div v-else-if="currentStep === 3" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 text-center mb-6">Detalles Finales</h3>

            <!-- Referral Code (Optional) -->
            <div>
              <label for="referral" class="block text-sm font-medium text-gray-700 mb-2">
                Código de Referido (Opcional)
              </label>
              <div class="relative">
                <input
                  id="referral"
                  v-model="form.referral_code"
                  type="text"
                  :class="[
                    'input-field pl-10',
                    hasFieldError('referral_code') ? 'border-red-500 focus:border-red-500' : ''
                  ]"
                  placeholder="ABC123XYZ"
                  :disabled="isLoading"
                  @blur="validateField('referral_code', form.referral_code)"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                  </svg>
                </div>
              </div>
              <p v-if="getFieldError('referral_code')" class="mt-1 text-sm text-red-600">
                {{ getFieldError('referral_code') }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Si tienes un código de referido, ingrésalo para obtener beneficios adicionales
              </p>
            </div>

            <!-- Terms and Conditions -->
            <div class="space-y-4">
              <div class="flex items-start">
                <div class="flex items-center h-5">
                  <input
                    id="terms"
                    v-model="form.terms"
                    type="checkbox"
                    :class="[
                      'h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded',
                      hasFieldError('terms') ? 'border-red-500' : ''
                    ]"
                    :disabled="isLoading"
                  >
                </div>
                <div class="ml-3 text-sm">
                  <label for="terms" class="font-medium text-gray-700">
                    Acepto los 
                    <a href="/terms" target="_blank" class="text-primary-600 hover:text-primary-500">
                      términos y condiciones
                    </a> 
                    y la 
                    <a href="/privacy" target="_blank" class="text-primary-600 hover:text-primary-500">
                      política de privacidad
                    </a> *
                  </label>
                  <p v-if="getFieldError('terms')" class="mt-1 text-red-600">
                    {{ getFieldError('terms') }}
                  </p>
                </div>
              </div>

              <div class="flex items-start">
                <div class="flex items-center h-5">
                  <input
                    id="marketing"
                    v-model="form.marketing"
                    type="checkbox"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    :disabled="isLoading"
                  >
                </div>
                <div class="ml-3 text-sm">
                  <label for="marketing" class="text-gray-700">
                    Quiero recibir noticias, ofertas y actualizaciones por email
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between space-x-4 pt-6">
            <Button
              v-if="currentStep > 1"
              type="button"
              @click="previousStep"
              variant="ghost"
              :disabled="isLoading"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
              </template>
              Anterior
            </Button>
            <div v-else></div>

            <Button
              v-if="currentStep < 3"
              type="button"
              @click="nextStep"
              variant="primary"
              :disabled="!canProceed || isLoading"
            >
              Siguiente
              <template #icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </template>
            </Button>
            <Button
              v-else
              type="submit"
              variant="primary"
              :loading="isLoading"
              :disabled="!canProceed || isLoading"
            >
              <template #icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
              </template>
              Crear Cuenta
            </Button>
          </div>

          <!-- General Error -->
          <div v-if="getFieldError('general')" class="text-center">
            <p class="text-sm text-red-600">
              {{ getFieldError('general') }}
            </p>
          </div>
        </form>

        <!-- Login Link -->
        <div class="text-center pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            ¿Ya tienes cuenta?
            <router-link to="/login" class="text-primary-600 hover:text-primary-500 font-medium">
              Iniciar sesión
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { useAuth } from '@/composables/auth/useAuth'
import { useAuthValidation } from '@/composables/auth/useValidation'
import Button from '@/components/ui/Button.vue'

// Auth composables
const { register, isLoading, getFieldError, hasFieldError, clearErrors } = useAuth()

// Form validation
const form = reactive({
  username: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  country_id: '',
  phone_number: '',
  referral_code: '',
  terms: false,
  marketing: false
})

const { registerRules, useValidation } = useAuthValidation()
const password = ref(() => form.password)
const { validateField, validateForm, getError, hasError } = useValidation(registerRules(password))

// Step management
const currentStep = ref(1)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

// Countries data (would come from API)
const countries = ref([
  { id: '1', name: 'Argentina' },
  { id: '2', name: 'Chile' },
  { id: '3', name: 'Colombia' },
  { id: '4', name: 'México' },
  { id: '5', name: 'Venezuela' },
  { id: '6', name: 'Brasil' },
  // Add more countries as needed
])

// Password strength calculation
const passwordStrength = computed(() => {
  const password = form.password
  if (!password) return { width: '0%', color: 'bg-gray-200', textColor: 'text-gray-500', label: '' }

  let score = 0
  if (password.length >= 8) score += 1
  if (/[a-z]/.test(password)) score += 1
  if (/[A-Z]/.test(password)) score += 1
  if (/\d/.test(password)) score += 1
  if (/[@$!%*?&]/.test(password)) score += 1

  const levels = [
    { width: '20%', color: 'bg-red-500', textColor: 'text-red-600', label: 'Muy débil' },
    { width: '40%', color: 'bg-orange-500', textColor: 'text-orange-600', label: 'Débil' },
    { width: '60%', color: 'bg-yellow-500', textColor: 'text-yellow-600', label: 'Regular' },
    { width: '80%', color: 'bg-blue-500', textColor: 'text-blue-600', label: 'Buena' },
    { width: '100%', color: 'bg-green-500', textColor: 'text-green-600', label: 'Muy fuerte' }
  ]

  return levels[Math.max(0, score - 1)] || levels[0]
})

// Step validation
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.username && form.first_name && form.last_name && form.email &&
             !hasError('username') && !hasError('first_name') && !hasError('last_name') && !hasError('email')
    case 2:
      return form.password && form.password_confirmation && form.country_id &&
             !hasError('password') && !hasError('password_confirmation') && !hasError('country_id')
    case 3:
      return form.terms && !hasError('referral_code') && !hasError('terms')
    default:
      return false
  }
})

// Navigation methods
const nextStep = () => {
  if (canProceed.value && currentStep.value < 3) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

// Form submission
const handleRegister = async () => {
  clearErrors()
  
  if (!validateForm(form)) {
    // Go to first step with errors
    for (let step = 1; step <= 3; step++) {
      // Check which step has errors and navigate there
      const stepFields = getStepFields(step)
      const hasStepErrors = stepFields.some(field => hasError(field))
      if (hasStepErrors) {
        currentStep.value = step
        break
      }
    }
    return
  }

  await register(form)
}

// Helper to get fields for each step
const getStepFields = (step: number): string[] => {
  switch (step) {
    case 1:
      return ['username', 'first_name', 'last_name', 'email']
    case 2:
      return ['password', 'password_confirmation', 'country_id', 'phone_number']
    case 3:
      return ['referral_code', 'terms']
    default:
      return []
  }
}

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
</style>