<!--
  Grupo colapsible para Sidebar.

  Contenedor de elementos de menú con funcionalidad
  de colapsar/expandir y animaciones suaves.
-->

<template>
  <div class="sidebar-group">
    <!-- Botón toggle del grupo -->
    <button
      type="button"
      :class="buttonClasses"
      @click="toggle"
    >
      <Icon
        name="lucide:folder"
        :class="iconClasses"
      />
      <span class="flex-1 text-left truncate">{{ label }}</span>
      <Icon
        name="lucide:chevron-right"
        :class="chevronClasses"
      />
    </button>

    <!-- Contenido colapsible -->
    <Transition
      name="sidebar-group"
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="isOpen" class="overflow-hidden">
        <div class="mt-1 space-y-1 pl-8">
          <slot />
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  // Configuración
  label: {
    type: String,
    required: true
  },

  // Estado inicial
  open: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['toggle'])

// Estado interno
const isOpen = ref(props.open)

// Watchers
watch(() => props.open, (newValue) => {
  isOpen.value = newValue
})

// Computed
const buttonClasses = computed(() => {
  return [
    'group w-full flex items-center px-3 py-2 text-left text-sm font-medium rounded-md',
    'text-slate-600 dark:text-slate-400',
    'hover:bg-slate-100 dark:hover:bg-slate-700',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
    'transition-colors'
  ].join(' ')
})

const iconClasses = computed(() => {
  return [
    'mr-3 flex-shrink-0 w-4 h-4',
    'text-slate-400 group-hover:text-slate-500',
    'dark:text-slate-500 dark:group-hover:text-slate-400'
  ].join(' ')
})

const chevronClasses = computed(() => {
  return [
    'ml-auto flex-shrink-0 w-4 h-4 transition-transform duration-200',
    'text-slate-400 group-hover:text-slate-500',
    'dark:text-slate-500 dark:group-hover:text-slate-400',
    isOpen.value ? 'rotate-90' : ''
  ].join(' ')
})

// Métodos
const toggle = () => {
  isOpen.value = !isOpen.value
  emit('toggle', isOpen.value)
}
</script>

<style scoped>
.sidebar-group-enter-active,
.sidebar-group-leave-active {
  overflow: hidden;
}

.sidebar-group-enter-from,
.sidebar-group-leave-to {
  max-height: 0;
  opacity: 0;
}

.sidebar-group-enter-to,
.sidebar-group-leave-from {
  max-height: 24rem; /* max-h-96 */
  opacity: 1;
}
</style>