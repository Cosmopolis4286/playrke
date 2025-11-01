<template>
  <div :class="containerClasses">
    <!-- Label -->
    <label
      v-if="label"
      :for="inputId"
      :class="labelClasses"
    >
      {{ label }}
      <span
        v-if="required"
        class="text-error-500 ml-1"
      >*</span>
    </label>

    <!-- Select Container -->
    <div
      ref="selectContainer"
      class="relative"
    >
      <!-- Native Select (Hidden but functional for accessibility) -->
      <select
        :id="inputId"
        v-model="internalValue"
        :disabled="disabled"
        :required="required"
        :class="hiddenSelectClasses"
        @change="handleNativeChange"
        @focus="handleFocus"
        @blur="handleBlur"
      >
        <option
          v-if="placeholder"
          value=""
          disabled
        >
          {{ placeholder }}
        </option>
        <option
          v-for="option in processedOptions"
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
        >
          {{ option.label }}
        </option>
      </select>

      <!-- Custom Select Display -->
      <button
        type="button"
        :class="selectButtonClasses"
        :disabled="disabled"
        :aria-expanded="isOpen"
        :aria-haspopup="true"
        :aria-describedby="errorId"
        @click="toggleDropdown"
        @keydown="handleKeyDown"
      >
        <!-- Selected Value Display -->
        <div class="flex items-center flex-1 min-w-0">
          <!-- SharedIcon -->
          <div
            v-if="selectedOption?.icon"
            class="flex-shrink-0 mr-3"
          >
            <component
              :is="selectedOption.icon"
              :class="iconClasses"
            />
          </div>

          <!-- Text Content -->
          <div class="flex-1 min-w-0 text-left">
            <span
              v-if="selectedOption"
              :class="selectedTextClasses"
            >
              {{ selectedOption.label }}
            </span>
            <span
              v-else
              :class="placeholderClasses"
            >
              {{ placeholder || 'Seleccionar opci�n' }}
            </span>

            <!-- Description -->
            <p
              v-if="selectedOption?.description"
              :class="descriptionClasses"
            >
              {{ selectedOption.description }}
            </p>
          </div>
        </div>

        <!-- Arrow SharedIcon -->
        <div :class="arrowContainerClasses">
          <SharedIcon
            name="down"
            :class="arrowIconClasses"
          />
        </div>

        <!-- Loading Indicator -->
        <div
          v-if="loading"
          class="absolute inset-y-0 right-8 flex items-center"
        >
          <div class="w-4 h-4 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin" />
        </div>
      </button>

      <!-- Dropdown -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-95 translate-y-1"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-1"
      >
        <div
          v-if="isOpen"
          :class="dropdownClasses"
          :style="dropdownStyles"
          role="listbox"
          :aria-labelledby="inputId"
        >
          <!-- Search Input -->
          <div
            v-if="searchable"
            class="p-2 border-b border-gray-200 dark:border-gray-600"
          >
            <div class="relative">
              <SharedIcon
                name="search"
                class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              />
              <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                @keydown.esc="closeDropdown"
                @keydown.enter.prevent="selectHighlighted"
                @keydown.arrow-up.prevent="highlightPrevious"
                @keydown.arrow-down.prevent="highlightNext"
              >
            </div>
          </div>

          <!-- Options List -->
          <div :class="optionsContainerClasses">
            <!-- No results -->
            <div
              v-if="filteredOptions.length === 0"
              class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center"
            >
              {{ noResultsText }}
            </div>

            <!-- Options -->
            <div
              v-for="(option, index) in filteredOptions"
              :key="option.value"
              :class="optionClasses(option, index)"
              :aria-selected="option.value === internalValue"
              role="option"
              @click="selectOption(option)"
              @mouseenter="highlightedIndex = index"
            >
              <!-- Option Content -->
              <div class="flex items-center flex-1 min-w-0">
                <!-- SharedIcon -->
                <div
                  v-if="option.icon"
                  class="flex-shrink-0 mr-3"
                >
                  <component
                    :is="option.icon"
                    :class="optionIconClasses(option)"
                  />
                </div>

                <!-- Text Content -->
                <div class="flex-1 min-w-0">
                  <p :class="optionLabelClasses(option)">
                    {{ option.label }}
                  </p>
                  <p
                    v-if="option.description"
                    :class="optionDescriptionClasses"
                  >
                    {{ option.description }}
                  </p>
                </div>
              </div>

              <!-- Selected Indicator -->
              <SharedIcon
                v-if="option.value === internalValue"
                name="check"
                class="w-4 h-4 text-primary-600 dark:text-primary-400 flex-shrink-0"
              />
            </div>
          </div>

          <!-- Custom Footer Slot -->
          <div
            v-if="$slots.footer"
            class="border-t border-gray-200 dark:border-gray-600 p-2"
          >
            <slot name="footer" />
          </div>
        </div>
      </Transition>
    </div>

    <!-- Helper Text -->
    <p
      v-if="helperText && !hasError"
      :class="helperTextClasses"
    >
      {{ helperText }}
    </p>

    <!-- Error Message -->
    <p
      v-if="hasError"
      :id="errorId"
      :class="errorTextClasses"
    >
      {{ errorMessage }}
    </p>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview Select - Componente select premium optimizado v2.0
 * @description Select avanzado con b�squeda, iconos, lazy loading y accessibility AAA
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
// useResponsive auto-imported by Nuxt
import type { PropType } from 'vue'

// Types
interface SelectOption {
  value: string | number
  label: string
  description?: string
  icon?: unknown
  disabled?: boolean
  group?: string
}

interface ProcessedSelectOption extends SelectOption {
  searchText: string
}

type SelectVariant = 'default' | 'premium' | 'minimal' | 'outlined'
type SelectSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

interface Props {
  // Core functionality
  modelValue?: string | number | null
  options?: SelectOption[]
  placeholder?: string
  disabled?: boolean
  required?: boolean
  loading?: boolean

  // Appearance
  variant?: SelectVariant
  size?: SelectSize
  label?: string
  helperText?: string
  error?: string | boolean

  // Search functionality
  searchable?: boolean
  searchPlaceholder?: string
  noResultsText?: string

  // Behavior
  clearable?: boolean
  multiple?: boolean
  maxHeight?: string

  // Validation
  rules?: Array<(value: unknown) => string | boolean>
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  searchPlaceholder: 'Buscar...',
  noResultsText: 'No se encontraron resultados',
  maxHeight: '200px',
  options: () => [],
})

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
  (e: 'change', value: string | number | null, option: SelectOption | ProcessedSelectOption | null): void
  (e: 'focus', event: FocusEvent): void
  (e: 'blur', event: FocusEvent): void
  (e: 'search', query: string): void
}>()

// Composables
const { viewport } = useResponsive()

// Reactive state
const selectContainer = ref<HTMLElement>()
const searchInput = ref<HTMLInputElement>()
const isOpen = ref(false)
const isFocused = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)

// Internal value management
const internalValue = computed({
  get: () => props.modelValue,
  set: (value: string | number | null) => emit('update:modelValue', value),
})

// Unique ID generation
const inputId = `select-${Math.random().toString(36).substr(2, 9)}`
const errorId = `${inputId}-error`

// Process options
const processedOptions = computed((): ProcessedSelectOption[] => props.options.map(option => ({
  ...option,
  searchText: `${option.label} ${option.description || ''}`.toLowerCase(),
})))

// Filter options based on search
const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value.trim()) {
    return processedOptions.value
  }

  const query = searchQuery.value.toLowerCase().trim()
  return processedOptions.value.filter(option =>
    option.searchText.includes(query),
  )
})

// Selected option
const selectedOption = computed(() => processedOptions.value.find(option => option.value === internalValue.value) || null)

// Validation
const validationErrors = computed(() => {
  if (!props.rules) { return [] }

  return props.rules
    .map(rule => rule(internalValue.value))
    .filter(result => typeof result === 'string') as string[]
})

const hasError = computed(() => !!(props.error || validationErrors.value.length > 0))

const errorMessage = computed(() => {
  if (typeof props.error === 'string') { return props.error }
  return validationErrors.value[0] || ''
})

// Styling classes
const containerClasses = computed(() => [
  'w-full',
])

const labelClasses = computed(() => [
  'block text-sm font-medium mb-2',
  hasError.value ? 'text-error-600 dark:text-error-400' : 'text-gray-700 dark:text-gray-300',
])

const hiddenSelectClasses = computed(() => [
  'sr-only opacity-0 absolute',
])

const selectButtonClasses = computed(() => {
  const baseClasses = [
    'relative w-full text-left cursor-pointer',
    'transition-all duration-200 ease-out',
    'transform-gpu will-change-transform',
    'focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500',
  ]

  const sizeClasses = {
    xs: 'px-2 py-1 text-xs',
    sm: 'px-3 py-2 text-sm',
    md: 'px-4 py-3 text-base',
    lg: 'px-5 py-4 text-lg',
    xl: 'px-6 py-5 text-xl',
  }

  const variantClasses = {
    default: [
      'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl',
      'hover:border-primary-400 dark:hover:border-primary-500',
      hasError.value ? 'border-error-500 dark:border-error-400' : '',
      isOpen.value ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-800' : '',
    ],
    premium: [
      'bg-gradient-to-br from-white to-primary-50/30 dark:from-gray-800 dark:to-gray-700',
      'border border-primary-200/60 dark:border-primary-700/60 rounded-2xl',
      'backdrop-blur-sm shadow-blue-soft',
      'hover:shadow-blue-medium hover:border-primary-400/80',
      hasError.value ? 'border-error-400 shadow-error-soft' : '',
      isOpen.value ? 'border-primary-500 shadow-blue-medium ring-2 ring-primary-200' : '',
    ],
    minimal: [
      'bg-transparent border-b-2 border-gray-300 dark:border-gray-600 rounded-none',
      'hover:border-primary-500',
      hasError.value ? 'border-error-500' : '',
      isOpen.value ? 'border-primary-600' : '',
    ],
    outlined: [
      'bg-transparent border-2 border-gray-300 dark:border-gray-600 rounded-xl',
      'hover:border-primary-500 hover:bg-primary-50/30 dark:hover:bg-primary-900/20',
      hasError.value ? 'border-error-500' : '',
      isOpen.value ? 'border-primary-600 bg-primary-50/30' : '',
    ],
  }

  const stateClasses = []
  if (props.disabled) {
    stateClasses.push('opacity-50 cursor-not-allowed')
  } else {
    stateClasses.push('hover:scale-[1.01]')
  }

  return [
    ...baseClasses,
    sizeClasses[props.size],
    ...variantClasses[props.variant],
    ...stateClasses,
  ].join(' ')
})

const iconClasses = computed(() => [
  'w-5 h-5 text-gray-500 dark:text-gray-400',
])

const selectedTextClasses = computed(() => [
  'block truncate font-medium',
  hasError.value ? 'text-error-700 dark:text-error-300' : 'text-gray-900 dark:text-white',
])

const placeholderClasses = computed(() => [
  'block truncate text-gray-500 dark:text-gray-400',
])

const descriptionClasses = computed(() => [
  'text-xs text-gray-500 dark:text-gray-400 mt-1 truncate',
])

const arrowContainerClasses = computed(() => [
  'flex items-center pr-2',
])

const arrowIconClasses = computed(() => [
  'w-5 h-5 text-gray-400 dark:text-gray-500 transition-transform duration-200',
  isOpen.value ? 'rotate-180' : '',
])

const dropdownClasses = computed(() => [
  'absolute z-50 w-full mt-1 overflow-hidden',
  'bg-white dark:bg-gray-800',
  'border border-gray-200 dark:border-gray-600',
  'rounded-xl shadow-xl',
  'backdrop-blur-md',
  'transform-gpu will-change-transform',
])

const dropdownStyles = computed(() => ({
  maxHeight: props.maxHeight,
}))

const optionsContainerClasses = computed(() => [
  'max-h-48 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent',
])

const optionClasses = (option: SelectOption, index: number) => [
  'flex items-center justify-between px-4 py-3 cursor-pointer',
  'transition-all duration-150 ease-out',
  'hover:bg-primary-50 dark:hover:bg-primary-900/20',
  option.disabled ? 'opacity-50 cursor-not-allowed' : '',
  option.value === internalValue.value ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white',
  index === highlightedIndex.value ? 'bg-primary-50 dark:bg-primary-900/20' : '',
].join(' ')

const optionIconClasses = (option: SelectOption) => [
  'w-5 h-5',
  option.value === internalValue.value ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400',
]

const optionLabelClasses = (option: SelectOption) => [
  'font-medium truncate',
  option.value === internalValue.value ? 'text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white',
]

const optionDescriptionClasses = computed(() => [
  'text-xs text-gray-500 dark:text-gray-400 truncate mt-1',
])

const helperTextClasses = computed(() => [
  'mt-2 text-xs text-gray-500 dark:text-gray-400',
])

const errorTextClasses = computed(() => [
  'mt-2 text-xs text-error-600 dark:text-error-400 flex items-center',
])

// Methods
const toggleDropdown = () => {
  if (props.disabled) { return }

  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

const openDropdown = async () => {
  isOpen.value = true
  highlightedIndex.value = filteredOptions.value.findIndex(
    option => option.value === internalValue.value,
  )

  if (props.searchable) {
    await nextTick()
    searchInput.value?.focus()
  }
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

const selectOption = (option: ProcessedSelectOption) => {
  if (option.disabled) { return }

  internalValue.value = option.value
  emit('change', option.value, option)
  closeDropdown()
}

const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
    const option = filteredOptions.value[highlightedIndex.value]
    if (option) {
      selectOption(option)
    }
  }
}

const highlightNext = () => {
  highlightedIndex.value = Math.min(
    highlightedIndex.value + 1,
    filteredOptions.value.length - 1,
  )
}

const highlightPrevious = () => {
  highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
}

const handleNativeChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const option = processedOptions.value.find(opt => opt.value.toString() === target.value)
  if (option) {
    emit('change', option.value, option)
  }
}

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false
  emit('blur', event)
}

const handleKeyDown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault()
      toggleDropdown()
      break
    case 'Escape':
      closeDropdown()
      break
    case 'ArrowDown':
      event.preventDefault()
      if (!isOpen.value) {
        openDropdown()
      } else {
        highlightNext()
      }
      break
    case 'ArrowUp':
      event.preventDefault()
      if (!isOpen.value) {
        openDropdown()
      } else {
        highlightPrevious()
      }
      break
  }
}

const handleClickOutside = (event: Event) => {
  if (selectContainer.value && !selectContainer.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

// Watchers
watch(searchQuery, (newQuery) => {
  highlightedIndex.value = -1
  emit('search', newQuery)
})

watch(isOpen, (newIsOpen) => {
  if (newIsOpen) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})

// Lifecycle
onMounted(() => {
  // Initialize any required setup
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Custom scrollbar for dropdown */
.scrollbar-thin {
  scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(209 213 219);
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background-color: rgb(156 163 175);
}

/* Dark mode scrollbar */
.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(75 85 99);
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background-color: rgb(107 114 128);
}

/* Performance optimizations */
.transform-gpu {
  transform: translateZ(0);
}

.will-change-transform {
  will-change: transform;
}

/* Focus styles */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Backdrop blur enhancement */
.backdrop-blur-md {
  backdrop-filter: blur(12px);
}

.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .transition-all,
  .transition-transform,
  .hover\:scale-\[1\.01\]:hover {
    transition: none !important;
    transform: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .backdrop-blur-md,
  .backdrop-blur-sm {
    backdrop-filter: none !important;
  }

  .shadow-blue-soft,
  .shadow-blue-medium,
  .shadow-xl {
    box-shadow: 0 0 0 2px currentColor !important;
  }
}

/* Print styles */
@media print {
  .absolute,
  .z-50,
  .shadow-xl,
  .backdrop-blur-md {
    display: none !important;
  }
}
</style>
