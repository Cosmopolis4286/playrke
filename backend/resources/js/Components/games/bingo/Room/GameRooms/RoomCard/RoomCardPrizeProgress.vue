<script setup lang="ts">
defineProps<{
  isAccumulated: boolean
  prizeFormatted: string
  sym: string
  showCardsProgress: boolean
  cardsProgressReady: boolean
  cardsRemaining: number
  showSellingFast: boolean
  totalCards: number
  minCardsToStart: number | null
  cardsProgressPct: number
}>()
</script>

<template>
  <!-- Premio — bloque hero, centrado -->
  <div
    v-tooltip="isAccumulated
      ? 'Bote acumulado: crece con cada cartón vendido en la sala'
      : 'Premio fijo para esta sala'"
    class="rc__prize"
  >
    <div class="rc__prize-label">
      <i class="fas fa-trophy" />
      {{ isAccumulated ? 'Bote acumulado' : 'Premio' }}
      <i v-if="isAccumulated" class="fas fa-arrow-trend-up rc__prize-trend" />
    </div>
    <div class="rc__prize-amount">
      <span class="rc__prize-num">{{ prizeFormatted }}</span>
      <span class="rc__prize-sym">{{ sym }}</span>
    </div>
  </div>

  <!-- Barra de progreso cartones -->
  <div
    v-if="showCardsProgress"
    v-tooltip="cardsProgressReady
      ? 'Ya se alcanzó el mínimo de cartones — el sorteo puede iniciar'
      : `Faltan ${cardsRemaining} cartón${cardsRemaining !== 1 ? 'es' : ''} vendido${cardsRemaining !== 1 ? 's' : ''} para que la sala pueda iniciar`"
    class="rc__progress"
    :class="{ 'rc__progress--ready': cardsProgressReady }"
  >
    <div class="rc__progress__header">
      <span
        class="rc__progress__label"
        :class="{ 'rc__progress__label--hot': showSellingFast && !cardsProgressReady }"
      >
        <i :class="showSellingFast && !cardsProgressReady ? 'fas fa-fire' : 'fas fa-layer-group'" />
        {{
          cardsProgressReady
            ? '¡Listo!'
            : (showSellingFast ? 'Vendiendo rápido' : 'Cartones para iniciar')
        }}
      </span>
      <span class="rc__progress__count">{{ totalCards }} / {{ minCardsToStart }}</span>
    </div>
    <div class="rc__progress__track">
      <div class="rc__progress__fill" :style="{ width: cardsProgressPct + '%' }" />
    </div>
  </div>
</template>

<style scoped>
/* ── Premio — bloque hero, centrado ── */
.rc__prize {
  display: flex; flex-direction: column; align-items: center; gap: 2px;
  padding: 10px 8px 11px;
  border-radius: 10px;
  background: radial-gradient(ellipse 90% 100% at 50% 0%, rgba(255,180,40,.09), transparent 70%);
  flex-shrink: 0;
}
.rc__prize-label {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #c8a028; opacity: .85;
}
.rc__prize-label i { font-size: 9px; filter: drop-shadow(0 0 3px rgba(200,160,40,.4)); }
.rc__prize-trend {
  color: #2af598; font-size: 9px;
  animation: prizeTrendPulse 1.8s ease-in-out infinite;
}
@keyframes prizeTrendPulse {
  0%,100% { opacity: .55; transform: translateY(0); }
  50%     { opacity: 1;   transform: translateY(-1px); }
}
.rc__prize-amount {
  display: flex; align-items: baseline; gap: 5px;
  max-width: 100%;
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 27px; font-weight: 900; line-height: 1.1; letter-spacing: -.4px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 8px rgba(255,150,0,.32));
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__prize-sym {
  font-size: 13px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
  flex-shrink: 0;
}

/* ── Progreso cartones ── */
.rc__progress { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }
.rc__progress__header {
  display: flex; align-items: center; justify-content: space-between; gap: 6px;
}
.rc__progress__label {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 9.5px; font-weight: 700; letter-spacing: .2px;
  color: #5a7ca0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__progress__label i { font-size: 8px; }

/* "Vendiendo rápido" — pico de ventas detectado entre actualizaciones */
.rc__progress__label--hot {
  color: #ff9430;
  animation: hotPulse 1s ease-in-out infinite;
}
.rc__progress__label--hot i { color: #ff7a1a; }
@keyframes hotPulse {
  0%, 100% { opacity: .8; }
  50%      { opacity: 1; }
}
.rc__progress__count {
  font-size: 10px; font-weight: 800; color: #7a9ac0;
  flex-shrink: 0; white-space: nowrap;
}
.rc__progress__track {
  height: 5px; border-radius: 4px; overflow: hidden;
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.06);
}
.rc__progress__fill {
  height: 100%; border-radius: 4px;
  background: linear-gradient(90deg, #2260c0 0%, #2af598 100%);
  box-shadow: 0 0 7px rgba(42,245,152,.3);
  transition: width .5s cubic-bezier(.22,.68,0,1.1);
}
.rc__progress--ready .rc__progress__label { color: #2af598; }
.rc__progress--ready .rc__progress__count { color: #2af598; }
.rc__progress--ready .rc__progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #7bffcc 100%);
  animation: cpPulse 1.5s ease-in-out infinite;
}
@keyframes cpPulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.35); }
  50%     { box-shadow: 0 0 12px rgba(42,245,152,.7); }
}
</style>
