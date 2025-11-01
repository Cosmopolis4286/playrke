<template>
  <FormGroup
    ref="formGroupRef"
    :title="config.title"
    :description="config.description"
    :layout="config.layout"
    :columns="config.columns"
    :variant="config.variant"
    :show-progress="config.showProgress"
    :total-fields="totalFieldsCount"
    :completed-fields="completedFieldsCount"
    :sections="config.sections"
    :tabs="config.tabs"
    :validate-on-mount="config.validateOnMount"
    :validate-on-change="config.validateOnChange"
    :global-errors="globalErrors"
    @validate="handleValidate"
    @reset="handleReset"
    @field-change="handleFieldChange"
    @tab-change="handleTabChange"
  >
    <!-- Dynamic Fields -->
    <template
      v-for="field in visibleFields"
      :key="field.id"
    >
      <FormField
        v-if="field.type !== 'custom' && field.type !== 'divider' && field.type !== 'html' && field.type !== 'slot'"
        v-model="formData[field.id]"
        :type="field.type"
        :name="field.id"
        :label="field.label"
        :placeholder="field.placeholder"
        :help-text="field.helpText"
        :required="field.required"
        :disabled="field.disabled || isSubmitting"
        :readonly="field.readonly"
        :errors="fieldErrors[field.id]"
        :options="getFieldOptions(field)"
        :radio-options="field.radioOptions"
        :checkbox-label="field.checkboxLabel"
        :size="field.size || config.fieldSize"
        :variant="field.variant || config.fieldVariant"
        :prefix-icon="field.prefixIcon"
        :suffix-icon="field.suffixIcon"
        :show-char-count="field.showCharCount"
        :maxlength="field.maxlength"
        :min="field.min"
        :max="field.max"
        :step="field.step"
        :pattern="field.pattern"
        :rows="field.rows"
        :autocomplete="field.autocomplete"
        :debounce="field.debounce || config.debounce"
        :validate-on-blur="field.validateOnBlur ?? config.validateOnBlur"
        :validate-on-input="field.validateOnInput ?? config.validateOnInput"
        @validation="handleFieldValidation(field.id, $event[0], $event[1])"
        @input="handleFieldInput(field, $event)"
        @blur="handleFieldBlur(field, $event)"
        @focus="handleFieldFocus(field, $event)"
      />

      <!-- Custom Field Component -->
      <component
        :is="field.component"
        v-else-if="field.component"
        v-model="formData[field.id]"
        v-bind="field.props"
        :disabled="field.disabled || isSubmitting"
        :errors="fieldErrors[field.id]"
        @validation="handleFieldValidation(field.id, $event[0], $event[1])"
      />

      <!-- Divider -->
      <hr
        v-else-if="field.type === 'divider'"
        class="@apply my-6 border-gray-200"
      >

      <!-- HTML Content - DEPRECATED: Use 'text' type instead -->
      <!-- ⚠️ SECURITY: v-html removed to prevent XSS attacks -->
      <!-- If you need formatted content, use 'text' type or create a custom component -->
      <div
        v-else-if="field.type === 'html'"
        class="prose prose-sm max-w-none p-4 bg-yellow-50 border border-yellow-200 rounded"
      >
        <p class="text-sm text-yellow-800 font-semibold mb-2">
          ⚠️ Deprecated: HTML type
        </p>
        <p class="text-xs text-yellow-700">
          The 'html' field type has been deprecated for security reasons.
          Please use 'text' type or create a custom component instead.
        </p>
        <p class="text-xs text-gray-600 mt-2 font-mono">
          {{ field.content }}
        </p>
      </div>

      <!-- Custom Slot -->
      <div v-else-if="field.type === 'slot'">
        <slot
          :name="field.slotName"
          :field="field"
          :value="formData[field.id]"
          :errors="fieldErrors[field.id]"
          :set-value="(value: unknown) => setFieldValue(field.id, value)"
        />
      </div>
    </template>

    <!-- Form Actions -->
    <template #footer>
      <div class="flex items-center justify-between">
        <!-- Validation Summary -->
        <div
          v-if="hasErrors && config.showValidationSummary"
          class="flex items-center text-red-600"
        >
          <Icon
            name="warning"
            class="w-4 h-4 mr-2"
          />
          <span class="text-sm">
            {{ validationSummaryText }}
          </span>
        </div>

        <!-- Custom Actions Slot -->
        <div
          v-if="$slots.actions"
          class="flex items-center gap-3"
        >
          <slot
            name="actions"
            :submit="handleSubmit"
            :reset="handleReset"
            :validate="handleValidate"
            :is-valid="isValid"
            :is-submitting="isSubmitting"
            :errors="fieldErrors"
            :data="formData"
          />
        </div>

        <!-- Default Actions -->
        <div
          v-else
          class="flex items-center gap-3 ml-auto"
        >
          <BaseButton
            v-if="config.showResetButton"
            variant="outline"
            :disabled="isSubmitting"
            @click="handleReset"
          >
            {{ config.resetButtonText || 'Limpiar' }}
          </BaseButton>

          <BaseButton
            v-if="config.showSubmitButton"
            variant="primary"
            :loading="isSubmitting"
            :disabled="!isValid && config.requireValidation"
            @click="handleSubmit"
          >
            {{ config.submitButtonText || 'Enviar' }}
          </BaseButton>
        </div>
      </div>
    </template>
  </FormGroup>
</template>

<script setup lang="ts">
/**
 * @fileoverview QuickForm - Formulario dinámico con configuración JSON
 * @description Genera formularios completos desde configuración declarativa
 */

import { computed, ref, reactive, watch, onMounted, nextTick } from 'vue'

interface FormField {
  id: string
  /**
   * Field type
   * @deprecated 'html' type is deprecated for security reasons (XSS vulnerability)
   * Use 'text' type or create a custom component with proper sanitization
   */
  type: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'date' | 'time' | 'datetime-local' | 'divider' | 'html' | 'slot' | 'custom'
  label?: string
  placeholder?: string
  helpText?: string
  defaultValue?: unknown
  required?: boolean
  disabled?: boolean
  readonly?: boolean

  // Validation
  min?: string | number
  max?: string | number
  step?: string | number
  pattern?: string
  maxlength?: number
  validationRules?: ValidationRule[]

  // Options for select/radio
  options?: Array<{ label: string, value: unknown, disabled?: boolean }>
  radioOptions?: Array<{ label: string, value: unknown, disabled?: boolean }>
  checkboxLabel?: string

  // Conditional display
  showIf?: ConditionalRule
  hideIf?: ConditionalRule
  dependsOn?: string[]

  // Styling
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'filled' | 'outlined'
  prefixIcon?: string
  suffixIcon?: string
  showCharCount?: boolean
  rows?: number

  // Behavior
  autocomplete?: string
  debounce?: number
  validateOnBlur?: boolean
  validateOnInput?: boolean

  // Custom component
  component?: string
  props?: Record<string, any>

  // Special types
  content?: string // for html type
  slotName?: string // for slot type

  // Layout hints
  span?: number
  order?: number
  section?: string
  tab?: string
}

interface ConditionalRule {
  field: string
  operator: 'equals' | 'not_equals' | 'contains' | 'not_contains' | 'greater_than' | 'less_than' | 'empty' | 'not_empty'
  value?: unknown
}

interface ValidationRule {
  type: 'required' | 'email' | 'min' | 'max' | 'pattern' | 'custom'
  value?: unknown
  message?: string
  function?: (value: unknown, formData: Record<string, any>) => boolean | string
}

interface FormConfig {
  title?: string
  description?: string
  layout?: 'grid' | 'stack' | 'sections' | 'tabs'
  columns?: number
  variant?: 'default' | 'card' | 'bordered' | 'minimal'

  // Field defaults
  fieldSize?: 'sm' | 'md' | 'lg'
  fieldVariant?: 'default' | 'filled' | 'outlined'

  // Behavior
  validateOnMount?: boolean
  validateOnChange?: boolean
  validateOnBlur?: boolean
  validateOnInput?: boolean
  requireValidation?: boolean
  debounce?: number

  // Progress
  showProgress?: boolean

  // Actions
  showSubmitButton?: boolean
  showResetButton?: boolean
  submitButtonText?: string
  resetButtonText?: string
  showValidationSummary?: boolean

  // Layout specific
  sections?: Array<{
    id: string
    title?: string
    description?: string
    fields: string[]
    layout?: 'grid' | 'stack'
    columns?: number
  }>

  tabs?: Array<{
    id: string
    title: string
    description?: string
    icon?: string
    fields: string[]
  }>
}

interface Props {
  config: FormConfig
  fields: FormField[]
  initialData?: Record<string, any>
  disabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialData: () => ({}),
  disabled: false,
  loading: false,
})

const emit = defineEmits<{
  'submit': [data: Record<string, any>, isValid: boolean]
  'change': [fieldId: string, value: unknown, formData: Record<string, any>]
  'validate': [isValid: boolean, errors: Record<string, string[]>]
  'reset': []
  'field-focus': [fieldId: string, field: FormField]
  'field-blur': [fieldId: string, field: FormField]
  'tab-change': [tabId: string]
}>()

// State
const formGroupRef = ref()
const formData = reactive<Record<string, any>>({})
const fieldErrors = ref<Record<string, string[]>>({})
const fieldValidations = ref<Record<string, boolean>>({})
const isSubmitting = ref(false)
const globalErrors = ref<string[]>([])

// Computed
const visibleFields = computed(() => props.fields.filter(field => shouldShowField(field)))

const totalFieldsCount = computed(() => props.fields.filter(field =>
  field.type !== 'divider'
  && field.type !== 'html'
  && field.type !== 'slot',
).length)

const completedFieldsCount = computed(() => Object.keys(formData).filter((key) => {
  const value = formData[key]
  return value !== undefined && value !== null && value !== ''
}).length)

const hasErrors = computed(() => Object.keys(fieldErrors.value).length > 0 || globalErrors.value.length > 0)

const isValid = computed(() => Object.values(fieldValidations.value).every(isValid => isValid) && !hasErrors.value)

const validationSummaryText = computed(() => {
  const errorCount = Object.values(fieldErrors.value).reduce((count, errors) => count + errors.length, 0)
  return errorCount === 1 ? '1 error encontrado' : `${errorCount} errores encontrados`
})

// Methods
const shouldShowField = (field: FormField): boolean => {
  if (field.showIf && !evaluateCondition(field.showIf)) { return false }
  if (field.hideIf && evaluateCondition(field.hideIf)) { return false }
  return true
}

const evaluateCondition = (rule: ConditionalRule): boolean => {
  const fieldValue = formData[rule.field]

  switch (rule.operator) {
    case 'equals':
      return fieldValue === rule.value
    case 'not_equals':
      return fieldValue !== rule.value
    case 'contains':
      return String(fieldValue).includes(String(rule.value))
    case 'not_contains':
      return !String(fieldValue).includes(String(rule.value))
    case 'greater_than':
      return Number(fieldValue) > Number(rule.value)
    case 'less_than':
      return Number(fieldValue) < Number(rule.value)
    case 'empty':
      return !fieldValue || fieldValue === '' || fieldValue === null || fieldValue === undefined
    case 'not_empty':
      return fieldValue && fieldValue !== '' && fieldValue !== null && fieldValue !== undefined
    default:
      return true
  }
}

const getFieldOptions = (field: FormField) => field.options || []

const setFieldValue = (fieldId: string, value: unknown) => {
  formData[fieldId] = value
  emit('change', fieldId, value, { ...formData })
}

const handleFieldValidation = (fieldId: string, isValid: boolean, errors: string[]) => {
  fieldValidations.value[fieldId] = isValid

  if (errors.length > 0) {
    fieldErrors.value[fieldId] = errors
  } else {
    fieldErrors.value[fieldId] = undefined
  }
}

const handleFieldChange = (fieldId: string, value: unknown) => {
  emit('change', fieldId, value, { ...formData })
}

const handleFieldInput = (field: FormField, _event: Event) => {
  if (field.dependsOn) {
    updateDependentFields(field)
  }
}

const handleFieldBlur = (field: FormField, _event: Event) => {
  emit('field-blur', field.id, field)
}

const handleFieldFocus = (field: FormField, _event: Event) => {
  emit('field-focus', field.id, field)
}

const updateDependentFields = (field: FormField) => {
  const dependentFields = props.fields.filter(f =>
    f.dependsOn?.includes(field.id),
  )

  dependentFields.forEach((depField) => {
    nextTick(() => {
      const currentValue = formData[depField.id]
      if (currentValue !== undefined) {
        formData[depField.id] = currentValue
      }
    })
  })
}

const handleValidate = async (isValid: boolean, errors: Record<string, string[]>) => {
  fieldErrors.value = { ...errors }
  await runCustomValidations()
  emit('validate', isValid && globalErrors.value.length === 0, fieldErrors.value)
}

const runCustomValidations = async () => {
  globalErrors.value = []

  for (const field of props.fields) {
    if (field.validationRules) {
      for (const rule of field.validationRules) {
        if (rule.type === 'custom' && rule.function) {
          const result = rule.function(formData[field.id], formData)
          if (typeof result === 'string') {
            globalErrors.value.push(result)
          } else if (!result) {
            globalErrors.value.push(rule.message || `Error en campo ${field.label || field.id}`)
          }
        }
      }
    }
  }
}

const handleSubmit = async () => {
  if (isSubmitting.value) { return }

  isSubmitting.value = true

  try {
    const isFormValid = await formGroupRef.value?.validate()
    await runCustomValidations()

    const finalIsValid = isFormValid && globalErrors.value.length === 0
    emit('submit', { ...formData }, finalIsValid)
  } finally {
    isSubmitting.value = false
  }
}

const handleReset = () => {
  Object.keys(formData).forEach((key) => {
    formData[key] = undefined
  })

  Object.assign(formData, props.initialData)

  fieldErrors.value = {}
  fieldValidations.value = {}
  globalErrors.value = []

  formGroupRef.value?.reset()
  emit('reset')
}

const handleTabChange = (tabId: string | number) => {
  emit('tab-change', String(tabId))
}

const initializeFormData = () => {
  props.fields.forEach((field) => {
    if (field.defaultValue !== undefined) {
      formData[field.id] = field.defaultValue
    }
  })

  Object.assign(formData, props.initialData)
}

// Watchers
watch(() => props.initialData, (newData) => {
  Object.assign(formData, newData)
}, { deep: true })

watch(() => props.fields, () => {
  initializeFormData()
}, { deep: true })

// Lifecycle
onMounted(() => {
  initializeFormData()
})

// Expose methods
defineExpose({
  submit: handleSubmit,
  reset: handleReset,
  validate: () => formGroupRef.value?.validate(),
  setFieldValue,
  getFieldValue: (fieldId: string) => formData[fieldId],
  getFormData: () => ({ ...formData }),
  setFormData: (data: Record<string, any>) => Object.assign(formData, data),
  isValid: () => isValid.value,
  getErrors: () => ({ ...fieldErrors.value }),
})
</script>

<style scoped>
.field-enter-active,
.field-leave-active {
  transition: all 0.3s ease;
}

.field-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.field-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .field-enter-active,
  .field-leave-active {
    transition: none !important;
  }
}
</style>
