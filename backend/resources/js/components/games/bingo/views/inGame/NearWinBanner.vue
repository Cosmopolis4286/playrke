<script setup lang="ts">
import type { PlayerProgress } from './types'

interface Props {
  players: PlayerProgress[]
  remainingChips: number
}

defineProps<Props>()
</script>

<template>
  <Transition name="ig-fade">
    <div v-if="players.length > 0" class="ig-near-win">
      <div class="ig-near-win__header">
        <i class="fas fa-fire ig-near-win__fire" />
        <span class="ig-near-win__chips-badge">{{ remainingChips }}</span>
        <span class="ig-near-win__title">ficha{{ remainingChips === 1 ? '' : 's' }} restantes</span>
      </div>
      <div class="ig-near-win__row">
        <div
          v-for="(p, idx) in players"
          :key="p.user_id"
          class="ig-near-win__player"
          :class="{
            'ig-near-win__player--hot':  p.missing <= 2,
            'ig-near-win__player--warn': p.missing <= 5 && p.missing > 2,
          }"
        >
          <span class="ig-near-win__rank">#{{ idx + 1 }}</span>
          <span class="ig-near-win__name">{{ p.user_name }}</span>
          <span class="ig-near-win__miss-badge" :class="{ 'ig-near-win__miss-badge--hot': p.missing <= 2 }">
            {{ p.missing === 0 ? '¡BINGO!' : p.missing }}
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
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
</style>
