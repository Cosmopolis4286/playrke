<script setup lang="ts">
import { computed } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

const props = defineProps<{
  room: BingoRoom
  totalPct: number
  jackpotTypeOptions?: { value: string; code: string; label: string }[]
}>()

const jackpotTypeLabel = computed(() => {
  const id = props.room.config?.jackpot_type_id
  if (!id) return null
  return props.jackpotTypeOptions?.find(o => String(o.value) === String(id))?.label ?? null
})
</script>

<template>
  <div class="details-section">
    <h6 class="section-title">Distribución de Porcentajes</h6>
    <div class="details-grid">
      <div class="detail-item">
        <span class="detail-label">%Sala (comisión)</span>
        <span class="detail-value">{{ fmt.pct(room.config?.room_commission) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">%RKE</span>
        <span class="detail-value">{{ fmt.pct(room.config?.rke_commission) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">%Jackpot</span>
        <span class="detail-value">{{ room.config?.jackpot_pct ? fmt.pct(room.config?.jackpot_pct) : 'No aplica' }}</span>
      </div>
      <div v-if="jackpotTypeLabel" class="detail-item">
        <span class="detail-label">Destino del jackpot</span>
        <span class="detail-value">{{ jackpotTypeLabel }}</span>
      </div>
      <div v-if="Number(room.config?.rounds ?? 1) > 1" class="detail-item">
        <span class="detail-label">%Rondas</span>
        <span class="detail-value">{{ fmt.pct(room.config?.rounds_pct) }}</span>
      </div>
      <div v-if="room.config?.use_serials" class="detail-item">
        <span class="detail-label">%Seriales</span>
        <span class="detail-value">{{ fmt.pct(room.config?.serials_pct) }}</span>
      </div>
      <div class="detail-item" :class="totalPct > 100 ? 'highlight-danger' : 'highlight'">
        <span class="detail-label">Total distribuido <small class="text-muted">(sin RKE)</small></span>
        <span class="detail-value font-weight-bold" :class="totalPct > 100 ? 'text-danger' : 'text-success'">{{ totalPct }}%</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.details-section {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--ds-border);
}

.section-title {
  color: var(--ds-text-2);
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 0.65rem;
  padding-bottom: 0.4rem;
  border-bottom: 2px solid #007bff;
  display: inline-block;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 0.6rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  padding: 0.45rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 0.375rem;
  border-left: 3px solid transparent;
}
.detail-item.highlight { background: #e8f4fd; border-left-color: #007bff; }
.detail-item.highlight .detail-label { color: #0b3d6b; }
.detail-item.highlight-danger { background: #fdecea; border-left-color: #dc3545; }
.detail-item.highlight-danger .detail-label { color: #7a1f1f; }

.detail-label {
  font-size: 0.7rem;
  color: var(--ds-text-2);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value { font-size: 0.875rem; color: var(--ds-text); }

.font-weight-bold { font-weight: 600 !important; }
.text-success { color: #28a745 !important; }
.text-danger  { color: #dc3545 !important; }
.text-muted   { color: var(--ds-text-2) !important; }
</style>
