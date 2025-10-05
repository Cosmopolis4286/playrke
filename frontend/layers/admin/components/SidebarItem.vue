<!--
  Item de navegación para Sidebar.

  Elemento individual del menú con estado activo,
  verificación de permisos y soporte para iconos.
-->

<template>
  <NuxtLink
    v-if="hasAccess"
    :to="to"
    :class="linkClasses"
    :exact="exact"
  >
    <Icon
      :name="icon"
      :class="iconClasses"
    />
    <span class="truncate">{{ label }}</span>

    <!-- Badge opcional -->
    <span
      v-if="badge"
      class="ml-auto px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full"
    >
      {{ badge }}
    </span>
  </NuxtLink>
</template>

<script setup>
// Props
const props = defineProps({
  // Navegación
  to: {
    type: String,
    required: true
  },

  // Contenido
  icon: {
    type: String,
    required: true
  },

  label: {
    type: String,
    required: true
  },

  // Configuración
  exact: {
    type: Boolean,
    default: false
  },

  // Permisos
  permission: String,
  role: String,

  // Badge opcional
  badge: [String, Number]
})

// Composables
const { hasPermission, hasRole } = useAuth()

// Computed
const hasAccess = computed(() => {
  // Si no se especifica permiso ni rol, permitir acceso
  if (!props.permission && !props.role) {
    return true
  }

  // Verificar permiso
  if (props.permission && !hasPermission(props.permission)) {
    return false
  }

  // Verificar rol
  if (props.role && !hasRole(props.role)) {
    return false
  }

  return true
})

const linkClasses = computed(() => {
  return [
    'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
    'hover:bg-slate-100 dark:hover:bg-slate-700',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
    'text-slate-700 dark:text-slate-300',
    'router-link-active:bg-blue-50 router-link-active:text-blue-700',
    'dark:router-link-active:bg-blue-900/50 dark:router-link-active:text-blue-200'
  ].join(' ')
})

const iconClasses = computed(() => {
  return [
    'mr-3 flex-shrink-0 w-5 h-5',
    'text-slate-400 group-hover:text-slate-500',
    'dark:text-slate-500 dark:group-hover:text-slate-400',
    'router-link-active:text-blue-500',
    'dark:router-link-active:text-blue-400'
  ].join(' ')
})
</script>