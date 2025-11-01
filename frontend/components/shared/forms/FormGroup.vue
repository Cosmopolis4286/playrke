<template>
  <div
    class="form-group"
    :class="groupClasses"
  >
    <!-- Group Header -->
    <div
      v-if="title || $slots.header"
      class="form-group__header"
    >
      <slot name="header">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="@apply text-lg font-semibold text-gray-900">
              {{ title }}
              <span
                v-if="required"
                class="text-red-500 ml-1"
              >*</span>
            </h3>
            <p
              v-if="description"
              class="@apply text-sm text-gray-600 mt-1"
            >
              {{ description }}
            </p>
          </div>

          <!-- Group Actions -->
          <div
            v-if="$slots.actions"
            class="flex items-center gap-2"
          >
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </div>

    <!-- Group Content -->
    <div
      class="form-group__content"
      :class="contentClasses"
    >
      <!-- Progress Indicator -->
      <div
        v-if="showProgress"
        class="@apply mb-6 bg-gray-200 rounded-full h-2 overflow-hidden"
      >
        <div
          class="@apply h-full bg-primary-600 transition-all duration-500 ease-out rounded-full"
          :style="{ width: `${progressPercentage}%` }"
        />
        <div
          v-if="showProgressText"
          class="@apply text-sm text-gray-600 mt-2 text-center"
        >
          {{ progressText }}
        </div>
      </div>

      <!-- Field Grid -->
      <div
        v-if="layout === 'grid'"
        :class="gridClasses"
      >
        <slot
          :validate="validateGroup"
          :reset="resetGroup"
          :errors="groupErrors"
        />
      </div>

      <!-- Field Stack -->
      <div
        v-else-if="layout === 'stack'"
        class="space-y-4"
      >
        <slot
          :validate="validateGroup"
          :reset="resetGroup"
          :errors="groupErrors"
        />
      </div>

      <!-- Sections Layout -->
      <div
        v-else-if="layout === 'sections'"
        class="space-y-8"
      >
        <div
          v-for="(section, index) in sections"
          :key="section.id || index"
          class="form-section"
        >
          <!-- Section Header -->
          <div
            v-if="section.title"
            class="@apply mb-4 pb-2 border-b border-gray-200"
          >
            <h4 class="@apply text-base font-medium text-gray-900">
              {{ section.title }}
            </h4>
            <p
              v-if="section.description"
              class="@apply text-sm text-gray-600 mt-1"
            >
              {{ section.description }}
            </p>
          </div>

          <!-- Section Fields -->
          <div :class="section.layout === 'grid' ? getGridClasses(section.columns) : 'space-y-4'">
            <slot
              :name="`section-${section.id || index}`"
              :section="section"
              :validate="validateGroup"
              :reset="resetGroup"
              :errors="groupErrors"
            />
          </div>
        </div>
      </div>

      <!-- Tabs Layout -->
      <div
        v-else-if="layout === 'tabs'"
        class="form-tabs"
      >
        <!-- Tab Navigation -->
        <div class="@apply border-b border-gray-200 mb-6">
          <nav class="@apply -mb-px flex space-x-8">
            <button
              v-for="(tab, index) in tabs"
              :key="tab.id || index"
              class="
                '@apply py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                activeTab === (tab.id || index)
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
              @click="activeTab = tab.id || index"
            >
              <SharedIcon
                v-if="tab.icon"
                :name="tab.icon"
                class="w-4 h-4 mr-2 inline"
              />
              {{ tab.title }}
              <BaseBadge
                v-if="getTabErrorCount(tab.id || index) > 0"
                variant="error"
                size="sm"
                class="ml-2"
              >
                {{ getTabErrorCount(tab.id || index) }}
              </BaseBadge>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="form-tab-content">
          <div
            v-for="(tab, index) in tabs"
            v-show="activeTab === (tab.id || index)"
            :key="tab.id || index"
            class="space-y-4"
          >
            <slot
              :name="`tab-${tab.id || index}`"
              :tab="tab"
              :validate="validateGroup"
              :reset="resetGroup"
              :errors="groupErrors"
            />
          </div>
        </div>
      </div>

      <!-- Default Slot -->
      <div v-else>
        <slot
          :validate="validateGroup"
          :reset="resetGroup"
          :errors="groupErrors"
        />
      </div>
    </div>

    <!-- Group Footer -->
    <div
      v-if="$slots.footer || showDefaultActions"
      class="form-group__footer"
      :class="footerClasses"
    >
      <slot
        name="footer"
        :validate="validateGroup"
        :reset="resetGroup"
        :errors="groupErrors"
      >
        <div
          v-if="showDefaultActions"
          class="flex items-center justify-between"
        >
          <!-- Validation Summary -->
          <div
            v-if="showValidationSummary && hasErrors"
            class="flex items-center text-red-600"
          >
            <SharedIcon
              name="warning"
              class="w-4 h-4 mr-2"
            />
            <span class="text-sm">
              {{ validationSummaryText }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-3 ml-auto">
            <BaseButton
              v-if="showResetButton"
              variant="outline"
              :disabled="isValidating"
              @click="resetGroup"
            >
              Limpiar
            </BaseButton>

            <BaseButton
              v-if="showValidateButton"
              variant="primary"
              :loading="isValidating"
              @click="validateGroup"
            >
              Validar
            </BaseButton>
          </div>
        </div>
      </slot>
    </div>

    <!-- Global Errors -->
    <div
      v-if="globalErrors.length > 0"
      class="@apply mt-4"
    >
      <UiAlert variant="error">
        <template #title>
          Errores de validación
        </template>
        <ul class="@apply list-disc list-inside space-y-1">
          <li
            v-for="error in globalErrors"
            :key="error"
            class="text-sm"
          >
            {{ error }}
          </li>
        </ul>
      </UiAlert>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview FormGroup - Agrupación de campos con validación
 * @description Contenedor inteligente para grupos de campos con layouts flexibles
 */

import { computed, ref, provide, onMounted, watch } from 'vue'

interface FormSection {
  id?: string | number
  title?: string
  description?: string
  layout?: 'grid' | 'stack'
  columns?: number
  fields?: string[]
}

interface FormTab {
  id?: string | number
  title: string
  description?: string
  icon?: string
  disabled?: boolean
  fields?: string[]
}

interface Props {
  // Basic
  title?: string
  description?: string
  required?: boolean

  // Layout
  layout?: 'grid' | 'stack' | 'sections' | 'tabs' | 'auto'
  columns?: number
  gap?: 'sm' | 'md' | 'lg'
  responsive?: boolean

  // Sections & Tabs
  sections?: FormSection[]
  tabs?: FormTab[]
  activeTabDefault?: string | number

  // Validation
  validateOnMount?: boolean
  validateOnChange?: boolean
  validateRealtime?: boolean
  showValidationSummary?: boolean
  globalErrors?: string[]

  // Progress
  showProgress?: boolean
  showProgressText?: boolean
  totalFields?: number
  completedFields?: number

  // Actions
  showDefaultActions?: boolean
  showValidateButton?: boolean
  showResetButton?: boolean

  // Styling
  variant?: 'default' | 'card' | 'bordered' | 'minimal'
  size?: 'sm' | 'md' | 'lg'
  background?: boolean
  shadow?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  layout: 'auto',
  columns: 2,
  gap: 'md',
  responsive: true,
  validateOnMount: false,
  validateOnChange: true,
  validateRealtime: false,
  showValidationSummary: true,
  globalErrors: () => [],
  showDefaultActions: true,
  showValidateButton: true,
  showResetButton: true,
  variant: 'default',
  size: 'md',
  background: false,
  shadow: false,
  activeTabDefault: 0,
})

const emit = defineEmits<{
  'validate': [isValid: boolean, errors: Record<string, string[]>]
  'reset': []
  'field-change': [fieldName: string, value: unknown]
  'tab-change': [tabId: string | number]
  'section-complete': [sectionId: string | number]
}>()

// State
const activeTab = ref(props.activeTabDefault)
const isValidating = ref(false)
const fieldValidations = ref<Record<string, { isValid: boolean, errors: string[] }>>({})
const fieldValues = ref<Record<string, any>>({})

// Form Group Context
const formGroupContext = {
  registerField: (fieldName: string, validation: { isValid: boolean, errors: string[] }) => {
    fieldValidations.value[fieldName] = validation
  },
  unregisterField: (fieldName: string) => {
    fieldValidations.value[fieldName] = undefined
    fieldValues.value[fieldName] = undefined
  },
  updateFieldValue: (fieldName: string, value: unknown) => {
    fieldValues.value[fieldName] = value
    emit('field-change', fieldName, value)

    if (props.validateRealtime) {
      validateGroup()
    }
  },
  validateField: (fieldName: string) => fieldValidations.value[fieldName]?.isValid ?? true,
}

provide('formGroup', formGroupContext)

// Computed
const groupClasses = computed(() => [
  'form-group',
  `form-group--${props.variant}`,
  `form-group--${props.size}`,
  {
    'form-group--background': props.background,
    'form-group--shadow': props.shadow,
    'form-group--has-errors': hasErrors.value,
  },
])

const contentClasses = computed(() => {
  const classes = ['form-group__content']

  if (props.variant === 'card') {
    classes.push('@apply bg-white border border-gray-200 rounded-lg p-6')
  } else if (props.variant === 'bordered') {
    classes.push('@apply border border-gray-200 rounded-lg p-4')
  } else if (props.variant === 'minimal') {
    classes.push('@apply p-2')
  } else {
    classes.push('@apply p-4')
  }

  return classes
})

const footerClasses = computed(() => {
  const classes = ['form-group__footer', '@apply mt-6 pt-4']

  if (props.variant === 'card' || props.variant === 'bordered') {
    classes.push('@apply border-t border-gray-200')
  }

  return classes
})

const gridClasses = computed(() => {
  const classes = ['grid', `gap-${props.gap}`]

  if (props.responsive) {
    if (props.columns === 1) {
      classes.push('grid-cols-1')
    } else if (props.columns === 2) {
      classes.push('grid-cols-1 md:grid-cols-2')
    } else if (props.columns === 3) {
      classes.push('grid-cols-1 md:grid-cols-2 lg:grid-cols-3')
    } else {
      classes.push(`grid-cols-1 md:grid-cols-2 lg:grid-cols-${Math.min(props.columns, 4)}`)
    }
  } else {
    classes.push(`grid-cols-${props.columns}`)
  }

  return classes.join(' ')
})

const groupErrors = computed(() => {
  const errors: Record<string, string[]> = {}

  Object.entries(fieldValidations.value).forEach(([fieldName, validation]) => {
    if (!validation.isValid && validation.errors.length > 0) {
      errors[fieldName] = validation.errors
    }
  })

  return errors
})

const hasErrors = computed(() => Object.keys(groupErrors.value).length > 0 || props.globalErrors.length > 0)

const errorCount = computed(() => Object.values(groupErrors.value).reduce((count, errors) => count + errors.length, 0) + props.globalErrors.length)

const validationSummaryText = computed(() => {
  const count = errorCount.value
  return count === 1 ? '1 error encontrado' : `${count} errores encontrados`
})

const progressPercentage = computed(() => {
  if (!props.totalFields || props.totalFields === 0) { return 0 }
  const completed = props.completedFields || Object.keys(fieldValues.value).length
  return Math.min((completed / props.totalFields) * 100, 100)
})

const progressText = computed(() => {
  const completed = props.completedFields || Object.keys(fieldValues.value).length
  const total = props.totalFields || 0
  return `${completed}/${total} campos completados`
})

// Methods
const getGridClasses = (columns: number = 2) => {
  if (props.responsive) {
    if (columns === 1) { return 'grid grid-cols-1 gap-4' }
    if (columns === 2) { return 'grid grid-cols-1 md:grid-cols-2 gap-4' }
    if (columns === 3) { return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4' }
    return `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-${Math.min(columns, 4)} gap-4`
  }
  return `grid grid-cols-${columns} gap-4`
}

const getTabErrorCount = (tabId: string | number): number => {
  const tab = props.tabs?.find(t => (t.id || props.tabs?.indexOf(t)) === tabId)
  if (!tab?.fields) { return 0 }

  return tab.fields.reduce((count, fieldName) => {
    const fieldErrors = groupErrors.value[fieldName]
    return count + (fieldErrors?.length || 0)
  }, 0)
}

const validateGroup = async (): Promise<boolean> => {
  isValidating.value = true

  try {
    await new Promise(resolve => setTimeout(resolve, 100))
    const isValid = !hasErrors.value
    emit('validate', isValid, groupErrors.value)
    return isValid
  } finally {
    isValidating.value = false
  }
}

const resetGroup = () => {
  fieldValidations.value = {}
  fieldValues.value = {}
  emit('reset')
}

// Watchers
watch(() => activeTab.value, (newTab) => {
  emit('tab-change', newTab)
})

watch(() => fieldValues.value, () => {
  if (props.validateOnChange) {
    validateGroup()
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  if (props.validateOnMount) {
    validateGroup()
  }
})

// Expose methods
defineExpose({
  validate: validateGroup,
  reset: resetGroup,
  getFieldValue: (fieldName: string) => fieldValues.value[fieldName],
  setFieldValue: (fieldName: string, value: unknown) => {
    fieldValues.value[fieldName] = value
  },
  getErrors: () => groupErrors.value,
  isValid: () => !hasErrors.value,
  setActiveTab: (tabId: string | number) => {
    activeTab.value = tabId
  },
})
</script>

<style scoped>
.form-group {
  @apply w-full;
}

.form-group--sm {
  @apply text-sm;
}

.form-group--lg {
  @apply text-base;
}

.form-group--card {
  @apply bg-gray-50 rounded-lg p-1;
}

.form-group--background {
  @apply bg-gray-50;
}

.form-group--shadow {
  @apply shadow-sm;
}

.form-group__header {
  @apply mb-6;
}

.form-section {
  @apply relative;
}

.form-tabs {
  @apply w-full;
}

.form-tab-content {
  @apply min-h-[200px];
}

/* Focus management for tabs */
.form-tabs button:focus {
  @apply outline-none ring-2 ring-primary-500 ring-offset-2;
}

/* Animation for tab transitions */
.form-tab-content > div {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .form-group *,
  .form-tab-content > div {
    animation: none !important;
    transition: none !important;
  }
}
</style>
