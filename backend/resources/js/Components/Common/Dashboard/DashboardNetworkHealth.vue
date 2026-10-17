<script setup lang="ts">
import { computed } from 'vue'
import type { NetworkHealth, NetworkIdleSeller } from '@/js/types/common/dashboard/dashboard'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

const props = defineProps<{
  health?: NetworkHealth
  /** Página de gestión de vendedores (cambia entre agencia y comercio) */
  sellersHref: string
}>()

const h = computed(() => props.health)
const todayPct = computed(() => (h.value?.active ? Math.round((h.value.sold_today / h.value.active) * 100) : 0))

function idleLabel(row: NetworkIdleSeller): string {
  if (row.days === null) return 'Nunca ha vendido'
  if (row.days === 0) return 'Vendió hoy'
  return `Sin vender hace ${row.days} día(s)`
}

// Mensaje listo para reactivar al vendedor por WhatsApp.
function whatsappHref(row: NetworkIdleSeller): string | null {
  const digits = row.phone?.replace(/\D/g, '') ?? ''
  if (!digits) return null
  const text = `Hola ${row.name}, ¿cómo vas? Hay salas abiertas para vender hoy. ¿Te ayudo con algo?`
  return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`
}
</script>

<template>
  <div v-if="h && h.total > 0" class="nh">
    <div class="nh__kpis">
      <div class="nh__kpi">
        <span class="nh__kpi-value">{{ h.sold_today }}<small>/{{ h.active }}</small></span>
        <span class="nh__kpi-label">Vendieron hoy</span>
      </div>
      <div class="nh__kpi" :class="{ 'nh__kpi--warn': h.idle_count > 0 }">
        <span class="nh__kpi-value">{{ h.idle_count }}</span>
        <span class="nh__kpi-label">Sin vender en 7 días</span>
      </div>
      <div class="nh__kpi" :class="{ 'nh__kpi--muted': h.inactive > 0 }">
        <span class="nh__kpi-value">{{ h.inactive }}</span>
        <span class="nh__kpi-label">No activos</span>
      </div>
    </div>

    <div class="nh__bar" :title="`${todayPct}% de los vendedores activos vendió hoy`">
      <span class="nh__bar-fill" :style="{ width: `${todayPct}%` }"></span>
    </div>

    <template v-if="h.idle.length">
      <p class="nh__subtitle">Para reactivar</p>
      <ul class="nh__idle">
        <li v-for="row in h.idle" :key="`${row.name}-${row.phone}`" class="nh__idle-row">
          <span class="nh__idle-who">
            {{ row.name }}
            <small>{{ idleLabel(row) }}</small>
          </span>
          <a
            v-if="whatsappHref(row)"
            :href="whatsappHref(row)!"
            target="_blank"
            rel="noopener"
            class="nh__wa"
            title="Escribir por WhatsApp"
          ><i class="fab fa-whatsapp"></i></a>
        </li>
      </ul>
      <a v-if="h.idle_count > h.idle.length" :href="sellersHref" class="nh__more">
        Ver los {{ h.idle_count }} vendedores sin ventas
      </a>
    </template>
    <p v-else class="nh__ok"><i class="fas fa-circle-check"></i> Todos tus vendedores activos vendieron esta semana.</p>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-user-plus"
    title="Aún no tienes vendedores"
    description="Cuando agregues vendedores, aquí verás quién vende y quién no."
    compact
  />
</template>

<style scoped>
.nh { display: flex; flex-direction: column; gap: 0.75rem; }

.nh__kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; }
.nh__kpi {
  display: flex; flex-direction: column; gap: 2px;
  padding: 0.6rem 0.75rem; border-radius: 10px;
  background: var(--ds-surface-2);
}
.nh__kpi-value { font-size: 1.15rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.nh__kpi-value small { font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted); }
.nh__kpi-label { font-size: 0.68rem; color: var(--ds-text-muted); }
.nh__kpi--warn .nh__kpi-value { color: #d97706; }
.nh__kpi--muted .nh__kpi-value { color: var(--ds-text-2); }

.nh__bar { height: 6px; border-radius: 6px; background: var(--ds-surface-2); overflow: hidden; }
.nh__bar-fill { display: block; height: 100%; background: #10b981; border-radius: 6px; transition: width 0.3s; }

.nh__subtitle { margin: 0; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ds-text-muted); }
.nh__idle { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem; }
.nh__idle-row {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.45rem 0.75rem; border-radius: 8px; background: var(--ds-surface-2);
}
.nh__idle-who { flex: 1; min-width: 0; display: flex; flex-direction: column; font-size: 0.8rem; color: var(--ds-text); }
.nh__idle-who small { font-size: 0.68rem; color: var(--ds-text-muted); }
.nh__wa { color: #16a34a; font-size: 1rem; }
.nh__wa:hover { color: #15803d; }
.nh__more { font-size: 0.75rem; font-weight: 600; color: #db2777; }
.nh__ok { margin: 0; font-size: 0.8rem; color: #059669; }
</style>
