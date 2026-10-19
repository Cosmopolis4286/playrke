<script setup lang="ts">
/** Bitácora de la sala (bingo_room_audits), más reciente primero. */
import type { BingoRoomTimelineEntry } from '@js/types/games/bingo/room/bingo-room'

defineProps<{ entries: BingoRoomTimelineEntry[] }>()

const ICONS: Record<string, string> = {
  status_changed: 'fa-arrows-rotate',
  cancelled: 'fa-ban',
  refund_initiated: 'fa-rotate-left',
  refund_completed: 'fa-circle-check',
  refund_failed: 'fa-triangle-exclamation',
  draw_started: 'fa-play',
  draw_finished: 'fa-flag-checkered',
  winner_declared: 'fa-trophy',
  manually_activated: 'fa-hand-pointer',
  prize_payment_completed: 'fa-money-bill-wave',
  prize_payment_failed: 'fa-triangle-exclamation',
  prize_payment_suspended: 'fa-pause',
}
const DANGER = ['cancelled', 'refund_failed', 'prize_payment_failed', 'prize_payment_suspended']

function when(iso: string | null): string {
  return iso ? new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '—'
}
</script>

<template>
  <p v-if="!entries.length" class="tl-empty">Sin eventos registrados para esta sala.</p>
  <ol v-else class="tl">
    <li v-for="e in entries" :key="e.id" class="tl__item" :class="{ 'tl__item--danger': DANGER.includes(e.event) }">
      <span class="tl__dot"><i class="fas" :class="ICONS[e.event] ?? 'fa-circle-info'"></i></span>
      <div class="tl__body">
        <div class="tl__head">
          <strong>{{ e.label }}</strong>
          <span class="tl__meta">{{ when(e.created_at) }}<template v-if="e.actor"> · {{ e.actor }}</template></span>
        </div>
        <div v-if="e.description" class="tl__desc">{{ e.description }}</div>
      </div>
    </li>
  </ol>
</template>

<style scoped>
.tl-empty { font-size: 0.82rem; color: var(--ds-text-muted); text-align: center; padding: 1.5rem 0; margin: 0; }
.tl { list-style: none; margin: 0; padding: 0; position: relative; }
.tl::before { content: ''; position: absolute; left: 11px; top: 4px; bottom: 4px; width: 2px; background: var(--ds-border); }
.tl__item { display: flex; gap: 0.6rem; padding: 0.3rem 0; position: relative; }
.tl__dot { width: 24px; height: 24px; flex-shrink: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--ds-surface-2); border: 1px solid var(--ds-border); font-size: 0.62rem; color: var(--ds-text-2); z-index: 1; }
.tl__item--danger .tl__dot { color: #dc2626; border-color: rgba(220,38,38,.4); }
.tl__body { min-width: 0; flex: 1; }
.tl__head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.2rem 0.6rem; font-size: 0.8rem; color: var(--ds-text); }
.tl__meta { font-size: 0.7rem; color: var(--ds-text-muted); white-space: nowrap; }
.tl__desc { font-size: 0.75rem; color: var(--ds-text-2); overflow-wrap: anywhere; }
</style>
