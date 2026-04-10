<script setup lang="ts">
interface Props {
  message?: string
}

defineProps<Props>()
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl p-8 text-center max-w-sm mx-4">
      <!-- Spinner -->
      <div class="relative w-16 h-16 mx-auto mb-4">
        <div class="absolute inset-0 border-4 border-blue-100 rounded-full" />
        <div class="absolute inset-0 border-4 border-blue-600 rounded-full border-t-transparent animate-spin" />
      </div>

      <!-- Message -->
      <p class="text-lg font-medium text-gray-900">
        {{ message || 'Cargando...' }}
      </p>

      <!-- Subtext -->
      <p class="text-sm text-gray-500 mt-2">
        Por favor espera un momento
      </p>
    </div>
  </div>
</template>
