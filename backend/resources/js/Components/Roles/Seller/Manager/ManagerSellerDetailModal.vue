<script setup lang="ts">
/**
 * Ficha de detalle de solo lectura de un vendedor — no reusa
 * ManagerSellerForm.vue (que es el form de edición) porque muestra campos
 * de dominio que el form no expone (a quién reporta, saldos de wallet,
 * actividad de ventas), mismo criterio visual que CommerceDetailModal.vue.
 */
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { SellerDetail } from '@/js/types/roles/seller/seller'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: SellerDetail | null
  error?: string | null
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  retry: []
}>()
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Ficha de vendedor"
    icon="fa-user-tie"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="sd-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando ficha del vendedor...
    </div>

    <div v-else-if="error" class="sd-error">
      <i class="fas fa-triangle-exclamation sd-error-icon"></i>
      <p class="sd-error-text">{{ error }}</p>
      <button type="button" class="sd-close-btn" @click="$emit('retry')">
        <i class="fas fa-rotate-right mr-1"></i>Reintentar
      </button>
    </div>

    <div v-else-if="detail" class="sd-detail">
      <!-- Identidad -->
      <div class="sd-header">
        <div class="sd-avatar">
          <i class="fas fa-user-tie"></i>
        </div>
        <div class="sd-header-info">
          <h3 class="sd-name">{{ detail.name }}</h3>
          <p class="sd-username">@{{ detail.user.username }}</p>
        </div>
        <span class="badge sd-status" v-html="detail.status_badge"></span>
      </div>

      <!-- A quién reporta -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-sitemap mr-2"></i>Reporta a</h4>
        <div v-if="detail.reports_to.commerce" class="sd-hierarchy-path">
          <span class="sd-hierarchy-node">
            <i class="fas fa-building mr-1"></i>{{ detail.reports_to.commerce.name }}
          </span>
          <template v-if="detail.reports_to.subsidiary">
            <i class="fas fa-chevron-right sd-hierarchy-arrow"></i>
            <span class="sd-hierarchy-node sd-hierarchy-node--subsidiary">
              <i class="fas fa-code-branch mr-1"></i>{{ detail.reports_to.subsidiary.name }}
            </span>
          </template>
        </div>
        <p v-else class="text-muted small mb-0">Sin comercio asignado.</p>
      </div>

      <!-- Contacto -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-address-card mr-2"></i>Contacto</h4>
        <div class="sd-grid">
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-envelope"></i>Email</span>
            <span class="sd-field-value sd-field-value--truncate" :title="detail.user.email">{{ detail.user.email }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-phone"></i>Teléfono</span>
            <span class="sd-field-value">{{ detail.phone ?? '—' }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-globe"></i>País</span>
            <span class="sd-field-value">{{ detail.country?.name ?? '—' }}</span>
          </div>
        </div>
      </div>

      <!-- Negocio -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-briefcase mr-2"></i>Negocio</h4>
        <div class="sd-grid">
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-percent"></i>Comisión</span>
            <span class="sd-field-value">{{ detail.commission }}%</span>
          </div>
        </div>
      </div>

      <!-- Actividad del mes -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-chart-line mr-2"></i>Ventas este mes</h4>
        <div class="sd-grid">
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-ticket-alt"></i>Cartones vendidos</span>
            <span class="sd-field-value">{{ detail.activity.cards_sold }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-receipt"></i>Lotes de venta</span>
            <span class="sd-field-value">{{ detail.activity.lots_count }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-sack-dollar"></i>Monto vendido</span>
            <span class="sd-field-value">{{ detail.activity.sale_amount.toFixed(2) }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-hand-holding-dollar"></i>Comisión generada</span>
            <span class="sd-field-value">{{ detail.activity.commission_amount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Saldos de wallet -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-wallet mr-2"></i>Saldos</h4>
        <ul v-if="detail.wallet_balances.length" class="sd-chip-list">
          <li v-for="(wallet, idx) in detail.wallet_balances" :key="idx" class="sd-chip">
            {{ wallet.currency.symbol ?? wallet.currency.iso_code }} {{ wallet.balance.toFixed(2) }}
            <span v-if="wallet.bonus_balance > 0" class="sd-chip-tag">+{{ wallet.bonus_balance.toFixed(2) }} bono</span>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin wallet activa.</p>
      </div>

      <!-- Monedas -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-coins mr-2"></i>Monedas activas</h4>
        <ul v-if="detail.currencies_detail.length" class="sd-chip-list">
          <li v-for="currency in detail.currencies_detail" :key="currency.id" class="sd-chip" :class="{ 'sd-chip--primary': currency.level === 1 }">
            {{ currency.symbol }} {{ currency.description }}
            <span v-if="currency.level === 1" class="sd-chip-tag">principal</span>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin monedas configuradas.</p>
      </div>

      <!-- Juegos -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-gamepad mr-2"></i>Juegos permitidos</h4>
        <ul v-if="detail.games_detail.length" class="sd-chip-list">
          <li v-for="game in detail.games_detail" :key="game.id" class="sd-chip">{{ game.name }}</li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin juegos habilitados.</p>
      </div>
    </div>

    <template #footer>
      <button type="button" class="sd-close-btn" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
/**
 * Botón local: los estilos scoped de Modal.vue no atraviesan el slot
 * #footer inyectado por el padre — mismo patrón que CommerceDetailModal.vue.
 */
.sd-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: 1.5px solid var(--ds-border-2);
  background: transparent;
  color: var(--ds-text-2);
  transition: all 0.15s;
  line-height: 1.4;
}
.sd-close-btn:hover { background: var(--ds-surface-2); }

.sd-loading { display: flex; align-items: center; justify-content: center; padding: 2.5rem 0; color: var(--ds-text-muted); font-size: 0.88rem; }

.sd-error { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; padding: 2.5rem 1rem; text-align: center; }
.sd-error-icon { font-size: 1.6rem; color: #f87171; }
.sd-error-text { color: var(--ds-text-muted); font-size: 0.88rem; margin: 0; }

.sd-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--ds-border);
}
.sd-avatar {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #fff;
  background: linear-gradient(135deg, #667eea, #764ba2);
}
.sd-header-info { flex: 1; min-width: 0; }
.sd-name { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sd-username { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0.15rem 0 0; }
.sd-status { font-size: 0.7rem; }

.sd-section { margin-bottom: 1.25rem; }
.sd-section:last-child { margin-bottom: 0; }
.sd-section-title { display: flex; align-items: center; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ds-text-muted); margin: 0 0 0.6rem; }
.sd-section-title i { opacity: 0.7; }

.sd-hierarchy-path { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
.sd-hierarchy-node {
  display: inline-flex;
  align-items: center;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
}
.sd-hierarchy-node--subsidiary { border-color: #14b8a6; color: #14b8a6; }
.sd-hierarchy-arrow { font-size: 0.7rem; color: var(--ds-text-muted); opacity: 0.6; }

.sd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
.sd-field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.sd-field-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.sd-field-label i { font-size: 0.68rem; opacity: 0.75; }
.sd-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }
.sd-field-value--truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.sd-chip-list { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.5rem; }
.sd-chip {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ds-text);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  padding: 0.3rem 0.7rem;
  border-radius: 20px;
}
.sd-chip--primary { border-color: #667eea; color: #667eea; }
.sd-chip-tag { margin-left: 0.35rem; font-size: 0.65rem; opacity: 0.75; text-transform: uppercase; }
</style>
