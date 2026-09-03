<script setup lang="ts">
/**
 * Ficha de detalle de solo lectura de una sucursal — mismo patrón que
 * CommerceDetailModal.vue, adaptado a la jerarquía de Subsidiary: hacia
 * arriba (su Commerce padre) y hacia abajo (sus propios Sellers).
 */
import { ref } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { SubsidiaryDetail } from '@/js/types/roles/subsidiary/subsidiary'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: SubsidiaryDetail | null
  error?: string | null
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  retry: []
}>()

/** Colapsable — la jerarquía no aporta valor de un vistazo, arranca cerrada. */
const showHierarchy = ref(false)
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Ficha de sucursal"
    icon="fa-code-branch"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="sd-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando ficha de la sucursal...
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
          <i class="fas fa-code-branch"></i>
        </div>
        <div class="sd-header-info">
          <h3 class="sd-name">{{ detail.name }}</h3>
          <p class="sd-username">@{{ detail.user.username }}</p>
        </div>
        <span class="badge sd-status" v-html="detail.status_badge"></span>
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
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-user-tie"></i>Vendedores</span>
            <span class="sd-field-value">{{ detail.sellers_count }}</span>
          </div>
          <div class="sd-field" v-if="detail.brand">
            <span class="sd-field-label"><i class="fas fa-tag"></i>Marca</span>
            <span class="sd-field-value">{{ detail.brand }}</span>
          </div>
          <div class="sd-field" v-if="detail.product">
            <span class="sd-field-label"><i class="fas fa-box"></i>Producto</span>
            <span class="sd-field-value">{{ detail.product }}</span>
          </div>
        </div>
      </div>

      <!-- Comercio padre -->
      <div class="sd-section">
        <h4 class="sd-section-title"><i class="fas fa-store mr-2"></i>Comercio (comercializadora)</h4>
        <div v-if="detail.commerce" class="sd-grid">
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-building"></i>Nombre</span>
            <span class="sd-field-value">{{ detail.commerce.name }}</span>
          </div>
          <div class="sd-field">
            <span class="sd-field-label"><i class="fas fa-signal"></i>Estado</span>
            <span class="badge" v-html="detail.commerce.status_badge"></span>
          </div>
        </div>
        <p v-else class="text-muted small mb-0">Sin comercio asignado.</p>
      </div>

      <!-- Jerarquía: vendedores propios de esta sucursal -->
      <div class="sd-section">
        <h4 class="sd-section-title sd-section-title--toggle" @click="showHierarchy = !showHierarchy">
          <i class="fas fa-sitemap mr-2"></i>Jerarquía
          <span class="sd-section-summary">
            {{ detail.hierarchy.sellers_count }} vendedor{{ detail.hierarchy.sellers_count === 1 ? '' : 'es' }}
          </span>
          <i class="fas sd-chevron" :class="showHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </h4>

        <div v-if="showHierarchy" class="sd-hierarchy">
          <p v-if="!detail.hierarchy.sellers.length" class="text-muted small mb-0">
            Sin vendedores registrados en esta sucursal.
          </p>

          <ul v-else class="sd-hierarchy-list">
            <li v-for="seller in detail.hierarchy.sellers" :key="seller.id" class="sd-hierarchy-item">
              <span>{{ seller.name }}</span>
              <span class="badge" v-html="seller.status_badge"></span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="sd-close-btn" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
/**
 * Estilos calcados de CommerceDetailModal.vue (.cd-*) — mismo sistema
 * visual de ficha de solo lectura, prefijo .sd- para no colisionar si
 * ambos modales llegan a montarse en la misma vista alguna vez.
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

.sd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
.sd-field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.sd-field-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.sd-field-label i { font-size: 0.68rem; opacity: 0.75; }
.sd-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }
.sd-field-value--truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.sd-section-title--toggle { cursor: pointer; user-select: none; }
.sd-section-summary {
  margin-left: auto;
  font-size: 0.72rem;
  font-weight: 500;
  color: var(--ds-text-muted);
  text-transform: none;
  letter-spacing: normal;
}
.sd-chevron { font-size: 0.7rem; opacity: 0.6; }

.sd-hierarchy { margin-top: 0.6rem; }
.sd-hierarchy-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.sd-hierarchy-item {
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
