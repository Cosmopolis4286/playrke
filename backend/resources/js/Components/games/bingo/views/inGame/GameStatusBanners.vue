<script setup lang="ts">
defineProps<{
  drawStalling: boolean
  isFinished: boolean
  isReconnecting: boolean
  reconnectingTooLong: boolean
  claimingBingo: boolean
  bingoConfirmed: boolean
}>()

defineEmits<{
  resync: []
  reload: []
}>()
</script>

<template>
  <!-- Banner sorteo lento — si pasan >90s sin nueva ficha durante partida activa -->
  <Transition name="ig-reconnect">
    <div v-if="drawStalling && !isFinished && !isReconnecting" class="ig-stall-banner">
      <i class="fas fa-hourglass-half ig-stall-banner__icon" />
      El sorteo está tardando más de lo normal
      <button class="ig-reconnect-reload ig-stall-reload" @click="$emit('resync')">
        Resincronizar
      </button>
    </div>
  </Transition>

  <!-- Banner de reconexión WS -->
  <Transition name="ig-reconnect">
    <div v-if="isReconnecting" class="ig-reconnect-banner" :class="{ 'ig-reconnect-banner--timeout': reconnectingTooLong }">
      <span class="ig-reconnect-spinner" />
      <span v-if="reconnectingTooLong">Sin conexión —</span>
      <span v-else>Reconectando…</span>
      <button v-if="reconnectingTooLong" class="ig-reconnect-reload" @click="$emit('reload')">
        Recargar
      </button>
    </div>
  </Transition>

  <!-- Overlay claim BINGO en vuelo — sticky para no empujar el layout -->
  <Teleport to="body">
    <Transition name="ig-claim">
      <div v-if="claimingBingo" class="ig-claim-overlay">
        <span class="ig-claim-spinner" />
        Verificando BINGO…
      </div>
      <div v-else-if="bingoConfirmed" class="ig-claim-overlay ig-claim-overlay--confirmed">
        <i class="fas fa-check-circle" />
        ¡BINGO confirmado! Esperando resultado…
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Banner reconexión */
.ig-reconnect-banner {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; border-radius: 10px;
  background: rgba(255,160,30,0.1); border: 1px solid rgba(255,160,30,0.3);
  color: #ffb84a; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;
  transition: background 0.3s ease, border-color 0.3s ease;
}
.ig-reconnect-banner--timeout {
  background: rgba(255,60,60,0.1); border-color: rgba(255,60,60,0.35);
  color: #ff8080;
}
.ig-reconnect-reload {
  margin-left: auto; padding: 3px 10px; border-radius: 6px;
  background: rgba(255,60,60,0.15); border: 1px solid rgba(255,60,60,0.4);
  color: #ff8080; font-size: 10px; font-weight: 700; cursor: pointer;
  transition: background 0.15s ease;
}
.ig-reconnect-reload:hover { background: rgba(255,60,60,0.28); }
.ig-reconnect-spinner {
  width: 12px; height: 12px; border-radius: 50%;
  border: 2px solid rgba(255,160,30,0.28); border-top-color: #ffb84a;
  animation: ig-rspin 0.8s linear infinite; flex-shrink: 0;
}
.ig-reconnect-banner--timeout .ig-reconnect-spinner {
  animation: none;
  border-color: rgba(255,60,60,0.3);
  border-top-color: #ff8080;
}

/* Banner sorteo lento */
.ig-stall-banner {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; border-radius: 10px;
  background: rgba(255,160,30,0.07); border: 1px solid rgba(255,160,30,0.22);
  color: #c89040; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;
}
.ig-stall-banner__icon { animation: ig-hourglass 2s ease-in-out infinite; }
@keyframes ig-hourglass { 0%,100% { opacity: 0.6; } 50% { opacity: 1; } }
.ig-stall-reload {
  border-color: rgba(255,160,30,0.4) !important;
  background: rgba(255,160,30,0.1) !important;
  color: #ffb84a !important;
}
.ig-stall-reload:hover { background: rgba(255,160,30,0.2) !important; }

@keyframes ig-rspin { to { transform: rotate(360deg) } }
.ig-reconnect-enter-active,
.ig-reconnect-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.ig-reconnect-enter-from,
.ig-reconnect-leave-to     { opacity: 0; transform: translateY(-4px); }

/* Overlay claim BINGO — fixed centrado, no empuja el layout (Fix #6) */
.ig-claim-overlay {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  z-index: 1400;
  display: flex; align-items: center; gap: 10px;
  padding: 10px 22px; border-radius: 30px;
  background: rgba(14,8,38,0.92); border: 1px solid rgba(123,79,255,0.5);
  color: #b090ff; font-size: 12px; font-weight: 700; letter-spacing: 0.3px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 24px rgba(123,79,255,0.2);
  backdrop-filter: blur(12px); white-space: nowrap;
}
.ig-claim-overlay--confirmed {
  border-color: rgba(42,245,152,0.5);
  color: #2af598;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 24px rgba(42,245,152,0.18);
}
.ig-claim-overlay--confirmed i { font-size: 14px; }
.ig-claim-spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(123,79,255,0.3); border-top-color: #b090ff;
  animation: ig-rspin 0.7s linear infinite; flex-shrink: 0;
}
.ig-claim-enter-active,
.ig-claim-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.ig-claim-enter-from  { opacity: 0; transform: translateX(-50%) translateY(8px); }
.ig-claim-leave-to    { opacity: 0; transform: translateX(-50%) translateY(8px); }
</style>
