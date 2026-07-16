<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  playersFlash: boolean
  livePlayers: number
  totalWinners: number
  cardCount: number
  rounds?: number
  round?: number
  bingoPattern?: string
}

const props = withDefaults(defineProps<Props>(), {
  rounds: 0,
  round: 1,
  bingoPattern: '',
})

defineEmits<{
  openPlayers: []
}>()

// Fix #14: guard contra datos inconsistentes del backend (round > rounds)
const safeRound = computed(() =>
  props.rounds ? Math.min(props.round ?? 1, props.rounds) : (props.round ?? 1)
)
</script>

<template>
  <div class="gh-stats">

    <!-- Players -->
    <button class="gh-stat gh-stat--btn" :class="{ 'gh-stat--flash': playersFlash }" @click="$emit('openPlayers')" title="Ver jugadores y ganadores">
      <div class="gh-stat__icon-wrap">
        <i class="fas fa-users" />
        <i v-if="totalWinners > 0" class="fas fa-trophy gh-stat__badge-icon" />
      </div>
      <div class="gh-stat__body">
        <span class="gh-stat__val">
          {{ livePlayers }}
          <em v-if="totalWinners > 0" class="gh-stat__sub-win">· {{ totalWinners }} <i class="fas fa-trophy" /></em>
        </span>
        <span class="gh-stat__lbl">jugadores</span>
      </div>
    </button>

    <div class="gh-stat-sep" />

    <!-- Cards -->
    <div class="gh-stat">
      <i class="fas fa-ticket-alt gh-stat__solo-icon" />
      <div class="gh-stat__body">
        <span class="gh-stat__val">{{ cardCount }}</span>
        <span class="gh-stat__lbl">cartone{{ cardCount === 1 ? '' : 's' }}</span>
      </div>
    </div>

    <!-- Rounds -->
    <template v-if="rounds">
      <div class="gh-stat-sep" />
      <div class="gh-stat">
        <i class="fas fa-sync-alt gh-stat__solo-icon" />
        <div class="gh-stat__body">
          <span class="gh-stat__val">{{ safeRound }}<em class="gh-stat__sub">/{{ rounds }}</em></span>
          <span class="gh-stat__lbl">ronda</span>
        </div>
      </div>
    </template>

    <!-- Pattern -->
    <template v-if="bingoPattern">
      <div class="gh-stat-sep" />
      <div class="gh-stat">
        <i class="fas fa-th gh-stat__solo-icon" />
        <div class="gh-stat__body">
          <span class="gh-stat__val gh-stat__val--sm">{{ bingoPattern }}</span>
          <span class="gh-stat__lbl">patrón</span>
        </div>
      </div>
    </template>

  </div>
</template>

<style scoped>
.gh-stats {
  display: flex; align-items: stretch;
  padding: 0 18px; background: rgba(0,0,0,0.1);
  flex-wrap: wrap; min-height: 40px;
}

.gh-stat {
  display: flex; align-items: center; gap: 7px;
  padding: 6px 12px; flex: 1; min-width: 0; position: relative;
}
.gh-stat--btn {
  cursor: pointer; border-radius: 8px;
  transition: background 0.18s ease; background: transparent; border: none; text-align: left;
}
.gh-stat--btn:hover { background: rgba(100,140,255,0.07); }
/* Feedback táctil tipo app nativa — el :hover no existe en touch, así que el
   :active es la única señal de "toqué esto" en móvil. */
.gh-stat--btn:active { transform: scale(0.94); }

/* Icon with optional badge overlay */
.gh-stat__icon-wrap { position: relative; width: 22px; height: 22px; flex-shrink: 0; }
.gh-stat__icon-wrap > i:first-child {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}
.gh-stat__badge-icon {
  position: absolute; bottom: -5px; left: 10px;
  width: 13px !important; height: 13px !important;
  font-size: 6.5px !important; color: #ffd700 !important;
  background: rgba(255,200,0,0.16) !important; border-color: rgba(255,200,0,0.3) !important;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
}
.gh-stat__solo-icon {
  width: 22px; height: 22px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}

.gh-stat__body { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.gh-stat__val {
  font-size: 13px; font-weight: 800; color: #c8d8ff; line-height: 1.1;
  font-family: 'Courier New', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: flex; align-items: center; gap: 4px;
}
.gh-stat__val--sm { font-size: 11px; font-family: inherit; }
.gh-stat__sub     { font-size: 9px; color: #2a3860; font-weight: 500; font-style: normal; }
.gh-stat__sub-win { font-size: 11px; color: #ffd700; font-family: inherit; font-style: normal; }
.gh-stat__sub-win i { font-size: 9px; }
.gh-stat__lbl { font-size: 7px; font-weight: 700; color: #2a3860; text-transform: uppercase; letter-spacing: 0.5px; }

/* Flash animation on stat */
.gh-stat--flash .gh-stat__icon-wrap > i:first-child,
.gh-stat--flash .gh-stat__solo-icon { background: rgba(42,245,152,0.14); color: #2af598; }
.gh-stat--flash .gh-stat__val { animation: stat-pop 0.5s ease; }
@keyframes stat-pop { 0% { color: #2af598; transform: scale(1); } 40% { transform: scale(1.14); } 100% { color: #c8d8ff; transform: scale(1); } }

/* Stat separators */
.gh-stat-sep {
  width: 1px; align-self: stretch; margin: 6px 0;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.12), transparent);
  flex-shrink: 0;
}

/* "Mis fichas faltantes" — mismos umbrales de color que el badge que reemplaza */
.gh-stat--warn .gh-stat__solo-icon { background: rgba(255,160,0,0.14); color: #ffb060; }
.gh-stat--warn .gh-stat__val       { color: #ffb060; }
.gh-stat--hot .gh-stat__solo-icon  { background: rgba(255,50,50,0.14); color: #ff8080; animation: gh-stat-pulse 1s ease-in-out infinite alternate; }
.gh-stat--hot .gh-stat__val        { color: #ff8080; }
.gh-stat--bingo .gh-stat__solo-icon { background: rgba(42,245,152,0.16); color: #2af598; animation: gh-stat-pulse-bingo 0.8s ease-in-out infinite alternate; }
.gh-stat--bingo .gh-stat__val       { color: #2af598; }
@keyframes gh-stat-pulse { from { box-shadow: 0 0 0 0 rgba(255,50,50,0.15); } to { box-shadow: 0 0 8px 2px rgba(255,50,50,0.3); } }
@keyframes gh-stat-pulse-bingo { from { box-shadow: 0 0 0 0 rgba(42,245,152,0.2); } to { box-shadow: 0 0 10px 2px rgba(42,245,152,0.4); } }

/* Responsive */
@media (max-width: 768px) {
  .gh-stats { padding: 0 14px; }
  .gh-stat { padding: 6px 8px; }
}
@media (max-width: 560px) {
  .gh-stats { display: grid; grid-template-columns: 1fr 1fr; padding: 0 12px; }
  .gh-stat-sep { display: none; }
  .gh-stat { flex: none; padding: 6px 8px; border-bottom: 1px solid rgba(80,120,255,0.06); }
  .gh-stat:nth-child(odd) { border-right: 1px solid rgba(80,120,255,0.06); }
}
@media (max-width: 380px) {
  .gh-stats { padding: 0 10px; }
  .gh-stat { padding: 5px 6px; }
  .gh-stat__val { font-size: 12px; }
}
</style>
