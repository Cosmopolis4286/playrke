<!--
  Componente Card reutilizable.

  Contenedor base con variantes para admin y player,
  soporte para header, footer y estados hover.
-->

<template>
  <div :class="cardClasses">
    <!-- Header del card -->
    <div v-if="$slots.header || title" :class="headerClasses">
      <slot name="header">
        <h3 v-if="title" :class="titleClasses">
          {{ title }}
        </h3>
      </slot>
    </div>

    <!-- Contenido principal -->
    <div :class="contentClasses">
      <slot />
    </div>

    <!-- Footer del card -->
    <div v-if="$slots.footer" :class="footerClasses">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  // Variante del card
  variant: {
    type: String,
    default: 'default',
    validator: (value) => [
      'default', 'admin', 'player', 'gaming', 'glass'
    ].includes(value)
  },

  // Título opcional
  title: String,

  // Padding personalizado
  padding: {
    type: String,
    default: 'default',
    validator: (value) => ['none', 'sm', 'default', 'lg'].includes(value)
  },

  // Efectos hover
  hover: {
    type: Boolean,
    default: false
  },

  // Sombra personalizada
  shadow: {
    type: String,
    default: 'default',
    validator: (value) => ['none', 'sm', 'default', 'lg', 'gaming'].includes(value)
  }
})

// Computed para clases dinámicas
const cardClasses = computed(() => {
  const classes = ['card']

  // Variante
  switch (props.variant) {
    case 'admin':
      classes.push('card-admin')
      break
    case 'player':
      classes.push('card-player')
      break
    case 'gaming':
      classes.push('card-gaming')
      break
    case 'glass':
      classes.push('glass')
      break
    default:
      classes.push('bg-card text-card-foreground')
  }

  // Efectos hover
  if (props.hover) {
    classes.push('hover:shadow-lg transition-shadow duration-200 cursor-pointer')
  }

  // Sombras personalizadas
  if (props.shadow !== 'default') {
    switch (props.shadow) {
      case 'none':
        classes.push('shadow-none')
        break
      case 'sm':
        classes.push('shadow-sm')
        break
      case 'lg':
        classes.push('shadow-lg')
        break
      case 'gaming':
        classes.push('shadow-gaming')
        break
    }
  }

  return classes.join(' ')
})

const headerClasses = computed(() => {
  const classes = []

  // Padding del header
  switch (props.padding) {
    case 'none':
      classes.push('px-0 pt-0')
      break
    case 'sm':
      classes.push('px-3 pt-3')
      break
    case 'lg':
      classes.push('px-8 pt-8')
      break
    default:
      classes.push('px-6 pt-6')
  }

  classes.push('pb-3 border-b border-border/10')

  return classes.join(' ')
})

const contentClasses = computed(() => {
  const classes = []

  // Padding del contenido
  switch (props.padding) {
    case 'none':
      classes.push('p-0')
      break
    case 'sm':
      classes.push('p-3')
      break
    case 'lg':
      classes.push('p-8')
      break
    default:
      classes.push('p-6')
  }

  return classes.join(' ')
})

const footerClasses = computed(() => {
  const classes = []

  // Padding del footer
  switch (props.padding) {
    case 'none':
      classes.push('px-0 pb-0')
      break
    case 'sm':
      classes.push('px-3 pb-3')
      break
    case 'lg':
      classes.push('px-8 pb-8')
      break
    default:
      classes.push('px-6 pb-6')
  }

  classes.push('pt-3 border-t border-border/10')

  return classes.join(' ')
})

const titleClasses = computed(() => {
  const classes = ['font-semibold']

  // Estilos según la variante
  if (props.variant === 'gaming' || props.variant === 'player') {
    classes.push('text-lg font-gaming text-white')
  } else {
    classes.push('text-lg text-foreground')
  }

  return classes.join(' ')
})

// Slots
const slots = useSlots()
</script>