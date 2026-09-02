<script setup lang="ts">
/**
 * Ficha de detalle de solo lectura de una comercializadora — no reusa
 * AdminCommerceForm.vue (que es el form de edición) porque muestra campos
 * de dominio que el form no expone (código único, conteo de vendedores),
 * mismo criterio visual que UserDetailModal.vue pero sin sus secciones
 * específicas de jugador.
 */
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { CommerceDetail } from '@/js/types/roles/commerce/commerce'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: CommerceDetail | null
  error?: string | null
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  retry: []
  'set-default': []
}>()
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
    </div>

    <template #footer>
      <button
        v-if="detail && !detail.is_default_commerce && detail.status === 'ACTIVE'"
        type="button"
        class="cd-default-btn"
        @click="$emit('set-default')"
      >
        <i class="fas fa-star mr-1"></i>Marcar como principal
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
</style>
