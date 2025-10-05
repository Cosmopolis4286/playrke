<!--
  Componente Modal reutilizable.

  Modal con overlay, animaciones y soporte para
  diferentes tamaños y variantes.
-->

<template>
  <Teleport to="body">
    <Transition
      name="modal-overlay"
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="modal-overlay"
        @click="handleOverlayClick"
      >
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
          <Transition
            name="modal-content"
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div
              v-if="modelValue"
              :class="modalClasses"
              @click.stop
            >
              <!-- Header -->
              <div v-if="$slots.header || title || closable" :class="headerClasses">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <slot name="header">
                      <h3 v-if="title" :class="titleClasses">
                        {{ title }}
                      </h3>
                    </slot>
                  </div>

                  <!-- Botón cerrar -->
                  <button
                    v-if="closable"
                    class="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-muted"
                    @click="close"
                  >
                    <Icon name="lucide:x" class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- Contenido -->
              <div :class="contentClasses">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" :class="footerClasses">
                <slot name="footer" />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
// Props
const props = defineProps({
  // Control de visibilidad
  modelValue: {
    type: Boolean,
    default: false
  },

  // Configuración básica
  title: String,

  // Tamaño del modal
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
  },

  // Variante
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'admin', 'player', 'gaming'].includes(value)
  },

  // Comportamiento
  closable: {
    type: Boolean,
    default: true
  },

  closeOnOverlay: {
    type: Boolean,
    default: true
  },

  persistent: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'close'])

// Computed para clases dinámicas
const modalClasses = computed(() => {
  const classes = [
    'modal-content relative w-full rounded-lg shadow-xl',
    'transform transition-all'
  ]

  // Tamaños
  switch (props.size) {
    case 'xs':
      classes.push('max-w-xs')
      break
    case 'sm':
      classes.push('max-w-sm')
      break
    case 'lg':
      classes.push('max-w-4xl')
      break
    case 'xl':
      classes.push('max-w-6xl')
      break
    case '2xl':
      classes.push('max-w-7xl')
      break
    case 'full':
      classes.push('max-w-full mx-4')
      break
    default:
      classes.push('max-w-md')
  }

  // Variantes
  switch (props.variant) {
    case 'admin':
      classes.push('bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700')
      break
    case 'player':
    case 'gaming':
      classes.push('bg-slate-800 border border-slate-700')
      break
    default:
      classes.push('bg-background border border-border')
  }

  return classes.join(' ')
})

const headerClasses = computed(() => {
  const classes = ['px-6 py-4']

  if (props.variant === 'admin') {
    classes.push('border-b border-slate-200 dark:border-slate-700')
  } else if (props.variant === 'player' || props.variant === 'gaming') {
    classes.push('border-b border-slate-700')
  } else {
    classes.push('border-b border-border')
  }

  return classes.join(' ')
})

const titleClasses = computed(() => {
  const classes = ['text-lg font-semibold']

  if (props.variant === 'gaming' || props.variant === 'player') {
    classes.push('text-white font-gaming')
  } else {
    classes.push('text-foreground')
  }

  return classes.join(' ')
})

const contentClasses = computed(() => {
  return 'px-6 py-4'
})

const footerClasses = computed(() => {
  const classes = ['px-6 py-4 flex justify-end space-x-3']

  if (props.variant === 'admin') {
    classes.push('border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700')
  } else if (props.variant === 'player' || props.variant === 'gaming') {
    classes.push('border-t border-slate-700 bg-slate-900/50')
  } else {
    classes.push('border-t border-border bg-muted/50')
  }

  return classes.join(' ')
})

// Métodos
const close = () => {
  if (!props.persistent) {
    emit('update:modelValue', false)
    emit('close')
  }
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    close()
  }
}

// Gestión de scroll del body
watch(() => props.modelValue, (newValue) => {
  if (process.client) {
    if (newValue) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  }
})

// Limpiar al desmontar
onUnmounted(() => {
  if (process.client) {
    document.body.style.overflow = ''
  }
})

// Gestión de tecla Escape
onMounted(() => {
  const handleEscape = (event) => {
    if (event.key === 'Escape' && props.modelValue && props.closable) {
      close()
    }
  }

  document.addEventListener('keydown', handleEscape)

  onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
  })
})
</script>