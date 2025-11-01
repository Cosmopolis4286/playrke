<template>
  <form
    :class="formClasses"
    :novalidate="noValidate"
    v-bind="$attrs"
    @submit="handleSubmit"
    @reset="handleReset"
  >
    <!-- Header Slot -->
    <header
      v-if="$slots.header || title"
      :class="headerClasses"
    >
      <slot
        name="header"
        :form-state="formState"
      >
        <div
          v-if="title"
          class="space-y-1"
        >
          <h2 :class="titleClasses">
            {{ title }}
          </h2>
          <p
            v-if="description"
            :class="descriptionClasses"
          >
            {{ description }}
          </p>
        </div>
      </slot>
    </header>

    <!-- Main Content -->
    <div :class="contentClasses">
      <slot
        :form-state="formState"
        :errors="errors"
        :set-field="setField"
        :validate="validateField"
      />
    </div>

    <!-- Error Summary -->
    <div
      v-if="showErrors && hasErrors"
      :class="errorSummaryClasses"
    >
      <div class="flex items-start gap-2">
        <Icon
          name="exclamation-triangle"
          class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0"
        />
        <div>
          <h4 class="text-sm font-medium text-red-800 mb-1">
            {{ errorSummaryTitle }}
          </h4>
          <ul class="text-xs text-red-600 space-y-1">
            <li
              v-for="error in errorList"
              :key="error.field"
            >
              {{ error.message }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <footer
      v-if="$slots.footer || showDefaultActions"
      :class="footerClasses"
    >
      <slot
        name="footer"
        :form-state="formState"
        :submit="handleSubmit"
        :reset="handleReset"
      >
        <div
          v-if="showDefaultActions"
          class="flex items-center justify-between"
        >
          <!-- Secondary Actions -->
          <div class="flex items-center gap-2">
            <BaseButton
              v-if="showResetButton"
              type="reset"
              variant="ghost"
              size="sm"
              :disabled="!formState.dirty || formState.submitting"
              @click="handleReset"
            >
              {{ resetText }}
            </BaseButton>
          </div>

          <!-- Primary Actions -->
          <div class="flex items-center gap-2">
            <BaseButton
              type="submit"
              :variant="submitVariant"
              :size="submitSize"
              :loading="formState.submitting"
              :disabled="!isValid || formState.submitting"
            >
              {{ submitText }}
            </BaseButton>
          </div>
        </div>
      </slot>
    </footer>

    <!-- Loading Overlay -->
    <div
      v-if="formState.submitting && showLoadingOverlay"
      :class="loadingOverlayClasses"
    >
      <div class="flex flex-col items-center gap-3">
        <Icon
          name="spinner"
          class="w-8 h-8 text-primary-600 animate-spin"
        />
        <span class="text-sm font-medium text-gray-700">{{ loadingText }}</span>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
/**
 * @fileoverview BaseForm - Componente de formulario base optimizado
 * @description Formulario simple y performante con validación básica
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { computed, reactive, ref, watch, onMounted } from 'vue'
import Button from '~/components/ui/base/Button.vue'
import { uiLogger } from '~/utils/logger'
// usePerformance auto-imported by Nuxt

type FormVariant = 'default' | 'card' | 'minimal'
type FormSize = 'sm' | 'md' | 'lg'

interface ValidationError {
  field: string
  message: string
}

interface FormState {
  submitting: boolean
  submitted: boolean
  dirty: boolean
  touched: boolean
}

interface Props {
  // Apariencia
  variant?: FormVariant
  size?: FormSize

  // Contenido
  title?: string
  description?: string

  // Comportamiento
  noValidate?: boolean
  showErrors?: boolean
  errorSummaryTitle?: string

  // Acciones
  showDefaultActions?: boolean
  showResetButton?: boolean
  submitText?: string
  resetText?: string
  submitVariant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning' | 'info' | 'outline'
  submitSize?: 'sm' | 'md' | 'lg' | 'xl'

  // Estados
  showLoadingOverlay?: boolean
  loadingText?: string

  // Datos iniciales (tipado seguro)
  initialData?: Record<string, unknown>

  // Validación (tipado seguro)
  validationRules?: Record<string, (value: unknown) => string | true>
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  noValidate: true,
  showErrors: true,
  errorSummaryTitle: 'Corrige los siguientes errores:',
  showDefaultActions: true,
  showResetButton: true,
  submitText: 'Enviar',
  resetText: 'Limpiar',
  submitVariant: 'primary',
  submitSize: 'md',
  showLoadingOverlay: true,
  loadingText: 'Procesando...',
  initialData: () => ({}),
  validationRules: () => ({}),
})

const emit = defineEmits<{
  submit: [data: Record<string, unknown>, event: Event]
  reset: [event: Event]
  change: [field: string, value: unknown, formData: Record<string, unknown>]
}>()

// Composables disponibles via auto-import

// Estado reactivo
const formState = reactive<FormState>({
  submitting: false,
  submitted: false,
  dirty: false,
  touched: false,
})

const formData = ref<Record<string, unknown>>({})
const errors = ref<Record<string, string>>({})

// Inicialización
onMounted(() => {
  formData.value = { ...props.initialData }
})

// Computadas memoizadas
const hasErrors = computed(() => Object.keys(errors.value).length > 0)

const isValid = computed(() => !hasErrors.value)

const errorList = computed((): ValidationError[] =>
  Object.entries(errors.value).map(([field, message]) => ({ field, message })),
)

// Clases computadas con memoización
const formClasses = computed(() => [
  'relative transition-all duration-200',
  {
    // Variantes
    'bg-white rounded-xl border border-gray-200 shadow-sm': props.variant === 'card',
    'bg-white rounded-lg border border-gray-200': props.variant === 'default',
    '': props.variant === 'minimal',

    // Tamaños
    'p-4 space-y-4': props.size === 'sm',
    'p-6 space-y-6': props.size === 'md',
    'p-8 space-y-8': props.size === 'lg',

    // Estados
    'opacity-75 pointer-events-none': formState.submitting && props.showLoadingOverlay,
  },
])

const headerClasses = computed(() => [
  {
    'pb-3 border-b border-gray-200': props.variant !== 'minimal',
    'mb-4': props.size === 'sm',
    'mb-6': props.size === 'md' || props.size === 'lg',
  },
])

const titleClasses = computed(() => [
  'font-semibold text-gray-900',
  {
    'text-lg': props.size === 'sm',
    'text-xl': props.size === 'md',
    'text-2xl': props.size === 'lg',
  },
])

const descriptionClasses = computed(() => 'text-gray-600 text-sm mt-1')

const contentClasses = computed(() => 'space-y-4')

const errorSummaryClasses = computed(() => [
  'p-3 rounded-lg border border-red-200 bg-red-50',
  {
    'text-sm': props.size === 'sm',
    'text-base': props.size === 'md' || props.size === 'lg',
  },
])

const footerClasses = computed(() => [
  {
    'pt-4 border-t border-gray-200': props.variant !== 'minimal',
    'mt-4': props.size === 'sm',
    'mt-6': props.size === 'md' || props.size === 'lg',
  },
])

const loadingOverlayClasses = computed(() => [
  'absolute inset-0 bg-white/80 backdrop-blur-sm',
  'flex items-center justify-center z-50 rounded-xl',
])

// Métodos optimizados
const setField = (field: string, value: unknown): void => {
  formData.value[field] = value
  formState.dirty = true

  // Limpiar error del campo si existe
  if (errors.value[field]) {
    errors.value[field] = undefined
  }

  emit('change', field, value, { ...formData.value })
}

const validateField = (field: string): boolean => {
  const rule = props.validationRules[field]
  if (!rule) { return true }

  const result = rule(formData.value[field])

  if (result === true) {
    errors.value[field] = undefined
    return true
  }

  errors.value[field] = result
  return false
}

const validateForm = (): boolean => {
  let isFormValid = true

  // Validar todos los campos con reglas
  Object.keys(props.validationRules).forEach((field) => {
    if (!validateField(field)) {
      isFormValid = false
    }
  })

  return isFormValid
}

const handleSubmit = async (event: Event): Promise<void> => {
  event.preventDefault()

  if (formState.submitting) { return }

  formState.touched = true

  // Validar formulario
  if (!validateForm()) {
    return
  }

  formState.submitting = true

  try {
    emit('submit', { ...formData.value }, event)
    formState.submitted = true
    formState.dirty = false
  } catch (error) {
    uiLogger.error('Form submission error', { error, formData: formData.value, component: 'BaseForm' })
  } finally {
    formState.submitting = false
  }
}

const handleReset = (event: Event): void => {
  formData.value = { ...props.initialData }
  errors.value = {}
  formState.dirty = false
  formState.touched = false
  formState.submitted = false

  emit('reset', event)
}

// Watchers optimizados
watch(() => props.initialData, (newData) => {
  if (newData && !formState.dirty) {
    formData.value = { ...newData }
  }
}, { deep: true })

// API pública
defineExpose({
  formData: computed(() => formData.value),
  formState: computed(() => formState),
  errors: computed(() => errors.value),
  isValid,
  hasErrors,
  setField,
  validateField,
  validateForm,
  submit: handleSubmit,
  reset: handleReset,
})
</script>

<style scoped>
/* Animaciones suaves */
.form-field:focus-within {
  z-index: 10;
}

/* Optimización de loading */
.pointer-events-none * {
  pointer-events: none;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .transition-all {
    transition: none !important;
  }
}
</style>
