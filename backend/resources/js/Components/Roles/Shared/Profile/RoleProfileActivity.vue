<script setup lang="ts">
import RpCard from './RpCard.vue'
import type { RoleProfileMovement } from '@/js/types/roles/shared/role-profile-common'
import { formatAmount, timeAgo } from '@/js/composables/roles/shared/profile/useRoleProfile'

defineProps<{ movements: RoleProfileMovement[] }>()

const STATUS: Record<RoleProfileMovement['status'], { label: string; tone: string; icon: string }> = {
  PENDING:   { label: 'Pendiente',  tone: 'warning', icon: 'fa-hourglass-half' },
  COMPLETED: { label: 'Completado', tone: 'success', icon: 'fa-check' },
  CANCELLED: { label: 'Cancelado',  tone: 'muted',   icon: 'fa-xmark' },
  REJECTED:  { label: 'Rechazado',  tone: 'danger',  icon: 'fa-ban' },
}

function meta(status: string) {
  return STATUS[status as RoleProfileMovement['status']] ?? { label: status, tone: 'muted', icon: 'fa-circle' }
}
</script>

<template>
  <RpCard title="Movimientos recientes" icon="fa-clock-rotate-left" subtitle="Últimos movimientos de tu billetera">
    <template #actions>
      <a href="/wallet-movements" class="rp-link">Ver todos <i class="fas fa-arrow-right"></i></a>
    </template>

    <ol v-if="movements.length" class="rp-timeline">
      <li v-for="m in movements" :key="m.id" class="rp-timeline__item">
        <span :class="['rp-timeline__icon', `rp-tone--${meta(m.status).tone}`]"><i :class="['fas', meta(m.status).icon]"></i></span>
        <div class="rp-timeline__main">
          <span class="rp-timeline__type">{{ m.type_label }}</span>
          <span class="rp-timeline__meta">
            <span :class="['rp-timeline__status', `rp-tone--${meta(m.status).tone}`]">{{ meta(m.status).label }}</span>
            · <time :datetime="m.created_at" :title="new Date(m.created_at).toLocaleString('es-VE')">{{ timeAgo(m.created_at) }}</time>
          </span>
        </div>
        <span class="rp-timeline__amount">{{ formatAmount(m.amount) }} <small>{{ m.currency }}</small></span>
      </li>
    </ol>
    <div v-else class="rp-empty">
      <i class="fas fa-inbox"></i>
      <span>Sin movimientos todavía. Aquí aparecerán tus recargas, retiros y acreditaciones.</span>
    </div>
  </RpCard>
</template>

<style scoped>
.rp-link { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: #7c8cf0; text-decoration: none; }
.rp-link:hover { text-decoration: underline; color: #7c8cf0; }

.rp-timeline { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.rp-timeline__item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid var(--ds-border); }
.rp-timeline__item:last-child { border-bottom: none; }
.rp-timeline__icon {
  width: 32px; height: 32px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px; font-size: 0.75rem;
  background: color-mix(in srgb, currentColor 14%, transparent);
}
.rp-timeline__main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.rp-timeline__type { font-size: 0.86rem; font-weight: 700; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-timeline__meta { font-size: 0.72rem; color: var(--ds-text-muted); }
.rp-timeline__status { font-weight: 700; }
.rp-timeline__amount { font-size: 0.92rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
.rp-timeline__amount small { font-size: 0.7rem; font-weight: 600; color: var(--ds-text-muted); }

.rp-tone--success { color: #22c55e; }
.rp-tone--warning { color: #f59e0b; }
.rp-tone--danger  { color: #f43f5e; }
.rp-tone--muted   { color: var(--ds-text-muted); }

.rp-empty {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.85rem 1rem; border-radius: 10px;
  background: var(--ds-surface-2);
  font-size: 0.8rem; color: var(--ds-text-2);
}
.rp-empty i { font-size: 1.1rem; color: var(--ds-text-muted); }
</style>
