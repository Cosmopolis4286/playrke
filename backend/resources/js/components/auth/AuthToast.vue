<template>
  <Teleport to="body">
    <div class="toast-container" aria-live="polite" aria-atomic="false">
      <TransitionGroup name="toast" tag="div">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', `toast--${toast.type}`]"
          role="alert"
          @click="remove(toast.id)"
        >
          <!-- Accent bar -->
          <span class="toast__bar" />

          <!-- Icon -->
          <span class="toast__icon">
            <i :class="icons[toast.type]" />
          </span>

          <!-- Message -->
          <span class="toast__message">{{ toast.message }}</span>

          <!-- Close -->
          <button
            type="button"
            class="toast__close"
            :aria-label="`Cerrar notificación`"
            @click.stop="remove(toast.id)"
          >
            <i class="fa-solid fa-xmark" />
          </button>

          <!-- Progress bar -->
          <span
            v-if="toast.duration > 0"
            class="toast__progress"
            :style="{ animationDuration: `${toast.duration}ms` }"
          />
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useToast } from '@js/composables/utils/useToast'

const { toasts, remove } = useToast()

const icons: Record<string, string> = {
  success: 'fa-solid fa-circle-check',
  error:   'fa-solid fa-circle-xmark',
  warning: 'fa-solid fa-triangle-exclamation',
  info:    'fa-solid fa-circle-info',
}
</script>

<style scoped>
/* ── Container ───────────────────────────────────────────── */
.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
  width: 320px;
}

/* ── Toast base ──────────────────────────────────────────── */
.toast {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px 12px 16px;
  background: rgba(6, 10, 36, 0.92);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 12px;
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.55);
  pointer-events: auto;
  cursor: pointer;
  overflow: hidden;
  user-select: none;
}

/* ── Accent side bar ─────────────────────────────────────── */
.toast__bar {
  position: absolute;
  left: 0;
  top: 10%;
  bottom: 10%;
  width: 3px;
  border-radius: 0 3px 3px 0;
}

/* ── Icon ────────────────────────────────────────────────── */
.toast__icon {
  font-size: 1.1rem;
  flex-shrink: 0;
  line-height: 1;
}

/* ── Message ─────────────────────────────────────────────── */
.toast__message {
  flex: 1;
  font-family: 'Poppins', sans-serif;
  font-size: 0.82rem;
  font-weight: 400;
  line-height: 1.45;
  color: #e2e8f0;
  letter-spacing: 0.1px;
}

/* ── Close button ────────────────────────────────────────── */
.toast__close {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: rgba(226, 232, 240, 0.35);
  padding: 2px 4px;
  border: none;
  background: none;
  cursor: pointer;
  transition: color 0.2s;
  line-height: 1;
}
.toast__close:hover { color: rgba(226, 232, 240, 0.8); }

/* ── Progress bar ────────────────────────────────────────── */
.toast__progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 2px;
  width: 100%;
  border-radius: 0 0 12px 12px;
  animation: shrink linear forwards;
  transform-origin: left;
}

@keyframes shrink {
  from { transform: scaleX(1); }
  to   { transform: scaleX(0); }
}

/* ── Variants ────────────────────────────────────────────── */
.toast--success .toast__bar     { background: #22d3a5; }
.toast--success .toast__icon    { color: #22d3a5; }
.toast--success .toast__progress{ background: rgba(34, 211, 165, 0.55); }

.toast--error .toast__bar     { background: #f87171; }
.toast--error .toast__icon    { color: #f87171; }
.toast--error .toast__progress{ background: rgba(248, 113, 113, 0.55); }

.toast--warning .toast__bar     { background: #fbbf24; }
.toast--warning .toast__icon    { color: #fbbf24; }
.toast--warning .toast__progress{ background: rgba(251, 191, 36, 0.55); }

.toast--info .toast__bar     { background: #00b8d9; }
.toast--info .toast__icon    { color: #00b8d9; }
.toast--info .toast__progress{ background: rgba(0, 184, 217, 0.55); }

/* ── Transitions ─────────────────────────────────────────── */
.toast-enter-active {
  transition: opacity 0.28s ease, transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(24px) scale(0.96);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(24px) scale(0.96);
}
.toast-move {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 400px) {
  .toast-container {
    bottom: 12px;
    right: 12px;
    left: 12px;
    width: auto;
  }
}

/* ── Reduced motion ──────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active,
  .toast-move,
  .toast__progress { transition: none; animation: none; }
}
</style>
