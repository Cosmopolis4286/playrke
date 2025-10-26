<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
    <div class="flex flex-col items-center space-y-4">
      <div class="relative h-20 w-20">
        <div class="absolute inset-0 rounded-full border-4 border-primary/20"></div>
        <div class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-primary"></div>
      </div>
      <p class="text-sm text-muted-foreground">Cargando...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Indicador de carga global para la aplicación
 */
</script>
