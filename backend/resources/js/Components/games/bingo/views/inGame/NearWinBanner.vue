<script setup lang="ts">
import { computed } from 'vue'
import type { PlayerProgress } from './types'

interface Props {
  players: PlayerProgress[]
  remainingChips: number
  maxVisible?: number
}

const props = withDefaults(defineProps<Props>(), { maxVisible: 8 })

const visiblePlayers = computed(() => props.players.slice(0, props.maxVisible))
const hiddenCount    = computed(() => Math.max(0, props.players.length - props.maxVisible))
</script>

<template>
  <div class="ig-near-win-collapse" :class="{ 'ig-near-win-collapse--open': players.length > 0 }">
    <div class="ig-near-win-collapse__inner">
      <div v-if="players.length > 0" class="ig-near-win">
        <div class="ig-near-win__header">
          <i class="fas fa-fire ig-near-win__fire" />
          <span class="ig-near-win__chips-badge">{{ remainingChips }}</span>
          <span class="ig-near-win__title">ficha{{ remainingChips === 1 ? '' : 's' }} restantes</span>
        </div>
        <div class="ig-near-win__row">
          <div
            v-for="(p, idx) in visiblePlayers"
            :key="p.user_id"
            class="ig-near-win__player"
            :class="{
              'ig-near-win__player--hot':  p.missing <= 2,
              'ig-near-win__player--warn': p.missing <= 5 && p.missing > 2,
            }"
            :title="p.user_name"
          >
            <span class="ig-near-win__rank">#{{ idx + 1 }}</span>
            <span class="ig-near-win__name">{{ p.user_name }}</span>
            <span class="ig-near-win__miss-badge" :class="{ 'ig-near-win__miss-badge--hot': p.missing <= 2 }">
              {{ p.missing === 0 ? '¡BINGO!' : p.missing }}
            </span>
          </div>
          <span v-if="hiddenCount > 0" class="ig-near-win__more">+{{ hiddenCount }} más</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Wrapper que anima su altura (grid-template-rows 0fr → 1fr) para que aparecer/
   desaparecer el banner no empuje el layout de golpe — evita el salto de los
   cartones de abajo cuando quedan <=10 fichas por cantar. */
.ig-near-win-collapse {
  display: grid; grid-template-rows: 0fr;
  transition: grid-template-rows 0.32s ease;
}
.ig-near-win-collapse--open { grid-template-rows: 1fr; }
.ig-near-win-collapse__inner { overflow: hidden; min-height: 0; }

.ig-near-win {
  background: linear-gradient(135deg, rgba(255,100,30,0.08) 0%, rgba(255,180,0,0.06) 100%);
  border: 1px solid rgba(255,130,0,0.2); border-radius: 14px;
  padding: 10px 16px; display: flex; flex-direction: column; gap: 8px;
  box-shadow: 0 0 24px rgba(255,120,0,0.06);
}
.ig-near-win__header { display: flex; align-items: center; gap: 8px; }
.ig-near-win__fire { color: #ff8c00; font-size: 14px; animation: fire-flicker 1s ease-in-out infinite alternate; flex-shrink: 0; }
@keyframes fire-flicker { from { transform: scale(1) rotate(-3deg); color: #ff6a00; } to { transform: scale(1.1) rotate(3deg); color: #ffb800; } }
.ig-near-win__chips-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 24px; height: 24px; padding: 0 6px;
  background: rgba(255,120,0,0.15); border: 1px solid rgba(255,120,0,0.35);
  border-radius: 8px; font-size: 13px; font-weight: 900; color: #ffb060; font-family: 'Courier New', monospace;
  box-shadow: 0 0 10px rgba(255,120,0,0.2);
}
.ig-near-win__title { font-size: 11px; font-weight: 700; color: #c87040; text-transform: uppercase; letter-spacing: 0.8px; }

.ig-near-win__row { display: flex; flex-wrap: wrap; gap: 6px; }
.ig-near-win__player {
  display: flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 20px;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
  transition: all 0.25s ease;
}
.ig-near-win__player--hot { background: rgba(255,60,60,0.1); border-color: rgba(255,60,60,0.3); box-shadow: 0 0 10px rgba(255,60,60,0.12); }
.ig-near-win__player--warn { background: rgba(255,160,0,0.08); border-color: rgba(255,160,0,0.25); }
.ig-near-win__rank { font-size: 9px; color: #4a68a8; font-weight: 700; }
.ig-near-win__name { font-size: 11px; font-weight: 700; color: #c0d0f0; }
.ig-near-win__miss-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 4px;
  background: rgba(80,120,255,0.12); border: 1px solid rgba(80,120,255,0.25);
  border-radius: 6px; font-size: 9.5px; font-weight: 900; color: #7c9dff; font-family: 'Courier New', monospace;
}
.ig-near-win__miss-badge--hot { background: rgba(255,50,50,0.15); border-color: rgba(255,50,50,0.4); color: #ff8080; animation: hot-pulse 0.8s ease-in-out infinite alternate; }
@keyframes hot-pulse { from { box-shadow: none; } to { box-shadow: 0 0 8px rgba(255,50,50,0.4); } }
.ig-near-win__more {
  display: inline-flex; align-items: center;
  padding: 4px 10px; border-radius: 20px;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
  font-size: 10px; font-weight: 700; color: #4a68a8;
}
</style>
