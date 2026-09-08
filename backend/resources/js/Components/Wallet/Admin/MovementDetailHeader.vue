<script setup lang="ts">
import { computed } from 'vue'
import { ageInfo } from '@js/composables/wallet/admin/useMovementFormatters'
import type { Movement } from '@js/types/wallet/admin-movement'

const props = defineProps<{
  detail: Movement
  isActionable: boolean
  typeConfig: Record<string, { label: string; cls: string; style?: string; icon: string }>
  prizeTypeConfig: Record<'DIGITAL' | 'PHYSICAL', { label: string; icon: string }>
  statusConfig: Record<string, { label: string; cls: string }>
  userName: (u: Movement['user']) => string
}>()

const age = computed(() => ageInfo(props.detail.created_at))
</script>

<template>
  <!-- ── Alerta de acción requerida ──────────────────────────────── -->
  <div v-if="isActionable" class="mv-action-banner" :class="`mv-action-banner--${age.level}`">
    <i class="fas fa-clock me-2"></i>
    Este movimiento está <strong>pendiente</strong> de revisión ({{ age.label }}).
    <template v-if="detail.type === 'WITHDRAWAL'">
      El saldo ya fue descontado del usuario.
    </template>
  </div>

  <!-- ── ID de transacción ─────────────────────────────────────── -->
  <div class="mv-txid">
    <span class="mv-txid__label">ID transacción</span>
    <span class="mv-txid__value">{{ detail.id }}</span>
  </div>

  <!-- ── Fila: Usuario + Tipo + Estado ─────────────────────────── -->
  <div class="mv-detail__row mv-detail__row--3">
    <div class="mv-detail__section">
      <p class="mv-detail__label">Usuario</p>
      <p class="mv-detail__value">{{ userName(detail.user) }}</p>
      <p v-if="detail.user" class="mv-detail__sub">{{ detail.user.email }}</p>
    </div>
    <div class="mv-detail__section">
      <p class="mv-detail__label">Tipo</p>
      <span :class="typeConfig[detail.type]?.cls ?? 'mv-badge'" :style="typeConfig[detail.type]?.style">
        <i :class="typeConfig[detail.type]?.icon"></i>
        {{ typeConfig[detail.type]?.label ?? detail.type }}
      </span>
    </div>
    <div v-if="(detail.type === 'PRIZE' || detail.type === 'BONUS_PRIZE') && detail.prize_type" class="mv-detail__section">
      <p class="mv-detail__label">Naturaleza del premio</p>
      <span class="mv-badge mv-badge--prize-sub">
        <i :class="prizeTypeConfig[detail.prize_type].icon"></i>
        {{ prizeTypeConfig[detail.prize_type].label }}
      </span>
    </div>
    <div v-if="(detail.type === 'PRIZE' || detail.type === 'BONUS_PRIZE') && detail.game_id" class="mv-detail__section">
      <p class="mv-detail__label">ID del juego/sala</p>
      <p class="mv-detail__value mv-detail__value--mono">{{ detail.game_id }}</p>
    </div>
    <div class="mv-detail__section">
      <p class="mv-detail__label">Estado</p>
      <span :class="statusConfig[detail.status]?.cls ?? 'mv-status'">
        {{ statusConfig[detail.status]?.label ?? detail.status }}
      </span>
    </div>
  </div>

  <div class="mv-detail__divider" />
</template>

<style scoped>
/* ID en modal */
.mv-txid {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.6rem;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 6px;
  margin-bottom: 0.75rem;
}
.mv-txid__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  white-space: nowrap;
}
.mv-txid__value {
  font-family: ui-monospace, monospace;
  font-size: 0.78rem;
  color: var(--ds-text-2);
  word-break: break-all;
}

/* Tipo badge (compartido con MovementsTable.vue) */
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
.mv-badge--prize-sub { background: var(--ds-surface-2); color: var(--ds-text-2); margin-left: 6px; }

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

.mv-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

.mv-detail__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.mv-detail__row--3 { grid-template-columns: 1fr 1fr 1fr; }

.mv-detail__section { display: flex; flex-direction: column; gap: 2px; }

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.mv-detail__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ds-text);
  margin: 0;
}
.mv-detail__value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.82rem;
}

.mv-detail__sub {
  font-size: 0.75rem;
  color: var(--ds-text-muted);
  margin: 0;
}

/* Banner acción requerida — color escala con la antigüedad del pendiente */
.mv-action-banner {
  border-radius: 8px;
  padding: 0.6rem 0.9rem;
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
  border: 1px solid transparent;
}
.mv-action-banner--normal   { background: rgba(217,119,6,.14); border-color: rgba(217,119,6,.4);  color: #fbbf24; }
.mv-action-banner--warning  { background: rgba(217,119,6,.14); border-color: rgba(217,119,6,.4);  color: #fbbf24; }
.mv-action-banner--critical { background: rgba(220,38,38,.16); border-color: rgba(220,38,38,.45); color: #f87171; }

.me-2 { margin-right: 0.5rem; }
</style>
