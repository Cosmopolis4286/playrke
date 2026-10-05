<script setup lang="ts">
/**
 * "Mi Cliente": estado de la integración B2B vía API (endpoint, clave
 * enmascarada) y su actividad — usuarios que llegan por API (CustomerUser) y
 * salas de bingo que el cliente crea.
 */
import { ref } from 'vue'
import RpCard from './RpCard.vue'
import type { CustomerProfile } from '@/js/types/roles/shared/role-profile-common'
import { formatInteger } from '@/js/composables/roles/shared/profile/useRoleProfile'

defineProps<{ integration: CustomerProfile['integration']; businessName: string | null }>()

const copied = ref(false)
async function copyEndpoint(value: string) {
  try {
    await navigator.clipboard.writeText(value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch { /* portapapeles no disponible */ }
}
</script>

<template>
  <RpCard title="Integración API" icon="fa-plug" :subtitle="businessName ?? 'Conexión de tu plataforma con PlayRKE'">
    <template #actions>
      <span :class="['rp-int-status', integration.api_route && integration.key_hint ? 'rp-int-status--ok' : 'rp-int-status--warn']">
        <span class="rp-dot"></span>{{ integration.api_route && integration.key_hint ? 'Configurada' : 'Incompleta' }}
      </span>
    </template>

    <dl class="rp-int-fields">
      <div class="rp-int-field">
        <dt>Endpoint</dt>
        <dd>
          <template v-if="integration.api_route">
            <code class="rp-int-code">{{ integration.api_route }}</code>
            <button type="button" class="rp-copy" :title="copied ? 'Copiado' : 'Copiar'" @click="copyEndpoint(integration.api_route)">
              <i :class="['fas', copied ? 'fa-check' : 'fa-copy']"></i>
            </button>
          </template>
          <span v-else class="rp-int-missing">Sin configurar</span>
        </dd>
      </div>
      <div class="rp-int-field">
        <dt>Clave de cliente</dt>
        <dd>
          <code v-if="integration.key_hint" class="rp-int-code">{{ integration.key_hint }}</code>
          <span v-else class="rp-int-missing">Sin configurar</span>
          <small class="rp-int-hint">Por seguridad solo se muestran los últimos caracteres.</small>
        </dd>
      </div>
    </dl>

    <div class="rp-int-stats">
      <div class="rp-int-stat">
        <i class="fas fa-users"></i>
        <span class="rp-int-stat__value">{{ formatInteger(integration.users_total) }}</span>
        <span class="rp-int-stat__label">Usuarios integrados</span>
      </div>
      <div class="rp-int-stat">
        <i class="fas fa-user-check"></i>
        <span class="rp-int-stat__value">{{ formatInteger(integration.users_active) }}</span>
        <span class="rp-int-stat__label">Activos 30 días</span>
      </div>
      <a href="/bingo-rooms" class="rp-int-stat rp-int-stat--link">
        <i class="fas fa-dice"></i>
        <span class="rp-int-stat__value">{{ formatInteger(integration.rooms_active) }}<small> / {{ formatInteger(integration.rooms_total) }}</small></span>
        <span class="rp-int-stat__label">Salas activas</span>
      </a>
    </div>
  </RpCard>
</template>

<style scoped>
.rp-int-status { display: inline-flex; align-items: center; gap: 0.35rem; padding: 2px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
.rp-int-status--ok { background: rgba(22, 163, 74, 0.15); color: #22c55e; }
.rp-int-status--warn { background: rgba(217, 119, 6, 0.16); color: #f59e0b; }
.rp-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.rp-int-fields { display: flex; flex-direction: column; gap: 0.75rem; margin: 0; }
.rp-int-field dt { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-int-field dd { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; margin: 0.25rem 0 0; min-width: 0; }
.rp-int-code {
  padding: 4px 9px; border-radius: 7px;
  background: var(--ds-surface-2); color: var(--ds-text);
  font-size: 0.8rem; overflow-wrap: anywhere;
}
.rp-int-missing { font-size: 0.82rem; font-weight: 600; color: #f59e0b; }
.rp-int-hint { flex-basis: 100%; font-size: 0.7rem; color: var(--ds-text-muted); }
.rp-copy {
  width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;
  border: none; border-radius: 7px; background: transparent; color: var(--ds-text-muted); cursor: pointer; font-size: 0.72rem;
}
.rp-copy:hover { background: var(--ds-surface-2); color: var(--ds-text); }

.rp-int-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.6rem; margin-top: 1rem; }
.rp-int-stat {
  display: flex; flex-direction: column; gap: 0.1rem;
  padding: 0.7rem 0.85rem; border-radius: 12px; background: var(--ds-surface-2);
  color: inherit; text-decoration: none;
}
.rp-int-stat i { font-size: 0.8rem; color: #7c8cf0; margin-bottom: 0.2rem; }
.rp-int-stat__value { font-size: 1.3rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.rp-int-stat__value small { font-size: 0.78rem; font-weight: 600; color: var(--ds-text-muted); }
.rp-int-stat__label { font-size: 0.72rem; color: var(--ds-text-muted); }
.rp-int-stat--link:hover { background: var(--ds-border); text-decoration: none; color: inherit; }
</style>
