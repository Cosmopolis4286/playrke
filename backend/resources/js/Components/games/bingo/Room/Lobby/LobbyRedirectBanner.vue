<script setup lang="ts">
defineProps<{ show: boolean; secs?: number }>()
defineEmits<{ cancel: [] }>()
</script>

<template>
  <Teleport to="body">
  <Transition name="lby-redirect">
    <div v-if="show" class="lby-redirect-banner" role="alertdialog" aria-modal="true" aria-label="Redirigiendo al juego">
      <div class="lby-redirect-banner__inner">
        <div class="lby-redirect-banner__spinner" />
        <div>
          <p class="lby-redirect-banner__title">¡El sorteo ha comenzado!</p>
          <p class="lby-redirect-banner__sub">
            Entrando al juego{{ (secs != null && secs > 0) ? ` en ${secs}s` : '…' }}
          </p>
        </div>
        <button class="lby-redirect-banner__cancel" @click="$emit('cancel')" aria-label="Cancelar redirección">
          <i class="fas fa-times" />
        </button>
      </div>
    </div>
  </Transition>
  </Teleport>
</template>

<style scoped>
.lby-redirect-banner {
  position: fixed; inset: 0; z-index: 9000;
  background: rgba(2,5,16,.82); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.lby-redirect-banner__inner {
  display: flex; align-items: center; gap: 18px;
  background: linear-gradient(135deg, #0d1a10 0%, #081510 100%);
  border: 1px solid rgba(42,245,152,.35); border-radius: 18px; padding: 24px 32px;
  box-shadow: 0 0 60px rgba(42,245,152,.12), 0 24px 60px rgba(0,0,0,.7);
  animation: lbyRedirectPop .35s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyRedirectPop {
  from { opacity: 0; transform: scale(.9) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.lby-redirect-banner__spinner {
  width: 36px; height: 36px; flex-shrink: 0;
  border: 3px solid rgba(42,245,152,.15); border-top-color: #2af598;
  border-radius: 50%; animation: lbySpin 1s linear infinite;
}
@keyframes lbySpin { to { transform: rotate(360deg); } }
.lby-redirect-banner__title  { margin: 0 0 3px; font-size: 15px; font-weight: 700; color: #2af598; }
.lby-redirect-banner__sub    { margin: 0; font-size: 11px; color: #3a6050; }
.lby-redirect-banner__cancel {
  margin-left: 8px; width: 28px; height: 28px; flex-shrink: 0;
  border-radius: 50%; border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.04); color: rgba(255,255,255,.35);
  font-size: 11px; cursor: pointer; align-self: flex-start;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s, border-color .2s;
}
.lby-redirect-banner__cancel:hover { background: rgba(255,77,109,.12); border-color: rgba(255,77,109,.3); color: #ff6e8a; }

.lby-redirect-enter-active { transition: opacity .25s ease; }
.lby-redirect-leave-active { transition: opacity .2s ease; }
.lby-redirect-enter-from,
.lby-redirect-leave-to { opacity: 0; }
</style>
