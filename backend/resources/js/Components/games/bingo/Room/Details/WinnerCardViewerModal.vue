<script setup lang="ts">
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'
import type { WinnerEntry } from '@js/composables/games/bingo/room/useWinnerCardViewer'

defineProps<{
  open: boolean
  winner: WinnerEntry | null
  data: { serialNumber: string; cardMatrix: Record<string, (number | null)[]> } | null
  error: boolean
  cols: { label: string; min: number; max: number; freeRow: number | null; bg: string }[]
  rows: number
  currencyIso: (currencyId?: string | number | null) => string
  winnerUsername: (w: WinnerEntry) => string
  isFreeCell: (col: { freeRow: number | null }, r: number) => boolean
  cellNum: (colLabel: string, r: number) => number | null
}>()

const emit = defineEmits<{
  close: []
  retry: [winner: WinnerEntry]
}>()
</script>

<template>
  <Teleport to="body">
    <Transition name="wd-modal">
      <div v-if="open" class="wd-overlay" @click.self="emit('close')">
        <div class="wd-modal">
          <div class="wd-modal__header">
            <span class="wd-modal__title"><i class="fas fa-id-card" /> Cartón Ganador</span>
            <button class="wd-modal__close" @click="emit('close')" aria-label="Cerrar"><i class="fas fa-times" /></button>
          </div>

          <!-- Resumen del ganador: visible en todos los estados (loading/error/ok) -->
          <div v-if="winner" class="wd-winner-summary">
            <div class="wd-winner-summary__name">
              {{ winnerUsername(winner) }}
              <span class="winner-id">#{{ winner.user_id }}</span>
            </div>
            <div class="wd-winner-summary__meta">
              <span v-if="winner.round_number" class="wd-chip"><i class="fas fa-flag-checkered"></i> Ronda {{ winner.round_number }}</span>
              <span v-if="winner.winning_pattern" class="wd-chip"><i class="fas fa-shapes"></i> {{ winner.winning_pattern }}</span>
              <span class="wd-chip wd-chip--prize">
                <i class="fas fa-coins"></i>
                {{ currencyIso(winner.currency_id) }} {{ fmt.localAmount(winner.local_amount) }}
              </span>
            </div>
          </div>

          <div v-if="data" class="wd-modal__body">
            <div class="wd-card-serial">
              <i class="fas fa-barcode"></i>
              <span class="wd-card-serial__label">Serial</span>
              <span class="wd-card-serial__value">{{ data.serialNumber }}</span>
            </div>
            <!-- card grid -->
            <div class="wd-card">
              <div class="wd-card__head" :style="{ gridTemplateColumns: `repeat(${cols.length}, 1fr)` }">
                <span v-for="col in cols" :key="col.label" class="wd-col-hdr" :style="{ background: col.bg }">{{ col.label }}</span>
              </div>
              <div class="wd-card__body" :style="{ gridTemplateColumns: `repeat(${cols.length}, 1fr)` }">
                <template v-for="col in cols" :key="col.label">
                  <div
                    v-for="r in rows" :key="`${col.label}${r}`"
                    class="wd-cell"
                    :class="{
                      'wd-cell--free': isFreeCell(col, r),
                      'wd-cell--num': !isFreeCell(col, r) && cellNum(col.label, r) !== null,
                      'wd-cell--empty': cellNum(col.label, r) === null && !isFreeCell(col, r),
                    }"
                  >
                    <span v-if="isFreeCell(col, r)" class="wd-free">★</span>
                    <span v-else-if="cellNum(col.label, r) !== null">{{ cellNum(col.label, r) }}</span>
                  </div>
                </template>
              </div>
            </div>
          </div>
          <div v-else-if="error" class="wd-modal__body wd-modal__body--state">
            <i class="fas fa-exclamation-circle wd-state-icon wd-state-icon--error"></i>
            <p class="wd-state-text">No se pudo cargar el cartón ganador.</p>
            <button class="wd-retry-btn" @click="winner && emit('retry', winner)">
              <i class="fas fa-rotate-right"></i> Reintentar
            </button>
          </div>
          <div v-else class="wd-modal__body wd-modal__body--state">
            <i class="fas fa-spinner fa-spin wd-state-icon"></i>
            <p class="wd-state-text">Cargando cartón...</p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.wd-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.wd-modal {
  width: 100%; max-width: 380px;
  background: #fff; border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  overflow: hidden;
}
.wd-modal__header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  background: #212529; color: #fff;
}
.wd-modal__title { font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.wd-modal__close {
  width: 24px; height: 24px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2);
  background: transparent; color: #adb5bd; font-size: 0.75rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.wd-modal__close:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* Resumen del ganador (visible en loading/error/ok) */
.wd-winner-summary {
  padding: 12px 16px 10px;
  border-bottom: 1px solid #f0f2f5;
}
.wd-winner-summary__name { font-size: 0.88rem; font-weight: 700; color: #212529; margin-bottom: 6px; }
.wd-winner-summary__meta { display: flex; flex-wrap: wrap; gap: 6px; }
.winner-id { font-size: 0.68rem; font-weight: 400; color: #adb5bd; margin-left: 4px; }
.wd-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 0.68rem; font-weight: 600; color: #495057;
  background: #f1f3f5; border-radius: 999px; padding: 3px 9px;
}
.wd-chip i { font-size: 0.62rem; }
.wd-chip--prize { background: #eafbf1; color: #0f5132; }

.wd-modal__body { padding: 14px; }
.wd-modal__body--state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; min-height: 140px; color: #6c757d; text-align: center;
}
.wd-state-icon { font-size: 1.8rem; color: #adb5bd; }
.wd-state-icon--error { color: #dc3545; }
.wd-state-text { font-size: 0.8rem; margin: 0; }
.wd-retry-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 0.75rem; font-weight: 600; color: #007bff;
  background: #e7f1ff; border: none; border-radius: 6px;
  padding: 6px 12px; cursor: pointer; transition: background 0.14s;
}
.wd-retry-btn:hover { background: #d5e8ff; }

.wd-card-serial {
  display: flex; align-items: center; gap: 7px;
  background: #212529; border-radius: 6px;
  padding: 7px 10px; margin-bottom: 12px;
}
.wd-card-serial i { color: #ffc107; font-size: 0.8rem; }
.wd-card-serial__label {
  font-size: 0.62rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.5px; color: #adb5bd;
}
.wd-card-serial__value {
  font-family: monospace; font-size: 0.82rem; font-weight: 700;
  color: #fff; letter-spacing: 0.5px; margin-left: auto;
}

.wd-card { border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6; }
.wd-card__head { display: grid; }
.wd-col-hdr {
  padding: 7px 4px; text-align: center;
  font-family: 'Anton','Impact',sans-serif; font-size: 14px; font-weight: 900;
  color: #fff; letter-spacing: 0.5px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.wd-card__body { display: grid; background: #fff; }
.wd-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid #e9ecef;
  font-size: 12px; font-weight: 700; color: #212529;
}
.wd-cell--free { background: linear-gradient(135deg,rgba(42,245,152,0.18),rgba(0,158,253,0.12)); }
.wd-cell--empty { background: #f8f9fa; }
.wd-free {
  font-size: 16px;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

/* Modal transition */
.wd-modal-enter-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.wd-modal-enter-from  { opacity: 0; transform: scale(0.95); }
.wd-modal-leave-active { transition: opacity 0.14s ease; }
.wd-modal-leave-to    { opacity: 0; }
</style>
