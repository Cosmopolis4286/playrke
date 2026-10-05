<script setup lang="ts">
import Modal from '@js/Components/Common/Modal/Modal.vue'
import ExternalPlayerPurchaseHistory from './ExternalPlayerPurchaseHistory.vue'
import ExternalPlayerPrizeHistory from './ExternalPlayerPrizeHistory.vue'
import ExternalPlayerAuditTrail from './ExternalPlayerAuditTrail.vue'
import type { ExternalPlayerDetail } from '@/js/types/roles/admin/external-player'

defineProps<{
  modelValue: boolean
  detailLoading: boolean
  detail: ExternalPlayerDetail | null
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

function formatFullDate(date: string): string {
  return new Date(date).toLocaleString('es-VE', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Detalle del jugador externo"
    icon="fa-info-circle"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="detailLoading" class="ep-modal-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando...
    </div>

    <div v-else-if="detail" class="ep-detail">
      <div class="ep-detail__row ep-detail__row--3">
        <div class="ep-detail__section">
          <p class="ep-detail__label">Vendedor</p>
          <p class="ep-detail__value">
            {{ detail.seller_user ? `${detail.seller_user.first_name} ${detail.seller_user.last_name} (${detail.seller_user.email})` : '—' }}
          </p>
        </div>
        <div class="ep-detail__section">
          <p class="ep-detail__label">Agencia</p>
          <p class="ep-detail__value">{{ detail.subsidiary?.name ?? '— (reporta directo al comercio)' }}</p>
        </div>
        <div class="ep-detail__section">
          <p class="ep-detail__label">Comercio</p>
          <p class="ep-detail__value">{{ detail.commerce?.name ?? '—' }}</p>
        </div>
      </div>

      <div class="ep-detail__row ep-detail__row--3 mt-2">
        <div class="ep-detail__section">
          <p class="ep-detail__label">Link de acceso</p>
          <code class="ep-detail__value ep-detail__value--mono">{{ detail.link }}</code>
        </div>
        <div class="ep-detail__section">
          <p class="ep-detail__label">Registrado</p>
          <p class="ep-detail__value">{{ detail.created_at ? formatFullDate(detail.created_at) : '—' }}</p>
        </div>
        <div class="ep-detail__section">
          <p class="ep-detail__label">Expira</p>
          <p class="ep-detail__value">{{ detail.expiration_date ? formatFullDate(detail.expiration_date) : '—' }}</p>
        </div>
      </div>

      <div class="ep-detail__row mt-2">
        <div class="ep-detail__section">
          <p class="ep-detail__label">Código de compra</p>
          <code class="ep-detail__value ep-detail__value--mono">{{ detail.purchase_code?.purchase_code ?? '—' }}</code>
        </div>
        <div class="ep-detail__section">
          <p class="ep-detail__label">Convertido a usuario</p>
          <p class="ep-detail__value">{{ detail.converted_user ? `@${detail.converted_user.username}` : '—' }}</p>
        </div>
      </div>

      <ExternalPlayerPurchaseHistory :detail="detail" />
      <ExternalPlayerPrizeHistory :detail="detail" />
      <ExternalPlayerAuditTrail :detail="detail" />
    </div>
  </Modal>
</template>

<style scoped>
.ep-modal-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: var(--ds-text-2);
  font-size: 0.85rem;
}

.ep-detail { display: flex; flex-direction: column; gap: 0; }

.ep-detail__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.ep-detail__row--3 { grid-template-columns: 1fr 1fr 1fr; }

.ep-detail__section { display: flex; flex-direction: column; gap: 2px; min-width: 0; }

.ep-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.ep-detail__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ds-text);
  margin: 0;
  overflow-wrap: break-word;
}
.ep-detail__value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.78rem;
  background: var(--ds-surface-2);
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-block;
}

.mt-2 { margin-top: 0.5rem; }
.me-2 { margin-right: 0.5rem; }
</style>
