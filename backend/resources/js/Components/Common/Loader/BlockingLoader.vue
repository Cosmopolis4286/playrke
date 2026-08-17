<script setup lang="ts" name="BlockingLoader">
  /**
   * Overlay de pantalla completa que bloquea interacción mientras dura una
   * operación (ej. redirección Inertia). Mismo estilo visual que el overlay
   * de logout (#logout-overlay en auth-layout.blade.php) pero reutilizable
   * y controlado por prop en vez de vivir fijo en el layout Blade.
   */
  withDefaults(defineProps<{
    show: boolean
    text?: string
  }>(), {
    text: 'Cargando…',
  })
</script>

<template>
  <Transition name="blocking-loader-fade">
    <div v-if="show" class="blocking-loader" role="status" aria-live="polite">
      <svg width="52" height="52" viewBox="0 0 50 50" class="blocking-loader-spinner">
        <circle cx="25" cy="25" r="20" fill="none" stroke="var(--ds-border-2)" stroke-width="3" />
        <circle cx="25" cy="25" r="20" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-dasharray="80 126" />
      </svg>
      <p class="blocking-loader-text">{{ text }}</p>
    </div>
  </Transition>
</template>

<style scoped>
.blocking-loader {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 1.25rem;
  background: var(--ds-overlay);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.blocking-loader-spinner {
  animation: blocking-loader-spin 1s linear infinite;
}

.blocking-loader-text {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ds-text-2);
  letter-spacing: 0.04em;
}

@keyframes blocking-loader-spin {
  to { transform: rotate(360deg); }
}

.blocking-loader-fade-enter-active,
.blocking-loader-fade-leave-active {
  transition: opacity 0.15s ease;
}
.blocking-loader-fade-enter-from,
.blocking-loader-fade-leave-to {
  opacity: 0;
}
</style>
