<script setup lang="ts">
const props = defineProps<{
  isAccumulated: boolean
  prizeFormatted: string
  sym: string
  showCardsProgress: boolean
  cardsProgressReady: boolean
  cardsRemaining: number
  showSellingFast: boolean
  totalCards: number
  minCardsToStart: number | null
  maxCardsToStart?: number | null
  cardsProgressPct: number
  /** % de venta sobre el TOPE de la sala (max_cards_to_start) — ver
   * BingoGameRoomsDTO::cards_sold_pct. Cuando la sala tiene tope, esta es la
   * base de la única barra de venta (reemplaza cardsProgressPct/minCardsToStart
   * como base visual — el mínimo se comunica como etiqueta de texto, no como
   * marca geométrica: en salas reales el mínimo (ej. 5) suele ser órdenes de
   * magnitud menor que el tope (ej. 500), así que un tick de posición
   * quedaría invisible pegado al borde izquierdo). */
  cardsSoldPct?: number | null
  /** % del pozo actual sobre su proyección al 100% de venta del tope */
  prizePoolPct?: number | null
}>()

// Con tope configurado: una sola barra, base = tope de venta.
// Sin tope: fallback a la barra original, base = mínimo para iniciar.
const usesTopeBar = () => props.cardsSoldPct != null && props.maxCardsToStart

const progressLabel = () => {
  if (props.cardsProgressReady) return '¡Listo para iniciar!'
  if (props.showSellingFast) return 'Vendiendo rápido'
  return usesTopeBar() ? 'Cartones vendidos' : 'Cartones para iniciar'
}

const progressCount = () =>
  usesTopeBar() ? `${props.totalCards} / ${props.maxCardsToStart}` : `${props.totalCards} / ${props.minCardsToStart}`

const progressPct = () => (usesTopeBar() ? (props.cardsSoldPct ?? 0) : props.cardsProgressPct)

const progressTooltip = () => {
  const cap = props.maxCardsToStart ? ` (tope de la sala: ${props.maxCardsToStart} cartones)` : ''
  return props.cardsProgressReady
    ? `Ya se alcanzó el mínimo de cartones — el sorteo puede iniciar${cap}`
    : `Faltan ${props.cardsRemaining} cartón${props.cardsRemaining !== 1 ? 'es' : ''} vendido${props.cardsRemaining !== 1 ? 's' : ''} para que la sala pueda iniciar${cap}`
}
</script>

<template>
  <!-- Premio — bloque hero, centrado. El progreso del pozo se lee como texto
       bajo el monto (sin barra propia), evita apilar una tercera barra. -->
  <div
    v-tooltip="isAccumulated
      ? 'Bote acumulado: crece con cada cartón vendido en la sala'
      : 'Premio fijo para esta sala'"
    class="rc__prize"
  >
    <div class="rc__prize-label">
      <i class="fas fa-trophy rc__prize-trophy" />
      {{ isAccumulated ? 'Bote acumulado' : 'Premio' }}
      <i v-if="isAccumulated" class="fas fa-arrow-trend-up rc__prize-trend" />
    </div>
    <div class="rc__prize-amount">
      <span class="rc__prize-num">{{ prizeFormatted }}</span>
      <span class="rc__prize-sym">{{ sym }}</span>
    </div>
    <div
      v-if="prizePoolPct != null"
      v-tooltip="'Así van los premios: el pozo crece con cada cartón vendido'"
      class="rc__prize-pool-note"
    >
      <i class="fas fa-coins" /> Pozo al <b>{{ prizePoolPct }}%</b> de su proyección
    </div>
  </div>

  <!-- Barra única de progreso de venta — base = tope si la sala lo define,
       si no, base = mínimo para iniciar (comportamiento original). -->
  <div
    v-if="showCardsProgress || usesTopeBar()"
    v-tooltip="progressTooltip()"
    class="rc__progress"
    :class="{ 'rc__progress--ready': cardsProgressReady }"
  >
    <div class="rc__progress__header">
      <span
        class="rc__progress__label"
        :class="{ 'rc__progress__label--hot': showSellingFast && !cardsProgressReady }"
      >
        <i :class="showSellingFast && !cardsProgressReady ? 'fas fa-fire' : 'fas fa-ticket'" />
        {{ progressLabel() }}
      </span>
      <span class="rc__progress__count">{{ progressCount() }}</span>
    </div>
    <div class="rc__progress__track">
      <div class="rc__progress__fill" :style="{ width: progressPct() + '%' }" />
    </div>
  </div>
</template>

<style scoped>
/* ── Premio — bloque hero, centrado ── */
.rc__prize {
  display: flex; flex-direction: column; align-items: center; gap: 2px;
  padding: 6px 8px 8px;
  border-radius: 12px;
  background: radial-gradient(ellipse 90% 100% at 50% 0%, rgba(255,180,40,.07), transparent 72%);
  flex-shrink: 0;
  transition: background .3s ease;
}
.rc__prize-label {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
  color: #c8a028; opacity: .8;
}
.rc__prize-label i { font-size: 9px; filter: drop-shadow(0 0 3px rgba(200,160,40,.35)); }

/* Trofeo con vida propia — se balancea suavemente, como una moneda girando despacio */
.rc__prize-trophy {
  display: inline-block;
  transform-origin: center bottom;
  animation: trophyFloat 3s ease-in-out infinite;
}
@keyframes trophyFloat {
  0%, 100% { transform: translateY(0) rotate(-6deg); }
  50%      { transform: translateY(-1.5px) rotate(6deg); }
}
@media (prefers-reduced-motion: reduce) {
  .rc__prize-trophy { animation: none; }
}

.rc__prize-trend {
  color: #2af598; font-size: 9px;
  animation: prizeTrendPulse 2.4s ease-in-out infinite;
}
@keyframes prizeTrendPulse {
  0%,100% { opacity: .6; transform: translateY(0); }
  50%     { opacity: 1;   transform: translateY(-1px); }
}
.rc__prize-amount {
  display: flex; align-items: baseline; gap: 5px;
  max-width: 100%;
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 28px; font-weight: 900; line-height: 1.1; letter-spacing: -.4px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 10px rgba(255,150,0,.28));
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  position: relative;
}
/* Destello que recorre el número del premio cada ~3.2s — refuerza "premio especial" */
.rc__prize-num::after {
  content: '';
  position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
  background: linear-gradient(100deg, transparent, rgba(255,255,255,.55), transparent);
  transform: skewX(-20deg);
  animation: prizeSweep 3.2s ease-in-out infinite;
}
@keyframes prizeSweep {
  0%      { left: -60%; }
  35%,100% { left: 130%; }
}
@media (prefers-reduced-motion: reduce) {
  .rc__prize-num::after { animation: none; display: none; }
}
.rc__prize-sym {
  font-size: 13px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
  flex-shrink: 0;
}

/* Nota del pozo — línea de texto bajo el monto, sin barra propia */
.rc__prize-pool-note {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 9px; font-weight: 600;
  color: #a08040;
  margin-top: 2px;
}
.rc__prize-pool-note i { font-size: 8px; color: #e0a020; }
.rc__prize-pool-note b { color: #d8b04a; font-weight: 800; }

/* ── Progreso cartones (barra única) ── */
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
  animation: hotPulse 1.6s ease-in-out infinite;
}
.rc__progress__label--hot i { color: #ff7a1a; }
@keyframes hotPulse {
  0%, 100% { opacity: .85; }
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
  box-shadow: 0 0 7px rgba(42,245,152,.28);
  transition: width .6s cubic-bezier(.22,.68,0,1);
}
.rc__progress--ready .rc__progress__label { color: #2af598; }
.rc__progress--ready .rc__progress__count { color: #2af598; }
.rc__progress--ready .rc__progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #7bffcc 100%);
  animation: cpPulse 2.2s ease-in-out infinite;
}
@keyframes cpPulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.3); }
  50%     { box-shadow: 0 0 11px rgba(42,245,152,.55); }
}
</style>
