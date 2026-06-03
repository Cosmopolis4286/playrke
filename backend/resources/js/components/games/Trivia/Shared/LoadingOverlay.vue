<script setup lang="ts">
interface Props {
  message?: string
}

defineProps<Props>()
</script>

<template>
  <Transition name="fade-overlay" appear>
    <div class="loading-overlay">
      <div class="loading-overlay__spinner">
        <svg viewBox="0 0 50 50" class="loading-overlay__svg">
          <circle cx="25" cy="25" r="20" fill="none" stroke-width="3" class="loading-overlay__track" />
          <circle cx="25" cy="25" r="20" fill="none" stroke-width="3" class="loading-overlay__arc" />
        </svg>
      </div>
      <p v-if="message" class="loading-overlay__label">{{ message }}</p>
    </div>
  </Transition>
</template>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.25rem;
  background: rgba(5, 8, 20, 0.82);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.loading-overlay__spinner {
  width: 52px;
  height: 52px;
}

.loading-overlay__svg {
  width: 100%;
  height: 100%;
  animation: spin 1s linear infinite;
}

.loading-overlay__track {
  stroke: rgba(255, 255, 255, 0.1);
}

.loading-overlay__arc {
  stroke: #6366f1;
  stroke-linecap: round;
  stroke-dasharray: 80 126;
}

.loading-overlay__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.55);
  letter-spacing: 0.04em;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.25s ease;
}
.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}
</style>
