<script setup lang="ts">
import type { WinnerEntry, GameEndData } from '@js/Components/games/bingo/views/inGame/types'

defineProps<{
  gameEndData: GameEndData | null
  userId?: string | null
  currencySym: string
  loadingCardId: string | null
}>()

defineEmits<{
  'view-card': [winner: WinnerEntry]
}>()
</script>

<template>
  <div class="pm-section-label"><i class="fas fa-trophy" /> Ganadores</div>
  <div
    v-for="(w, idx) in gameEndData?.winners ?? []"
    :key="`pm-w-${w.card_id}-${idx}`"
    class="pm-row"
    :class="{ 'pm-row--me': String(w.user_id) === String(userId) }"
  >
    <div class="pm-row__rank">#{{ idx + 1 }}</div>
    <div class="pm-row__info">
      <div class="pm-row__name">
        {{ w.user_name }}
        <span v-if="String(w.user_id) === String(userId)" class="pm-you">TÚ</span>
      </div>
      <div class="pm-row__meta">
        <span class="pm-serial">{{ w.card_serial }}</span>
        <span v-if="w.round" class="pm-badge pm-badge--round">Ronda {{ w.round }}</span>
        <span v-if="w.pattern" class="pm-badge pm-badge--pattern">{{ w.pattern }}</span>
      </div>
    </div>
    <div class="pm-row__prize">
      <span class="pm-row__sym">{{ currencySym }}</span>{{ Number(w.prize).toFixed(2) }}
    </div>
    <button
      v-if="w.card_id"
      class="pm-card-btn"
      :disabled="loadingCardId === w.card_id"
      :title="loadingCardId === w.card_id ? 'Cargando…' : 'Ver cartón'"
      @click="$emit('view-card', w)"
    >
      <i :class="loadingCardId === w.card_id ? 'fas fa-spinner fa-spin' : 'fas fa-th'" />
    </button>
  </div>
</template>

<style scoped>
.pm-section-label { font-size: 9px; font-weight: 700; color: #3a5080; text-transform: uppercase; letter-spacing: 1.2px; display: flex; align-items: center; gap: 5px; padding: 4px 0; }
.pm-section-label i { color: #ffd700; font-size: 8px; }

.pm-row {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 10px; border-radius: 10px;
  background: rgba(255,255,255,0.025); border: 1px solid rgba(80,120,255,0.08);
  transition: background 0.2s;
}
.pm-row--me { background: rgba(42,245,152,0.06); border-color: rgba(42,245,152,0.2); }
.pm-row__rank { font-size: 10px; font-weight: 700; color: #4a68a8; width: 22px; flex-shrink: 0; }
.pm-row__info { flex: 1; min-width: 0; }
.pm-row__name { font-size: 12px; font-weight: 700; color: #c8d8ff; display: flex; align-items: center; gap: 5px; }
.pm-row__meta { display: flex; align-items: center; gap: 5px; margin-top: 2px; flex-wrap: wrap; }
.pm-row__prize { font-size: 13px; font-weight: 800; color: #ffd700; font-family: 'Courier New', monospace; flex-shrink: 0; white-space: nowrap; }
.pm-row__sym { font-size: 10px; font-weight: 700; margin-right: 2px; }
.pm-serial { font-size: 9.5px; color: #3a5080; }
.pm-you { font-size: 8px; font-weight: 900; color: #2af598; background: rgba(42,245,152,0.12); border: 1px solid rgba(42,245,152,0.3); border-radius: 4px; padding: 1px 5px; letter-spacing: 0.5px; }
.pm-badge { font-size: 9px; font-weight: 700; border-radius: 4px; padding: 1px 6px; }
.pm-badge--round { background: rgba(80,120,255,0.1); border: 1px solid rgba(80,120,255,0.2); color: #7c9dff; }
.pm-badge--pattern { background: rgba(180,80,255,0.1); border: 1px solid rgba(180,80,255,0.2); color: #c07fff; }
.pm-card-btn {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  color: #7c9dff; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, color 0.2s;
}
.pm-card-btn:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; }
</style>
