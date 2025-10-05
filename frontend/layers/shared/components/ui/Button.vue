<!--
  Componente Button reutilizable.

  Botón base con variantes para admin y player,
  soporte para iconos, estados de carga y accesibilidad.
-->

<template>
  <component
    :is="tag"
    :class="buttonClasses"
    :disabled="disabled || loading"
    v-bind="$attrs"
    @click="handleClick"
  >
    <!-- Icono izquierdo -->
    <Icon
      v-if="iconLeft && !loading"
      :name="iconLeft"
      :class="iconClasses"
    />

    <!-- Spinner de carga -->
    <Icon
      v-if="loading"
      name="lucide:loader-2"
      class="w-4 h-4 animate-spin"
      :class="{ 'mr-2': $slots.default }"
    />

    <!-- Contenido del botón -->
    <slot />

    <!-- Icono derecho -->
    <Icon
      v-if="iconRight && !loading"
      :name="iconRight"
      :class="iconClasses"
    />
  </component>
</template>

<script setup>
// Props
const props = defineProps({
  // Variante del botón
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => [
      'primary', 'secondary', 'ghost', 'destructive',
      'gaming', 'admin', 'player', 'neon'
    ].includes(value)
  },

  // Tamaño del botón
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },

  // Elemento HTML a renderizar
  tag: {
    type: String,
    default: 'button'
  },

  // Estados
  disabled: {
    type: Boolean,
    default: false
  },

  loading: {
    type: Boolean,
    default: false
  },

  // Iconos
  iconLeft: String,
  iconRight: String,

  // Ancho completo
  block: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['click'])

// Computed para clases dinámicas
const buttonClasses = computed(() => {
  const classes = [
    'btn', // Clase base
    `btn-${props.variant}`, // Variante
    `btn-${props.size}`, // Tamaño
  ]

  // Modificadores
  if (props.block) classes.push('w-full')
  if (props.loading) classes.push('loading')
  if (props.disabled) classes.push('opacity-50 cursor-not-allowed')

  return classes.join(' ')
})

const iconClasses = computed(() => {
  const classes = ['w-4 h-4']

  // Espaciado si hay contenido
  if (props.iconLeft && (slots.default?.() || props.iconRight)) {
    classes.push('mr-2')
  }
  if (props.iconRight && (slots.default?.() || props.iconLeft)) {
    classes.push('ml-2')
  }

  return classes.join(' ')
})

// Slots
const slots = useSlots()

// Métodos
const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
.btn {
  @apply inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors;
  @apply focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2;
  @apply disabled:opacity-50 disabled:pointer-events-none ring-offset-background;
}

/* Variantes de tamaño */
.btn-xs {
  @apply h-7 px-2 text-xs;
}

.btn-sm {
  @apply h-8 px-3 text-sm;
}

.btn-md {
  @apply h-10 px-4 py-2;
}

.btn-lg {
  @apply h-11 px-8 text-lg;
}

.btn-xl {
  @apply h-12 px-10 text-xl;
}

/* Variantes específicas ya definidas en main.css */
</style>