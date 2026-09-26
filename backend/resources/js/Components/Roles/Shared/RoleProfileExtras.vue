<script setup lang="ts">
/**
 * Secciones compartidas entre las 3 fichas de autogestión (Commerce/
 * Subsidiary/Seller "Mi ..."): Billetera, Comisión/Config, Red de negocio y
 * Actividad reciente. Antes cada ficha solo mostraba nombre/teléfono/país/
 * email — todo lo demás (saldo, comisión, monedas/juegos permitidos, red,
 * historial) existía en el modelo pero nunca se exponía en la UI.
 *
 * `network` es opcional (Seller no tiene sub-red propia, solo "reports_to"
 * que cada página ya muestra por su cuenta) y sus items son configurables
 * para no atarse a la forma exacta de Commerce vs Subsidiary.
 */
import { computed } from 'vue'
import type { RoleProfileWallet, RoleProfileMovement, RoleProfileAccountMeta } from '@/js/types/roles/shared/role-profile-common'

interface NetworkItem {
  icon: string
  label: string
  value: number
  href?: string
}

const props = defineProps<{
  commission?: number | null
  currencies?: string[]
  games?: string[]
  network?: NetworkItem[]
  wallets: RoleProfileWallet[]
  recentMovements: RoleProfileMovement[]
  accountMeta: RoleProfileAccountMeta
}>()

const hasConfigSection = computed(() =>
  props.commission !== undefined && props.commission !== null
  || (props.currencies?.length ?? 0) > 0
  || (props.games?.length ?? 0) > 0
)

function formatAmount(value: number): string {
  return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}

function formatDateTime(iso: string): string {
  return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}

/** Tiempo relativo simple ("hace 3 días") — sin librería, suficiente para este contexto. */
function timeAgo(iso: string): string {
  const diffMs = Date.now() - new Date(iso).getTime()
  const minutes = Math.floor(diffMs / 60000)
  if (minutes < 1) return 'justo ahora'
  if (minutes < 60) return `hace ${minutes} min`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `hace ${hours} h`
  const days = Math.floor(hours / 24)
  if (days < 30) return `hace ${days} d`
  const months = Math.floor(days / 30)
  if (months < 12) return `hace ${months} mes(es)`
  return `hace ${Math.floor(months / 12)} año(s)`
}

const movementStatusConfig: Record<RoleProfileMovement['status'], { label: string; cls: string }> = {
  PENDING:   { label: 'Pendiente',  cls: 'rpe-status rpe-status--pending' },
  COMPLETED: { label: 'Completado', cls: 'rpe-status rpe-status--completed' },
  CANCELLED: { label: 'Cancelado',  cls: 'rpe-status rpe-status--cancelled' },
  REJECTED:  { label: 'Rechazado',  cls: 'rpe-status rpe-status--rejected' },
}
</script>

<template>
  <!-- Billetera -->
  <div class="sd-section">
    <h4 class="sd-section-title"><i class="fas fa-wallet mr-2"></i>Billetera</h4>
    <div v-if="wallets.length" class="rpe-wallets">
      <div v-for="wallet in wallets" :key="wallet.currency" class="rpe-wallet-card">
        <div class="rpe-wallet-card__currency">{{ wallet.symbol }} {{ wallet.currency }}</div>
        <div class="rpe-wallet-card__balance">{{ formatAmount(wallet.balance) }}</div>
        <div v-if="wallet.bonus_balance > 0" class="rpe-wallet-card__bonus">
          <i class="fas fa-gift mr-1"></i>+{{ formatAmount(wallet.bonus_balance) }} bono
        </div>
      </div>
      <a href="/wallet" class="rpe-wallet-link">
        Ver movimientos <i class="fas fa-arrow-right ml-1"></i>
      </a>
    </div>
    <p v-else class="text-muted small mb-0">Sin billetera asociada.</p>
  </div>

  <!-- Red de negocio -->
  <div v-if="network && network.length" class="sd-section">
    <h4 class="sd-section-title"><i class="fas fa-sitemap mr-2"></i>Red de negocio</h4>
    <div class="rpe-network">
      <component
        :is="item.href ? 'a' : 'div'"
        v-for="item in network"
        :key="item.label"
        :href="item.href"
        class="rpe-network-item"
      >
        <i :class="['fas', item.icon]"></i>
        <span class="rpe-network-item__value">{{ item.value }}</span>
        <span class="rpe-network-item__label">{{ item.label }}</span>
      </component>
    </div>
  </div>

  <!-- Comisión / configuración permitida -->
  <div v-if="hasConfigSection" class="sd-section">
    <h4 class="sd-section-title"><i class="fas fa-sliders-h mr-2"></i>Comisión y configuración</h4>
    <div class="sd-grid">
      <div v-if="commission !== undefined && commission !== null" class="sd-field">
        <span class="sd-field-label"><i class="fas fa-percent"></i>Comisión</span>
        <span class="sd-field-value">{{ commission }}%</span>
      </div>
    </div>
    <div v-if="currencies?.length" class="rpe-chips">
      <span class="rpe-chips__label">Monedas habilitadas:</span>
      <span v-for="c in currencies" :key="c" class="rpe-chip rpe-chip--currency">{{ c }}</span>
    </div>
    <div v-if="games?.length" class="rpe-chips">
      <span class="rpe-chips__label">Juegos habilitados:</span>
      <span v-for="g in games" :key="g" class="rpe-chip rpe-chip--game">{{ g }}</span>
    </div>
  </div>

  <!-- Actividad reciente -->
  <div class="sd-section">
    <h4 class="sd-section-title"><i class="fas fa-clock-rotate-left mr-2"></i>Actividad reciente</h4>
    <div class="sd-grid rpe-account-meta">
      <div class="sd-field">
        <span class="sd-field-label"><i class="fas fa-calendar-plus"></i>Miembro desde</span>
        <span class="sd-field-value">{{ formatDateTime(accountMeta.member_since) }}</span>
      </div>
      <div class="sd-field">
        <span class="sd-field-label"><i class="fas fa-right-to-bracket"></i>Última conexión</span>
        <span class="sd-field-value">
          {{ accountMeta.last_login_at ? timeAgo(accountMeta.last_login_at) : 'Nunca' }}
        </span>
      </div>
    </div>

    <div v-if="recentMovements.length" class="rpe-timeline">
      <div v-for="movement in recentMovements" :key="movement.id" class="rpe-timeline-item">
        <span :class="movementStatusConfig[movement.status]?.cls">
          {{ movementStatusConfig[movement.status]?.label ?? movement.status }}
        </span>
        <span class="rpe-timeline-item__type">{{ movement.type_label }}</span>
        <span class="rpe-timeline-item__amount">{{ formatAmount(movement.amount) }} {{ movement.currency }}</span>
        <span class="rpe-timeline-item__date">{{ timeAgo(movement.created_at) }}</span>
      </div>
    </div>
    <p v-else class="text-muted small mb-0 mt-2">Sin movimientos de billetera todavía.</p>
  </div>
</template>

<style scoped>
.rpe-wallets { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: stretch; }
.rpe-wallet-card {
  background: var(--ds-surface-2);
  border-radius: 10px;
  padding: 0.7rem 1rem;
  min-width: 130px;
}
.rpe-wallet-card__currency { font-size: 0.72rem; font-weight: 700; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.rpe-wallet-card__balance { font-size: 1.15rem; font-weight: 700; color: var(--ds-text); margin-top: 0.15rem; }
.rpe-wallet-card__bonus { font-size: 0.7rem; color: #fbbf24; margin-top: 0.2rem; }
.rpe-wallet-link {
  display: inline-flex; align-items: center; align-self: center;
  font-size: 0.8rem; font-weight: 600; color: var(--ds-primary, #667eea);
  text-decoration: none;
}
.rpe-wallet-link:hover { text-decoration: underline; }

.rpe-network { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.rpe-network-item {
  display: flex; align-items: center; gap: 0.5rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  padding: 0.6rem 1rem;
  text-decoration: none;
  color: inherit;
  transition: background 0.15s;
}
a.rpe-network-item:hover { background: var(--ds-border); }
.rpe-network-item i { color: var(--ds-text-muted); font-size: 0.85rem; }
.rpe-network-item__value { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); }
.rpe-network-item__label { font-size: 0.78rem; color: var(--ds-text-muted); }

.rpe-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; margin-top: 0.6rem; }
.rpe-chips__label { font-size: 0.75rem; color: var(--ds-text-muted); margin-right: 0.25rem; }
.rpe-chip {
  font-size: 0.7rem; font-weight: 600; padding: 3px 10px; border-radius: 20px;
}
.rpe-chip--currency { background: rgba(37,99,235,.15); color: #60a5fa; }
.rpe-chip--game { background: rgba(124,58,237,.15); color: #a78bfa; }

.rpe-account-meta { margin-bottom: 0.75rem; }

.rpe-timeline { display: flex; flex-direction: column; gap: 0.5rem; }
.rpe-timeline-item {
  display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.8rem;
}
.rpe-timeline-item__type { font-weight: 600; color: var(--ds-text); }
.rpe-timeline-item__amount { color: var(--ds-text-2); }
.rpe-timeline-item__date { margin-left: auto; color: var(--ds-text-muted); font-size: 0.72rem; }

.rpe-status {
  display: inline-flex; align-items: center;
  font-size: 0.68rem; font-weight: 600;
  padding: 2px 9px; border-radius: 20px;
  white-space: nowrap;
}
.rpe-status--pending   { background: rgba(217,119,6,.18); color: #fbbf24; }
.rpe-status--completed { background: rgba(22,163,74,.16); color: #4ade80; }
.rpe-status--cancelled { background: var(--ds-surface-2); color: var(--ds-text-2); }
.rpe-status--rejected  { background: rgba(225,29,72,.16); color: #fb7185; }
</style>
