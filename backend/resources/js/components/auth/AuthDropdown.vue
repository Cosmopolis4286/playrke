<template>
  <div class="auth__form-item" :class="itemClasses">
    <div class="auth__dropdown" ref="dropdownRef">
      <!-- Left Icon -->
      <div v-if="leftIcon" class="auth__dropdown-icon auth__dropdown-icon--left" :style="iconStyle">
        <i :class="buildIconClass(getLeftIcon(), 'transition-all duration-200')"></i>
      </div>

      <!-- Trigger -->
      <button
        type="button"
        :id="triggerId"
        class="auth__dropdown-trigger"
        :class="triggerClasses"
        :aria-expanded="isOpen"
        :aria-haspopup="true"
        :disabled="disabled"
        @click="toggleDropdown"
        @keydown.escape="closeDropdown"
        @keydown.enter.prevent="toggleDropdown"
        @keydown.space.prevent="toggleDropdown"
        @keydown.arrow-down.prevent="focusFirstOption"
        @keydown.arrow-up.prevent="focusLastOption"
      >
        <!-- Selected Option Display -->
        <div class="auth__dropdown-trigger-content">
          <!-- Selected Icon/Flag -->
          <div v-if="selectedOption && (selectedOption.icon || selectedOption.flag)" class="auth__dropdown-trigger-indicator">
            <i v-if="selectedOption.icon" :class="selectedOption.icon"></i>
            <span v-else-if="selectedOption.flag" class="auth__dropdown-trigger-flag">{{ selectedOption.flag }}</span>
          </div>

          <!-- Selected Text -->
          <span class="auth__dropdown-trigger-text">
            {{ selectedLabel || placeholder }}
          </span>
        </div>

        <!-- Right Icons -->
        <div class="auth__dropdown-icons-right">
          <!-- Loading Spinner -->
          <div v-if="loading" class="auth__dropdown-loading">
            <i :class="buildIconClass(getIcon('loader'), 'animate-spin')"></i>
          </div>

          <!-- Chevron -->
          <div v-else class="auth__dropdown-chevron" :class="{ 'auth__dropdown-chevron--open': isOpen }">
            <i :class="buildIconClass(getIcon('chevronDown'))" :style="chevronStyle"></i>
          </div>
        </div>
      </button>

      <!-- Hidden Input -->
      <input
        type="hidden"
        :name="name"
        :value="selectedValue"
        :required="required"
      />

      <!-- Dropdown List -->
      <transition name="dropdown">
        <div
          v-show="isOpen"
          class="auth__dropdown-list"
          :class="listClasses"
          role="listbox"
          :aria-labelledby="triggerId"
        >
          <!-- Search Input -->
          <div v-if="searchable" class="auth__dropdown-search">
            <div class="auth__dropdown-search-icon">
              <i :class="buildIconClass(getIcon('search'))"></i>
            </div>
            <input
              ref="searchRef"
              v-model="searchQuery"
              type="text"
              class="auth__dropdown-search-input"
              :placeholder="searchPlaceholder"
              @keydown.escape="closeDropdown"
              @keydown.enter.prevent="selectCurrentOption"
              @keydown.arrow-down.prevent="focusNextOption"
              @keydown.arrow-up.prevent="focusPreviousOption"
            />
            <!-- Clear Search -->
            <button
              v-if="searchQuery"
              type="button"
              class="auth__dropdown-search-clear"
              @click="searchQuery = ''"
            >
              <i :class="buildIconClass(getIcon('x'))"></i>
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loading && !searchable" class="auth__dropdown-loading-state">
            <i :class="buildIconClass(getIcon('loader'), 'animate-spin')"></i>
            <span>Cargando...</span>
          </div>

          <!-- Options -->
          <div v-else-if="filteredOptions.length > 0" class="auth__dropdown-options">
            <button
              v-for="(option, index) in filteredOptions"
              :key="getOptionKey(option)"
              type="button"
              class="auth__dropdown-option"
              :class="getOptionClasses(option, index)"
              :aria-selected="isSelected(option)"
              role="option"
              @click="selectOption(option)"
              @mouseenter="hoveredIndex = index"
              @mouseleave="hoveredIndex = -1"
            >
              <!-- Option Leading Content -->
              <div class="auth__dropdown-option-leading">
                <!-- Option Icon -->
                <div v-if="option.icon" class="auth__dropdown-option-icon">
                  <i :class="option.icon"></i>
                </div>

                <!-- Option Flag (for countries) -->
                <div v-else-if="option.flag" class="auth__dropdown-option-flag">
                  {{ option.flag }}
                </div>

                <!-- Default Icon -->
                <div v-else-if="showDefaultIcon" class="auth__dropdown-option-icon">
                  <i :class="buildIconClass(getIcon('help'))"></i>
                </div>
              </div>

              <!-- Option Content -->
              <div class="auth__dropdown-option-content">
                <span class="auth__dropdown-option-text">{{ getOptionLabel(option) }}</span>
                <span v-if="option.description" class="auth__dropdown-option-description">
                  {{ option.description }}
                </span>
              </div>

              <!-- Option Trailing Content -->
              <div class="auth__dropdown-option-trailing">
                <!-- Selected Indicator -->
                <div v-if="isSelected(option)" class="auth__dropdown-option-check">
                  <i :class="buildIconClass(getValidationIcon('valid'))" :style="buildIconStyle(getValidationIcon('valid'))"></i>
                </div>

                <!-- Option Badge -->
                <div v-else-if="option.badge" class="auth__dropdown-option-badge">
                  {{ option.badge }}
                </div>
              </div>
            </button>
          </div>

          <!-- No Results -->
          <div v-else class="auth__dropdown-empty">
            <div class="auth__dropdown-empty-icon">
              <i :class="buildIconClass(searchQuery ? getIcon('search') : getIcon('info'))"></i>
            </div>
            <div class="auth__dropdown-empty-content">
              <p class="auth__dropdown-empty-title">
                {{ searchQuery ? 'No se encontraron resultados' : 'No hay opciones disponibles' }}
              </p>
              <p v-if="searchQuery" class="auth__dropdown-empty-subtitle">
                Intenta con otros términos de búsqueda
              </p>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Error Message -->
    <transition name="slide-down">
      <div v-if="error" class="auth__error">
        <i :class="buildIconClass(getValidationIcon('invalid'))" :style="buildIconStyle(getValidationIcon('invalid'))"></i>
        <span>{{ error }}</span>
      </div>
    </transition>

    <!-- Help Text -->
    <transition name="slide-down">
      <div v-if="helpText && !error" class="auth__help-text">
        <i :class="buildIconClass(getIcon('info'))"></i>
        <span>{{ helpText }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useIcons } from '../../composables/useIcons'

const { getIcon, getValidationIcon, buildIconClass, buildIconStyle } = useIcons()

// Types
interface Option {
  id: string | number
  name: string
  label?: string
  icon?: string
  flag?: string
  disabled?: boolean
  [key: string]: any
}

interface Props {
  modelValue?: string | number | null
  options: Option[]
  placeholder?: string
  searchPlaceholder?: string
  leftIcon?: string
  name?: string
  required?: boolean
  disabled?: boolean
  searchable?: boolean
  loading?: boolean
  error?: string
  helpText?: string
  maxHeight?: string
  valueKey?: string
  labelKey?: string
  variant?: 'default' | 'filled' | 'outlined'
  size?: 'sm' | 'md' | 'lg'
  showDefaultIcon?: boolean
  animate?: boolean
}

// Props
const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Seleccionar...',
  searchPlaceholder: 'Buscar...',
  leftIcon: '',
  name: '',
  required: false,
  disabled: false,
  searchable: true,
  loading: false,
  error: '',
  helpText: '',
  maxHeight: '240px',
  valueKey: 'id',
  labelKey: 'name',
  variant: 'default',
  size: 'md',
  showDefaultIcon: false,
  animate: true,
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  'select': [option: Option]
  'open': []
  'close': []
  'search': [query: string]
}>()

// State
const dropdownRef = ref<HTMLElement>()
const searchRef = ref<HTMLInputElement>()
const isOpen = ref(false)
const searchQuery = ref('')
const hoveredIndex = ref(-1)

// Computed
const triggerId = computed(() => `dropdown-trigger-${Math.random().toString(36).substr(2, 9)}`)

const selectedValue = computed(() => props.modelValue)

const selectedOption = computed(() => {
  return props.options.find(option => getOptionValue(option) === selectedValue.value)
})

const selectedLabel = computed(() => {
  return selectedOption.value ? getOptionLabel(selectedOption.value) : ''
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options

  const query = searchQuery.value.toLowerCase()
  return props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase()
    return label.includes(query)
  })
})

const triggerClasses = computed(() => {
  const classes = []

  if (isOpen.value) classes.push('auth__dropdown-trigger--open')
  if (props.error) classes.push('auth__dropdown-trigger--error')
  if (selectedValue.value) classes.push('auth__dropdown-trigger--selected')
  if (props.disabled) classes.push('auth__dropdown-trigger--disabled')

  classes.push(`auth__dropdown-trigger--${props.variant}`)
  classes.push(`auth__dropdown-trigger--${props.size}`)

  if (props.leftIcon) classes.push('auth__dropdown-trigger--with-left-icon')
  if (props.animate) classes.push('auth__dropdown-trigger--animated')

  return classes
})

const listClasses = computed(() => {
  const classes = []
  if (props.searchable) classes.push('auth__dropdown-list--searchable')
  return classes
})

const itemClasses = computed(() => {
  const classes = []

  if (props.error) classes.push('auth__form-item--error')
  if (selectedValue.value) classes.push('auth__form-item--selected')
  if (isOpen.value) classes.push('auth__form-item--focused')
  if (props.loading) classes.push('auth__form-item--loading')

  classes.push(`auth__form-item--${props.variant}`)
  classes.push(`auth__form-item--${props.size}`)

  if (props.animate) classes.push('auth__form-item--animated')

  return classes
})

const getLeftIcon = () => {
  if (props.leftIcon) {
    return getIcon(props.leftIcon as any)
  }
  return null
}

const iconStyle = computed(() => {
  const icon = getLeftIcon()
  if (!icon) return {}

  const style = buildIconStyle(icon)

  if (props.animate && (isOpen.value || selectedValue.value)) {
    style.transform = 'scale(1.1)'
    style.color = 'var(--color-primary-electric)'
  }

  return style
})

const chevronStyle = computed(() => {
  const icon = getIcon('chevronDown')
  const style = buildIconStyle(icon)

  if (isOpen.value) {
    style.transform = 'rotate(180deg)'
    style.color = 'var(--color-primary-electric)'
  }

  return style
})

// Methods
const getOptionValue = (option: Option) => {
  return option[props.valueKey] ?? option.id
}

const getOptionLabel = (option: Option) => {
  return option[props.labelKey] ?? option.label ?? option.name
}

const getOptionKey = (option: Option) => {
  return `option-${getOptionValue(option)}`
}

const isSelected = (option: Option) => {
  return getOptionValue(option) === selectedValue.value
}

const getOptionClasses = (option: Option, index: number) => {
  const classes = []
  if (isSelected(option)) classes.push('auth__dropdown-option--selected')
  if (option.disabled) classes.push('auth__dropdown-option--disabled')
  if (hoveredIndex.value === index) classes.push('auth__dropdown-option--hovered')
  return classes
}

const toggleDropdown = () => {
  if (props.disabled) return

  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

const openDropdown = async () => {
  if (props.disabled) return

  isOpen.value = true
  emit('open')

  await nextTick()

  if (props.searchable && searchRef.value) {
    searchRef.value.focus()
  }
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
  hoveredIndex.value = -1
  emit('close')
}

const selectOption = (option: Option) => {
  if (option.disabled) return

  const value = getOptionValue(option)
  emit('update:modelValue', value)
  emit('select', option)
  closeDropdown()
}

const selectCurrentOption = () => {
  const option = filteredOptions.value[hoveredIndex.value]
  if (option && !option.disabled) {
    selectOption(option)
  }
}

const focusFirstOption = () => {
  hoveredIndex.value = 0
}

const focusLastOption = () => {
  hoveredIndex.value = filteredOptions.value.length - 1
}

const focusNextOption = () => {
  if (hoveredIndex.value < filteredOptions.value.length - 1) {
    hoveredIndex.value++
  }
}

const focusPreviousOption = () => {
  if (hoveredIndex.value > 0) {
    hoveredIndex.value--
  }
}

const handleClickOutside = (event: Event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

// Watch for search changes
watch(searchQuery, (newQuery) => {
  emit('search', newQuery)
  hoveredIndex.value = -1
})

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Dropdown container */
.auth__dropdown {
  @apply relative w-full;
}

/* Trigger */
.auth__dropdown-trigger {
  @apply w-full h-12 px-4 pl-12 pr-10 text-sm text-left rounded-xl cursor-pointer transition-all;
  @apply flex items-center justify-between;
  background: var(--color-bg-input);
  border: 2px solid var(--color-border-secondary);
  color: var(--color-text-secondary);
}

.auth__dropdown-trigger:hover {
  border-color: var(--color-border-focus);
}

.auth__dropdown-trigger--open {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.15);
}

.auth__dropdown-trigger--error {
  border-color: var(--color-border-error);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.auth__dropdown-trigger--selected {
  color: var(--color-text-primary);
}

.auth__dropdown-trigger--disabled {
  @apply opacity-50 cursor-not-allowed;
}

.auth__dropdown-trigger-text {
  @apply truncate;
}

/* Chevron */
.auth__dropdown-chevron {
  @apply transition-transform duration-200;
}

.auth__dropdown-chevron--open {
  @apply transform rotate-180;
}

/* Dropdown List */
.auth__dropdown-list {
  @apply absolute top-full left-0 right-0 mt-2 py-2 rounded-xl shadow-lg z-50;
  background: var(--color-bg-card);
  border: 2px solid var(--color-border-secondary);
  backdrop-filter: blur(20px);
  max-height: v-bind('props.maxHeight');
  overflow-y: auto;
}

.auth__dropdown-list--searchable {
  @apply pt-0;
}

/* Search */
.auth__dropdown-search {
  @apply relative p-3 border-b border-gray-600;
}

.auth__dropdown-search-icon {
  @apply absolute left-6 top-1/2 transform -translate-y-1/2 text-gray-400;
}

.auth__dropdown-search-input {
  @apply w-full pl-8 pr-4 py-2 text-sm rounded-lg border border-gray-600 bg-gray-700 text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

/* Options Container */
.auth__dropdown-options {
  @apply max-h-48 overflow-y-auto;
}

/* Option */
.auth__dropdown-option {
  @apply w-full px-4 py-3 text-sm text-left cursor-pointer transition-all;
  @apply flex items-center space-x-3;
  color: var(--color-text-secondary);
}

.auth__dropdown-option:hover,
.auth__dropdown-option--hovered {
  background: rgba(0, 102, 255, 0.1);
  color: var(--color-text-primary);
}

.auth__dropdown-option--selected {
  background: rgba(0, 102, 255, 0.15);
  color: var(--color-primary);
}

.auth__dropdown-option--disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Option elements */
.auth__dropdown-option-icon,
.auth__dropdown-option-flag {
  @apply flex-shrink-0;
}

.auth__dropdown-option-flag {
  @apply text-lg;
}

.auth__dropdown-option-text {
  @apply flex-1 truncate;
}

.auth__dropdown-option-check {
  @apply flex-shrink-0 text-green-500;
}

/* Loading and Empty states */
.auth__dropdown-loading,
.auth__dropdown-empty {
  @apply px-4 py-3 text-sm text-center;
  color: var(--color-text-muted);
}

/* Transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease-out;
  transform-origin: top;
}

.dropdown-enter-from {
  opacity: 0;
  transform: scaleY(0.8) translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: scaleY(0.8) translateY(-10px);
}

/* Slide down transition for error */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* Scrollbar styling */
.auth__dropdown-options::-webkit-scrollbar {
  width: 4px;
}

.auth__dropdown-options::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
}

.auth__dropdown-options::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
}

.auth__dropdown-options::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
</style>