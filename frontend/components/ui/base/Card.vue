<template>
  <article
    :class="cardClasses"
    :role="role"
    v-bind="$attrs"
    @click="handleClick"
  >
    <!-- Header Section -->
    <header
      v-if="hasHeader"
      :class="headerClasses"
    >
      <slot name="header">
        <div
          v-if="title || subtitle || hasHeaderSlots"
          class="flex items-start justify-between"
        >
          <div class="flex-1">
            <!-- Title with optional icon -->
            <div
              v-if="title"
              class="flex items-center gap-3"
            >
              <slot name="icon" />
              <h3 class="heading-4">
                {{ title }}
              </h3>
            </div>

            <!-- Subtitle -->
            <p
              v-if="subtitle"
              class="body-small mt-1"
            >
              {{ subtitle }}
            </p>
          </div>

          <!-- Header actions -->
          <div
            v-if="$slots.actions"
            class="flex items-center gap-2 ml-4"
          >
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </header>

    <!-- Main Content -->
    <main :class="bodyClasses">
      <slot />
    </main>

    <!-- Footer Section -->
    <footer
      v-if="$slots.footer"
      :class="footerClasses"
    >
      <slot name="footer" />
    </footer>

    <!-- Loading Overlay -->
    <Transition
      enter-active-class="transition-all duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="loading"
        class="absolute inset-0 bg-white/90 backdrop-blur-sm flex items-center justify-center z-20 rounded-xl"
        role="status"
        aria-live="polite"
        :aria-label="loadingText"
      >
        <div class="flex flex-col items-center gap-3">
          <div class="loading-spinner w-6 h-6 text-primary-600" />
          <span
            v-if="loadingText"
            class="body-small text-primary-600 font-medium"
          >
            {{ loadingText }}
          </span>
        </div>
      </div>
    </Transition>
  </article>
</template>

<script setup lang="ts">
/**
 * @fileoverview Card component - Tarjeta optimizada con sistema @apply
 * @description Componente Card simplificado usando clases predefinidas
 */
import { computed, useSlots } from 'vue'

interface Props {
  /** Título de la tarjeta */
  title?: string
  /** Subtítulo de la tarjeta */
  subtitle?: string
  /** Variante visual */
  variant?: 'base' | 'interactive' | 'elevated' | 'floating' | 'glass'
  /** Estado de carga */
  loading?: boolean
  /** Texto de carga */
  loadingText?: string
  /** Tarjeta interactiva (clickeable) */
  interactive?: boolean
  /** Rol de accesibilidad */
  role?: string
}

interface Emits {
  /** Evento de click */
  click: [event: MouseEvent]
}

defineOptions({
  name: 'Card',
  inheritAttrs: false,
})

const props = withDefaults(defineProps<Props>(), {
  variant: 'base',
  loadingText: 'Cargando...',
  role: 'article',
})

const emit = defineEmits<Emits>()
const slots = useSlots()

// Computed properties
const hasHeader = computed(() =>
  props.title || props.subtitle || slots.header || slots.icon || slots.actions,
)

const hasHeaderSlots = computed(() =>
  slots.header || slots.icon || slots.actions,
)

const cardClasses = computed(() => {
  const classes = []

  // Añadir clase base según variante
  if (props.variant === 'glass') {
    classes.push('glass-card')
  } else {
    classes.push('card-base')
    // Añadir variante específica para no-glass variants
    if (props.variant !== 'base') {
      classes.push(`card-${props.variant}`)
    }
  }

  // Estados
  if (props.interactive) {
    classes.push('cursor-pointer')
  }

  return classes.join(' ')
})

const headerClasses = computed(() => 'card-header')
const bodyClasses = computed(() => 'card-body')
const footerClasses = computed(() => 'card-footer')

// Event handlers
const handleClick = (event: MouseEvent) => {
  if (props.loading) { return }

  if (props.interactive) {
    emit('click', event)
  }
}
</script>
