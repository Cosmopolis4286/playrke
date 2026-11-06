<script setup lang="ts">
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  detail: Movement
  directoryTypeLabel: Record<string, string>
  adminName: (u: Movement['processed_by']) => string
  formatDate: (iso: string) => string
}>()
</script>

<template>
  <!-- ── Directorio destino (WITHDRAWAL) ───────────────────────── -->
  <template v-if="detail.type === 'WITHDRAWAL' && detail.metadata">
    <div class="mv-detail__section">
      <p class="mv-detail__label">Directorio destino</p>
      <div class="mv-dir-info">
        <span class="mv-dir-info__type">
          <i class="fas fa-wallet me-1" />
          {{ directoryTypeLabel[detail.metadata.directory_type ?? ''] ?? detail.metadata.directory_type }}
        </span>
        <span class="mv-dir-info__id mv-detail__value--mono">
          ID: {{ detail.metadata.directory_id }}
        </span>
      </div>
    </div>
    <div class="mv-detail__divider" />
  </template>

  <!-- ── Descripción ─────────────────────────────────────────────── -->
  <div v-if="detail.description" class="mv-detail__section">
    <p class="mv-detail__label">Descripción</p>
    <p class="mv-detail__value">{{ detail.description }}</p>
    <div class="mv-detail__divider" />
  </div>

  <!-- ── Procesado por ───────────────────────────────────────────── -->
  <template v-if="detail.processed_by">
    <div class="mv-detail__row">
      <div class="mv-detail__section">
        <p class="mv-detail__label">Procesado por</p>
        <p class="mv-detail__value">{{ adminName(detail.processed_by) }}</p>
        <p class="mv-detail__sub">{{ detail.processed_by.email }}</p>
      </div>
      <div class="mv-detail__section">
        <p class="mv-detail__label">Procesado el</p>
        <p class="mv-detail__value">{{ detail.processed_at ? formatDate(detail.processed_at) : '—' }}</p>
      </div>
    </div>
    <div v-if="detail.admin_notes" class="mv-detail__section mt-2">
      <p class="mv-detail__label">Notas internas (solo administración)</p>
      <p class="mv-detail__value mv-admin-notes">{{ detail.admin_notes }}</p>
    </div>
    <div v-if="detail.player_message" class="mv-detail__section mt-2">
      <p class="mv-detail__label">Motivo comunicado al jugador</p>
      <p class="mv-detail__value mv-admin-notes">{{ detail.player_message }}</p>
    </div>
    <div class="mv-detail__divider" />
  </template>

  <!-- ── Fecha ───────────────────────────────────────────────────── -->
  <div class="mv-detail__section">
    <p class="mv-detail__label">Fecha de solicitud</p>
    <p class="mv-detail__value">{{ formatDate(detail.created_at) }}</p>
  </div>
</template>

<style scoped>
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

/* Directorio info */
.mv-dir-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: 4px;
}
.mv-dir-info__type {
  display: inline-flex;
  align-items: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #667eea;
}
.mv-dir-info__id {
  font-size: 0.75rem;
  color: var(--ds-text-2);
  font-family: ui-monospace, monospace;
}

/* Admin notes */
.mv-admin-notes {
  background: var(--ds-surface-2);
  border-left: 3px solid var(--ds-text-muted);
  padding: 0.4rem 0.6rem;
  border-radius: 0 6px 6px 0;
  font-style: italic;
  color: var(--ds-text-2);
}

.mt-2 { margin-top: 0.5rem; }
.me-1 { margin-right: 0.25rem; }
</style>
