<script setup lang="ts">
import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  columns: { key: string; label: string; sortable: boolean }[]
  movements: Movement[]
  typeConfig: Record<string, { label: string; cls: string; style?: string; icon: string }>
  prizeTypeConfig: Record<'DIGITAL' | 'PHYSICAL', { label: string; icon: string }>
  statusConfig: Record<string, { label: string; cls: string }>
  userName: (u: Movement['user']) => string
  formatAmount: (mov: Movement) => string
  formatDate: (iso: string) => string
}>()

defineEmits<{
  view: [id: string]
}>()
</script>

<template>
  <BaseTable
    table-id="wallet-movements"
    :columns="columns"
    :rows="movements"
    empty-message="No hay movimientos registrados"
    class="mt-3"
  >
    <template #cell-id="{ row }">
      <span class="mv-id">{{ String((row as Movement).id).slice(-8) }}</span>
    </template>

    <template #cell-user="{ row }">
      <div v-if="(row as Movement).user" class="mv-user">
        <span class="mv-user__name">{{ userName((row as Movement).user) }}</span>
        <span class="mv-user__email">{{ (row as Movement).user!.email }}</span>
      </div>
      <span v-else class="text-muted">—</span>
    </template>

    <template #cell-type="{ row }">
      <span
        :class="typeConfig[(row as Movement).type]?.cls ?? 'mv-badge'"
        :style="typeConfig[(row as Movement).type]?.style"
      >
        <i :class="typeConfig[(row as Movement).type]?.icon"></i>
        {{ typeConfig[(row as Movement).type]?.label ?? (row as Movement).type }}
      </span>
      <span v-if="(row as Movement).type === 'PRIZE' && (row as Movement).prize_type" class="mv-badge mv-badge--prize-sub">
        <i :class="prizeTypeConfig[(row as Movement).prize_type!].icon"></i>
        {{ prizeTypeConfig[(row as Movement).prize_type!].label }}
      </span>
    </template>

    <template #cell-amount="{ row }">
      <span
        class="mv-amount"
        :class="['DEPOSIT','PRIZE'].includes((row as Movement).type) ? 'mv-amount--positive' : 'mv-amount--negative'"
      >
        {{ formatAmount(row as Movement) }}
      </span>
    </template>

    <template #cell-currency="{ row }">
      <span v-if="(row as Movement).currency" class="mv-currency">
        {{ (row as Movement).currency!.symbol }} {{ (row as Movement).currency!.iso_code }}
      </span>
      <span v-else class="text-muted">—</span>
    </template>

    <template #cell-status="{ row }">
      <span :class="statusConfig[(row as Movement).status]?.cls ?? 'mv-status'">
        {{ statusConfig[(row as Movement).status]?.label ?? (row as Movement).status }}
      </span>
    </template>

    <template #cell-created_at="{ row }">
      <span class="mv-date">{{ formatDate((row as Movement).created_at) }}</span>
    </template>

    <template #cell-actions="{ row }">
      <button
        class="btn btn-sm btn-outline-info"
        title="Ver detalle"
        @click="$emit('view', String((row as Movement).id))"
      >
        <i class="fas fa-eye"></i>
      </button>
    </template>
  </BaseTable>
</template>

<style scoped>
/* ID en tabla */
.mv-id {
  font-family: ui-monospace, monospace;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  background: var(--ds-surface-2);
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
}

/* Usuario */
.mv-user        { display: flex; flex-direction: column; gap: 1px; }
.mv-user__name  { font-size: 0.85rem; font-weight: 600; color: var(--ds-text); }
.mv-user__email { font-size: 0.72rem; color: var(--ds-text-muted); }

/* Tipo badge */
.mv-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-badge--deposit      { background: rgba(22,163,74,.16);  color: #4ade80; }
.mv-badge--withdrawal   { background: rgba(217,119,6,.18);  color: #fbbf24; }
.mv-badge--purchase     { background: rgba(225,29,72,.16);  color: #fb7185; }
.mv-badge--prize        { background: rgba(202,138,4,.18);  color: #facc15; }
.mv-badge--prize-sub    { background: var(--ds-surface-2);  color: var(--ds-text-2); margin-left: 6px; }
.mv-badge--trivia-entry { background: rgba(124,58,237,.18); color: #a78bfa; }

/* Monto */
.mv-amount           { font-size: 0.875rem; font-weight: 700; white-space: nowrap; }
.mv-amount--positive { color: #4ade80; }
.mv-amount--negative { color: #f87171; }

/* Moneda */
.mv-currency { font-size: 0.78rem; font-weight: 600; color: var(--ds-text-2); }

/* Estado */
.mv-status {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-status--pending   { background: rgba(217,119,6,.18); color: #fbbf24; }
.mv-status--completed { background: rgba(22,163,74,.16); color: #4ade80; }
.mv-status--cancelled { background: var(--ds-surface-2); color: var(--ds-text-2); }
.mv-status--rejected  { background: rgba(225,29,72,.16); color: #fb7185; }

/* Fecha */
.mv-date { font-size: 0.78rem; color: var(--ds-text-2); white-space: nowrap; }
</style>
