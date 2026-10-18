<script setup lang="ts">
  /**
   * "Mis Comisiones" — histórico de comisiones propias (vendedor, agencia,
   * comercializadora, creador de salas) con cómo se cobró cada una:
   * efectivo (ya la tiene en mano), billetera (se acredita al cerrar la sala)
   * o custodiada (venta cruzada en su sala, se le paga al cobrar la deuda).
   * Datos: CommissionLedgerService (GET /commissions/data).
   */
  import { computed, ref } from 'vue'
  import backendClient from '@js/plugins/axios'

  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

  import type {
    CommissionsPageProps, CommissionLedger, CommissionRow, CommissionSource, CommissionStatus, CommissionHow,
  } from '@/js/types/commissions/commissions'

  const props = defineProps<CommissionsPageProps>()

  const ledger  = ref<CommissionLedger>(props.ledger)
  const from    = ref(props.filters.from ?? props.ledger.range.from)
  const to      = ref(props.filters.to ?? props.ledger.range.to)
  const source  = ref<CommissionSource | ''>(props.filters.source ?? '')
  const status  = ref<CommissionStatus | ''>(props.filters.status ?? '')
  const loading = ref(false)
  const error   = ref<string | null>(null)

  const SOURCE_LABELS: Record<CommissionSource, string> = {
    seller: 'Ventas propias', subsidiary: 'Agencia', network: 'Red', room: 'Comisión de sala',
  }
  const HOW_META: Record<CommissionHow, { label: string; icon: string; hint: string }> = {
    cash:    { label: 'Efectivo',  icon: 'fas fa-money-bill-wave', hint: 'La cobraste en mano al vender: no se acredita en tu billetera.' },
    wallet:  { label: 'Billetera', icon: 'fas fa-wallet',          hint: 'Compra con billetera del jugador: se acredita a tu billetera al cerrar la sala.' },
    custody: { label: 'Custodiada', icon: 'fas fa-shield-halved',  hint: 'Venta de otra red en tu sala: se acredita a tu billetera al cobrarle la deuda, con la sala cerrada.' },
  }

  const params = () => ({
    from: from.value || undefined,
    to: to.value || undefined,
    source: source.value || undefined,
    status: status.value || undefined,
  })

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await backendClient.get('/commissions/data', { params: params() })
      if (data.success) {
        ledger.value = data.data
        page.value = 1
      }
    } catch (e: any) {
      error.value = e?.response?.data?.message ?? 'No se pudo cargar el histórico.'
    } finally {
      loading.value = false
    }
  }

  function setRange(kind: 'month' | 'last30' | 'prev'): void {
    const now = new Date()
    const iso = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    if (kind === 'month') {
      from.value = iso(new Date(now.getFullYear(), now.getMonth(), 1)); to.value = iso(now)
    } else if (kind === 'last30') {
      from.value = iso(new Date(now.getTime() - 29 * 86400000)); to.value = iso(now)
    } else {
      from.value = iso(new Date(now.getFullYear(), now.getMonth() - 1, 1)); to.value = iso(new Date(now.getFullYear(), now.getMonth(), 0))
    }
    load()
  }

  function exportCsv(): void {
    const query = new URLSearchParams(Object.entries(params()).filter(([, v]) => v) as [string, string][])
    window.location.href = `/commissions/export?${query.toString()}`
  }

  // ── Paginación en cliente (el rango de fechas acota las filas) ─────────
  const PER_PAGE = 25
  const page = ref(1)
  const totalPages = computed(() => Math.max(1, Math.ceil(ledger.value.rows.length / PER_PAGE)))
  const pageRows = computed(() => ledger.value.rows.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE))

  const money = (symbol: string, v: number) =>
    `${symbol} ${v.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`.trim()
  const date = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

  const statusTone = (row: CommissionRow) =>
    ({ paid: 'cm-badge--ok', pending: 'cm-badge--warn', refunded: 'cm-badge--muted' })[row.status]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Filtros -->
      <div class="cm-filters">
        <div class="cm-field">
          <label class="cm-label">Desde</label>
          <input v-model="from" type="date" class="cm-input" />
        </div>
        <div class="cm-field">
          <label class="cm-label">Hasta</label>
          <input v-model="to" type="date" class="cm-input" />
        </div>
        <div v-if="ledger.sources.length > 1" class="cm-field">
          <label class="cm-label">Tipo</label>
          <select v-model="source" class="cm-input">
            <option value="">Todas</option>
            <option v-for="s in ledger.sources" :key="s" :value="s">{{ SOURCE_LABELS[s] }}</option>
          </select>
        </div>
        <div class="cm-field">
          <label class="cm-label">Estado</label>
          <select v-model="status" class="cm-input">
            <option value="">Todos</option>
            <option value="paid">Cobradas / acreditadas</option>
            <option value="pending">Pendientes</option>
            <option value="refunded">Reembolsadas</option>
          </select>
        </div>
        <button class="cm-btn cm-btn--primary" :disabled="loading" @click="load">
          <i class="fas fa-filter"></i> {{ loading ? 'Cargando…' : 'Filtrar' }}
        </button>
        <div class="cm-quick">
          <button class="cm-chip" @click="setRange('month')">Este mes</button>
          <button class="cm-chip" @click="setRange('last30')">Últimos 30 días</button>
          <button class="cm-chip" @click="setRange('prev')">Mes anterior</button>
        </div>
        <button class="cm-btn cm-btn--ghost cm-export" @click="exportCsv">
          <i class="fas fa-file-csv"></i> Exportar CSV
        </button>
      </div>

      <p v-if="error" class="cm-error"><i class="fas fa-circle-exclamation"></i> {{ error }}</p>

      <!-- Totales por moneda -->
      <div v-if="ledger.summary.length" class="cm-summary">
        <div v-for="s in ledger.summary" :key="s.currency_id" class="cm-card">
          <div class="cm-card__head">
            <span class="cm-card__currency">{{ s.currency }}</span>
            <span class="cm-card__count">{{ s.count }} comisión(es)</span>
          </div>
          <div class="cm-card__total">{{ s.total }}</div>
          <div class="cm-card__rows">
            <span><i class="fas fa-circle-check"></i> Cobrado {{ s.paid }}</span>
            <span><i class="fas fa-hourglass-half"></i> Pendiente {{ s.pending }}</span>
            <span><i class="fas fa-wallet"></i> En billetera {{ s.in_wallet }}</span>
          </div>
        </div>
      </div>

      <!-- Cómo se cobra cada comisión -->
      <div class="cm-legend">
        <span v-for="(meta, key) in HOW_META" :key="key" class="cm-legend__item">
          <i :class="meta.icon"></i> <b>{{ meta.label }}:</b> {{ meta.hint }}
        </span>
      </div>

      <!-- Histórico -->
      <div v-if="ledger.rows.length" class="cm-table-wrap">
        <table class="cm-table">
          <thead>
            <tr>
              <th>Fecha</th><th>Tipo</th><th>Sala</th><th>Comprador</th>
              <th class="cm-num">Venta</th><th class="cm-num">Comisión</th><th>Cómo</th><th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in pageRows" :key="row.id" :class="{ 'cm-row--refunded': row.status === 'refunded' }">
              <td class="cm-nowrap">{{ date(row.date) }}</td>
              <td>{{ row.source_label }}</td>
              <td class="cm-mono">{{ row.room ?? '—' }}</td>
              <td>{{ row.buyer ?? '—' }} <small v-if="row.cards" class="cm-muted">· {{ row.cards }} cartón(es)</small></td>
              <td class="cm-num cm-nowrap">{{ money(row.symbol, row.sale_amount) }}</td>
              <td class="cm-num cm-nowrap cm-commission">{{ money(row.symbol, row.commission) }}</td>
              <td class="cm-nowrap" :title="HOW_META[row.how].hint">
                <i :class="HOW_META[row.how].icon"></i> {{ HOW_META[row.how].label }}
              </td>
              <td>
                <span class="cm-badge" :class="statusTone(row)">{{ row.status_label }}</span>
                <a v-if="row.status === 'paid' && row.how !== 'cash'" href="/wallet" class="cm-link" title="Ver en tu billetera">
                  <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardEmptyState
        v-else
        icon="fas fa-percentage"
        title="Sin comisiones en este período"
        description="Cambia el rango de fechas o los filtros para ver otras comisiones."
      />

      <div v-if="totalPages > 1" class="cm-pager">
        <button class="cm-btn cm-btn--ghost" :disabled="page === 1" @click="page--"><i class="fas fa-chevron-left"></i></button>
        <span>Página {{ page }} de {{ totalPages }} · {{ ledger.rows.length }} comisiones</span>
        <button class="cm-btn cm-btn--ghost" :disabled="page === totalPages" @click="page++"><i class="fas fa-chevron-right"></i></button>
      </div>
      <p v-if="ledger.truncated" class="cm-muted cm-note">Se muestran las primeras 5.000 comisiones: acota el rango de fechas para ver el resto.</p>
    </div>
  </div>
</template>

<style scoped>
.cm-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; margin-bottom: 1rem; }
.cm-field { display: flex; flex-direction: column; gap: 4px; }
.cm-label { font-size: 0.7rem; font-weight: 600; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: .03em; }
.cm-input {
  border: 1.5px solid var(--ds-border-2); border-radius: 8px; padding: 6px 10px;
  font-size: 0.85rem; color: var(--ds-text); background: var(--ds-surface-2);
}
.cm-quick { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.cm-chip {
  border: 1px solid var(--ds-border-2); background: transparent; color: var(--ds-text-2);
  font-size: 0.75rem; font-weight: 600; padding: 5px 10px; border-radius: 20px;
}
.cm-chip:hover { background: var(--ds-surface-2); }
.cm-export { margin-left: auto; }

.cm-btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 9px;
  font-size: 0.82rem; font-weight: 600; border: none; white-space: nowrap;
}
.cm-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.cm-btn--primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.cm-btn--ghost { background: transparent; color: var(--ds-text-2); border: 1.5px solid var(--ds-border-2); }

.cm-error { color: #dc2626; font-size: 0.82rem; }

.cm-summary { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.cm-card {
  padding: 0.9rem 1rem; border-radius: 12px; background: var(--ds-surface, #fff);
  border: 1px solid var(--ds-border);
}
.cm-card__head { display: flex; justify-content: space-between; align-items: baseline; }
.cm-card__currency { font-size: 0.75rem; font-weight: 800; color: var(--ds-text-2); letter-spacing: .05em; }
.cm-card__count { font-size: 0.7rem; color: var(--ds-text-muted); }
.cm-card__total { font-size: 1.35rem; font-weight: 800; color: #10b981; margin: 0.25rem 0 0.4rem; }
.cm-card__rows { display: flex; flex-direction: column; gap: 2px; font-size: 0.75rem; color: var(--ds-text-2); }
.cm-card__rows i { width: 14px; color: var(--ds-text-muted); }

.cm-legend {
  display: flex; flex-direction: column; gap: 3px; margin-bottom: 1rem;
  padding: 0.6rem 0.85rem; border-radius: 10px; background: var(--ds-surface-2); font-size: 0.75rem; color: var(--ds-text-2);
}
.cm-legend__item i { width: 16px; color: #6366f1; }

.cm-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--ds-border); }
.cm-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.cm-table thead tr { background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-border); }
.cm-table th {
  padding: 0.6rem 0.8rem; font-weight: 600; color: var(--ds-text-2); text-align: left;
  font-size: 0.72rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap;
}
.cm-table td { padding: 0.6rem 0.8rem; border-bottom: 1px solid var(--ds-border); color: var(--ds-text); vertical-align: middle; }
.cm-table tbody tr:last-child td { border-bottom: none; }
.cm-row--refunded td { opacity: 0.55; }
.cm-num { text-align: right; }
.cm-nowrap { white-space: nowrap; }
.cm-mono { font-family: monospace; font-size: 0.78rem; }
.cm-muted { color: var(--ds-text-muted); }
.cm-commission { font-weight: 700; color: #10b981; }

.cm-badge { display: inline-block; font-size: 0.7rem; font-weight: 600; padding: 2px 9px; border-radius: 20px; }
.cm-badge--ok { background: rgba(16,185,129,.14); color: #059669; }
.cm-badge--warn { background: rgba(245,158,11,.16); color: #b45309; }
.cm-badge--muted { background: var(--ds-surface-2); color: var(--ds-text-muted); }
.cm-link { margin-left: 6px; font-size: 0.75rem; color: #6366f1; }

.cm-pager { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-top: 0.75rem; font-size: 0.8rem; color: var(--ds-text-2); }
.cm-note { font-size: 0.75rem; text-align: center; margin-top: 0.5rem; }

@media (max-width: 640px) {
  .cm-export { margin-left: 0; }
}
</style>
