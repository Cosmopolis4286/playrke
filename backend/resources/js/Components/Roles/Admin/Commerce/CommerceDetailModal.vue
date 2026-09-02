<script setup lang="ts">
/**
 * Ficha de detalle de solo lectura de una comercializadora — no reusa
 * AdminCommerceForm.vue (que es el form de edición) porque muestra campos
 * de dominio que el form no expone (código único, conteo de vendedores),
 * mismo criterio visual que UserDetailModal.vue pero sin sus secciones
 * específicas de jugador.
 */
import { ref } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { CommerceDetail } from '@/js/types/roles/commerce/commerce'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: CommerceDetail | null
  error?: string | null
  /** true si el usuario actual puede marcar/desmarcar el comercio principal (permiso commerces.set-default, exclusivo de superadmin). */
  canManageDefaultCommerce?: boolean
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  retry: []
  'set-default': []
  'unset-default': []
}>()

/** Colapsables — jerarquía y directorio no aportan valor de un vistazo como el resto de la ficha, arrancan cerrados. */
const showHierarchy = ref(false)
const showDirectory = ref(false)
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Ficha de comercializadora"
    icon="fa-store"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="cd-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando ficha de la comercializadora...
    </div>

    <div v-else-if="error" class="cd-error">
      <i class="fas fa-triangle-exclamation cd-error-icon"></i>
      <p class="cd-error-text">{{ error }}</p>
      <button type="button" class="cd-close-btn" @click="$emit('retry')">
        <i class="fas fa-rotate-right mr-1"></i>Reintentar
      </button>
    </div>

    <div v-else-if="detail" class="cd-detail">
      <!-- Identidad -->
      <div class="cd-header">
        <div class="cd-avatar">
          <i class="fas fa-store"></i>
        </div>
        <div class="cd-header-info">
          <h3 class="cd-name">{{ detail.name }}</h3>
          <p class="cd-username">@{{ detail.user.username }}</p>
        </div>
        <span v-if="detail.is_default_commerce" class="badge cd-default-badge">
          <i class="fas fa-star mr-1"></i>Principal
        </span>
        <span class="badge cd-status" v-html="detail.status_badge"></span>
      </div>

      <!-- Contacto -->
      <div class="cd-section">
        <h4 class="cd-section-title"><i class="fas fa-address-card mr-2"></i>Contacto</h4>
        <div class="cd-grid">
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-envelope"></i>Email</span>
            <span class="cd-field-value cd-field-value--truncate" :title="detail.user.email">{{ detail.user.email }}</span>
          </div>
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-phone"></i>Teléfono</span>
            <span class="cd-field-value">{{ detail.phone ?? '—' }}</span>
          </div>
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-globe"></i>País</span>
            <span class="cd-field-value">{{ detail.country?.name ?? '—' }}</span>
          </div>
        </div>
      </div>

      <!-- Negocio -->
      <div class="cd-section">
        <h4 class="cd-section-title"><i class="fas fa-briefcase mr-2"></i>Negocio</h4>
        <div class="cd-grid">
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-percent"></i>Comisión</span>
            <span class="cd-field-value">{{ detail.commission }}%</span>
          </div>
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-hashtag"></i>Código único</span>
            <span class="cd-field-value">{{ detail.unique_code ?? '—' }}</span>
          </div>
          <div class="cd-field">
            <span class="cd-field-label"><i class="fas fa-user-tie"></i>Vendedores</span>
            <span class="cd-field-value">{{ detail.sellers_count }}</span>
          </div>
        </div>
      </div>

      <!-- Monedas -->
      <div class="cd-section">
        <h4 class="cd-section-title"><i class="fas fa-coins mr-2"></i>Monedas activas</h4>
        <ul v-if="detail.currencies_detail.length" class="cd-chip-list">
          <li v-for="currency in detail.currencies_detail" :key="currency.id" class="cd-chip" :class="{ 'cd-chip--primary': currency.level === 1 }">
            {{ currency.symbol }} {{ currency.description }}
            <span v-if="currency.level === 1" class="cd-chip-tag">principal</span>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin monedas configuradas.</p>
      </div>

      <!-- Juegos -->
      <div class="cd-section">
        <h4 class="cd-section-title"><i class="fas fa-gamepad mr-2"></i>Juegos permitidos</h4>
        <ul v-if="detail.games_detail.length" class="cd-chip-list">
          <li v-for="game in detail.games_detail" :key="game.id" class="cd-chip">{{ game.name }}</li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin juegos habilitados.</p>
      </div>

      <!-- Jerarquía: agencias/sucursales y vendedores (directos + bajo agencia) -->
      <div class="cd-section">
        <h4 class="cd-section-title cd-section-title--toggle" @click="showHierarchy = !showHierarchy">
          <i class="fas fa-sitemap mr-2"></i>Jerarquía
          <span class="cd-section-summary">
            {{ detail.hierarchy.subsidiaries_count }} agencia{{ detail.hierarchy.subsidiaries_count === 1 ? '' : 's' }} ·
            {{ detail.hierarchy.total_sellers_count }} vendedor{{ detail.hierarchy.total_sellers_count === 1 ? '' : 'es' }}
          </span>
          <i class="fas cd-chevron" :class="showHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </h4>

        <div v-if="showHierarchy" class="cd-hierarchy">
          <p v-if="detail.hierarchy.direct_sellers_count === 0 && detail.hierarchy.subsidiaries_count === 0" class="text-muted small mb-0">
            Sin agencias ni vendedores registrados.
          </p>

          <template v-else>
            <!-- Vendedores directos (reportan al comercio sin agencia intermedia) -->
            <div v-if="detail.hierarchy.direct_sellers.length" class="cd-hierarchy-block">
              <p class="cd-hierarchy-label"><i class="fas fa-user-tie mr-1"></i>Vendedores directos</p>
              <ul class="cd-hierarchy-list">
                <li v-for="seller in detail.hierarchy.direct_sellers" :key="seller.id" class="cd-hierarchy-item">
                  <span>{{ seller.name }}</span>
                  <span class="badge" v-html="seller.status_badge"></span>
                </li>
              </ul>
            </div>

            <!-- Agencias, cada una con sus propios vendedores -->
            <div v-for="subsidiary in detail.hierarchy.subsidiaries" :key="subsidiary.id" class="cd-hierarchy-block">
              <p class="cd-hierarchy-label">
                <i class="fas fa-code-branch mr-1"></i>{{ subsidiary.name }}
                <span class="badge" v-html="subsidiary.status_badge"></span>
              </p>
              <ul v-if="subsidiary.sellers.length" class="cd-hierarchy-list cd-hierarchy-list--nested">
                <li v-for="seller in subsidiary.sellers" :key="seller.id" class="cd-hierarchy-item">
                  <span>{{ seller.name }}</span>
                  <span class="badge" v-html="seller.status_badge"></span>
                </li>
              </ul>
              <p v-else class="text-muted small mb-0 cd-hierarchy-list--nested">Sin vendedores en esta agencia.</p>
            </div>
          </template>
        </div>
      </div>

      <!-- Directorio: cuentas de cobro (Pago Móvil VE / PIX BR) del usuario dueño del comercio -->
      <div class="cd-section">
        <h4 class="cd-section-title cd-section-title--toggle" @click="showDirectory = !showDirectory">
          <i class="fas fa-address-book mr-2"></i>Directorio afiliado
          <span class="cd-section-summary">
            {{ detail.directory_accounts.pago_movil.length + detail.directory_accounts.pix_keys.length }} cuenta{{ (detail.directory_accounts.pago_movil.length + detail.directory_accounts.pix_keys.length) === 1 ? '' : 's' }}
          </span>
          <i class="fas cd-chevron" :class="showDirectory ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </h4>

        <div v-if="showDirectory" class="cd-hierarchy">
          <p v-if="!detail.directory_accounts.pago_movil.length && !detail.directory_accounts.pix_keys.length" class="text-muted small mb-0">
            Sin cuentas de cobro registradas para este usuario.
          </p>

          <template v-else>
            <div v-if="detail.directory_accounts.pago_movil.length" class="cd-hierarchy-block">
              <p class="cd-hierarchy-label"><i class="fas fa-mobile-screen mr-1"></i>Pago Móvil (Venezuela)</p>
              <ul class="cd-hierarchy-list">
                <li v-for="account in detail.directory_accounts.pago_movil" :key="account.id" class="cd-hierarchy-item">
                  <span>{{ account.holder_name }} — {{ account.bank_name ?? 'Banco' }} · {{ account.phone }}</span>
                  <span class="badge" :class="account.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'">
                    {{ account.status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}
                  </span>
                </li>
              </ul>
            </div>

            <div v-if="detail.directory_accounts.pix_keys.length" class="cd-hierarchy-block">
              <p class="cd-hierarchy-label"><i class="fas fa-qrcode mr-1"></i>PIX (Brasil)</p>
              <ul class="cd-hierarchy-list">
                <li v-for="account in detail.directory_accounts.pix_keys" :key="account.id" class="cd-hierarchy-item">
                  <span>{{ account.holder_name }} — {{ account.bank_name ?? 'Banco' }} · {{ account.pix_key_type }}: {{ account.pix_key_value }}</span>
                  <span class="badge" :class="account.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'">
                    {{ account.status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}
                  </span>
                </li>
              </ul>
            </div>
          </template>
        </div>
      </div>
    </div>

    <template #footer>
      <button
        v-if="detail && canManageDefaultCommerce && !detail.is_default_commerce && detail.status === 'ACTIVE'"
        type="button"
        class="cd-default-btn"
        @click="$emit('set-default')"
      >
        <i class="fas fa-star mr-1"></i>Marcar como principal
      </button>
      <button
        v-else-if="detail && canManageDefaultCommerce && detail.is_default_commerce"
        type="button"
        class="cd-default-btn cd-default-btn--unset"
        @click="$emit('unset-default')"
      >
        <i class="fas fa-star-half-stroke mr-1"></i>Desactivar marca de principal
      </button>
      <button type="button" class="cd-close-btn" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
/**
 * Botón local: los estilos scoped de Modal.vue no atraviesan el slot
 * #footer inyectado por el padre — mismo patrón que UserDetailModal.vue
 * (.ud-close-btn).
 */
.cd-close-btn {
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
.cd-close-btn:hover { background: var(--ds-surface-2); }

.cd-default-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  color: #fff;
  background: linear-gradient(135deg, #f5a623, #e08e0b);
  transition: all 0.15s;
  line-height: 1.4;
  margin-right: auto;
}
.cd-default-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

.cd-default-btn--unset {
  background: transparent;
  color: var(--ds-text-2);
  border: 1.5px solid var(--ds-border-2);
}
.cd-default-btn--unset:hover { background: var(--ds-surface-2); filter: none; }

.cd-default-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  color: #fff;
  background: linear-gradient(135deg, #f5a623, #e08e0b);
}

.cd-loading { display: flex; align-items: center; justify-content: center; padding: 2.5rem 0; color: var(--ds-text-muted); font-size: 0.88rem; }

.cd-error { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; padding: 2.5rem 1rem; text-align: center; }
.cd-error-icon { font-size: 1.6rem; color: #f87171; }
.cd-error-text { color: var(--ds-text-muted); font-size: 0.88rem; margin: 0; }

.cd-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--ds-border);
}
.cd-avatar {
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
.cd-header-info { flex: 1; min-width: 0; }
.cd-name { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cd-username { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0.15rem 0 0; }
.cd-status { font-size: 0.7rem; }

.cd-section { margin-bottom: 1.25rem; }
.cd-section:last-child { margin-bottom: 0; }
.cd-section-title { display: flex; align-items: center; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ds-text-muted); margin: 0 0 0.6rem; }
.cd-section-title i { opacity: 0.7; }

.cd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
.cd-field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.cd-field-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.cd-field-label i { font-size: 0.68rem; opacity: 0.75; }
.cd-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }
.cd-field-value--truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.cd-chip-list { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.5rem; }
.cd-chip {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ds-text);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  padding: 0.3rem 0.7rem;
  border-radius: 20px;
}
.cd-chip--primary { border-color: #667eea; color: #667eea; }
.cd-chip-tag { margin-left: 0.35rem; font-size: 0.65rem; opacity: 0.75; text-transform: uppercase; }

.cd-section-title--toggle { cursor: pointer; user-select: none; }
.cd-section-summary {
  margin-left: auto;
  font-size: 0.72rem;
  font-weight: 500;
  color: var(--ds-text-muted);
  text-transform: none;
  letter-spacing: normal;
}
.cd-chevron { font-size: 0.7rem; opacity: 0.6; }

.cd-hierarchy { margin-top: 0.6rem; display: flex; flex-direction: column; gap: 0.75rem; }
.cd-hierarchy-block { display: flex; flex-direction: column; gap: 0.3rem; }
.cd-hierarchy-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ds-text);
  margin: 0;
}
.cd-hierarchy-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.cd-hierarchy-list--nested { margin-left: 1.4rem; }
.cd-hierarchy-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--ds-text-2);
  padding: 0.25rem 0.5rem;
  background: var(--ds-surface-2);
  border-radius: 0.35rem;
}
</style>
