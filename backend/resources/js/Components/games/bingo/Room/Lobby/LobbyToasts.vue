<script setup lang="ts">
import type { SaleToast } from '@js/composables/games/bingo/room/lobby/useLobbyToasts'
defineProps<{ toasts: SaleToast[] }>()
</script>

<template>
  <Teleport to="body">
    <div class="lby-toasts">
      <TransitionGroup name="lby-toast">
        <div v-for="t in toasts" :key="t.id" class="lby-toast">
          <i class="fas fa-ticket-alt lby-toast__icon" />
          <span>{{ t.text }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.lby-toasts {
  position: fixed; left: 20px; bottom: 74px; z-index: 8000;
  display: flex; flex-direction: column-reverse; gap: 6px; pointer-events: none;
}
.lby-toast {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 7px 14px 7px 10px; border-radius: 10px;
  background: rgba(4,8,20,.88); border: 1px solid rgba(42,245,152,.25);
  backdrop-filter: blur(14px);
  box-shadow: 0 4px 18px rgba(0,0,0,.5), 0 0 12px rgba(42,245,152,.1);
  font-size: 11px; font-weight: 600; color: #c8d8f0; white-space: nowrap;
}
.lby-toast__icon { color: #2af598; font-size: 10px; flex-shrink: 0; }
.lby-toast-enter-active { transition: opacity .25s ease, transform .3s cubic-bezier(.22,1,.36,1); }
.lby-toast-leave-active { transition: opacity .3s ease, transform .3s ease; }
.lby-toast-enter-from   { opacity: 0; transform: translateX(-16px) scale(.95); }
.lby-toast-leave-to     { opacity: 0; transform: translateX(-8px) scale(.97); }
</style>
