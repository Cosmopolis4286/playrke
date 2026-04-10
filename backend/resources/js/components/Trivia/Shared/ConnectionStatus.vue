<script setup lang="ts">
interface Props {
  status: 'connected' | 'disconnected' | 'reconnecting'
}

defineProps<Props>()

const statusConfig = {
  connected: {
    bg: 'bg-green-500',
    text: 'Conectado',
    icon: '✓',
    show: false // Hide when connected
  },
  disconnected: {
    bg: 'bg-red-500',
    text: 'Desconectado',
    icon: '✕',
    show: true
  },
  reconnecting: {
    bg: 'bg-yellow-500',
    text: 'Reconectando...',
    icon: '↻',
    show: true
  }
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform -translate-y-full opacity-0"
    enter-to-class="transform translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-y-0 opacity-100"
    leave-to-class="transform -translate-y-full opacity-0"
  >
    <div
      v-if="statusConfig[status].show"
      :class="[
        'fixed top-0 left-0 right-0 z-50 px-4 py-2 text-white text-center text-sm font-medium',
        statusConfig[status].bg
      ]"
    >
      <div class="flex items-center justify-center gap-2">
        <span
          v-if="status === 'reconnecting'"
          class="animate-spin"
        >
          {{ statusConfig[status].icon }}
        </span>
        <span v-else>{{ statusConfig[status].icon }}</span>
        <span>{{ statusConfig[status].text }}</span>
      </div>
    </div>
  </Transition>
</template>
