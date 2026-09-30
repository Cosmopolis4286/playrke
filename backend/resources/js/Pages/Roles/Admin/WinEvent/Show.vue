<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import type {
  WinEventDetail,
  WinEventDetailCell,
  WinEventDetailItem,
} from '@/js/types/platform/win-event-detail'

const props = defineProps<{
  /** null: el evento no existe (p. ej. anunciado por una transacción revertida). */
  detail: WinEventDetail | null
  missingId?: string
}>()

// Solo se evalúan dentro del v-else (detail presente) — computed es perezoso.
const event = computed(() => props.detail!.event)
const winner = computed(() => props.detail!.winner)

/** Misma convención visual que PlatformWinTicker — slug sin entrada cae al genérico. */
const GAME_ICONS: Record<string, string> = {
  bingo: 'fas fa-dice',
  trivia: 'fas fa-question-circle',
}

const PRIZE_KIND_ICONS: Record<string, string> = {
  money: 'fas fa-trophy',
  item: 'fas fa-gift',
  service: 'fas fa-concierge-bell',
}

const gameIcon = computed(() => GAME_ICONS[event.value.game_slug] ?? 'fas fa-gamepad')

const prizeMain = computed(() => {
  const e = event.value
  if (e.prize_kind !== 'money' || e.amount_usd === null) return e.prize_label
  return `$${e.amount_usd.toFixed(2)} USD`
})

/** Monto en moneda local, solo si difiere de USD. */
const prizeLocal = computed(() => {
  const e = event.value
  if (e.prize_kind !== 'money' || e.amount_local === null || !e.currency || e.currency.iso_code === 'USD') return null
  return `${e.currency.symbol}${e.amount_local.toLocaleString('es', {
    minimumFractionDigits: e.currency.decimal_places,
    maximumFractionDigits: e.currency.decimal_places,
  })} ${e.currency.iso_code}`
})

function formatDate(iso: unknown): string {
  if (typeof iso !== 'string' || !iso) return '—'
  return new Date(iso).toLocaleString('es', { dateStyle: 'medium', timeStyle: 'short' })
}

function isBadgeCell(cell: WinEventDetailCell): cell is { value: string; variant: any } {
  return typeof cell === 'object' && cell !== null && 'value' in cell
}

function formatCell(cell: WinEventDetailCell, key: string): string {
  if (cell === null || cell === undefined || cell === '') return '—'
  if (typeof cell === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(cell)) return formatDate(cell)
  return String(key === 'rank' ? `#${cell}` : cell)
}

/**
 * Normaliza los números de un cartón (u otra grilla de un juego futuro) a
 * columnas con encabezado opcional. Soporta { B: [..], I: [..] }, [[..], [..]]
 * y listas planas.
 */
function numberGrid(value: unknown): { headers: string[] | null; rows: unknown[][] } | null {
  if (Array.isArray(value)) {
    if (value.every(Array.isArray)) return { headers: null, rows: value as unknown[][] }
    return { headers: null, rows: [value] }
  }
  if (value && typeof value === 'object') {
    const cols = Object.entries(value as Record<string, unknown>)
    if (!cols.every(([, v]) => Array.isArray(v))) return null
    const height = Math.max(...cols.map(([, v]) => (v as unknown[]).length))
    const rows = Array.from({ length: height }, (_, r) => cols.map(([, v]) => (v as unknown[])[r] ?? ''))
    return { headers: cols.map(([k]) => k), rows }
  }
  return null
}

function isFreeCell(cell: unknown): boolean {
  return cell === 0 || cell === '0' || cell === null || cell === 'FREE' || cell === '*'
}

function itemValue(item: WinEventDetailItem): string {
  return item.type === 'datetime' ? formatDate(item.value) : String(item.value)
}

function goBack(): void {
  if (window.history.length > 1) window.history.back()
  else router.visit('/admin/win-event')
}
</script>

<template>
  <template v-if="!detail">
    <PageHeader
      icon="fa-trophy"
      title="Detalle de Ganancia"
      :breadcrumb-items="[
        { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
        { label: 'Eventos de Ganancia', route: '/admin/win-event' },
        { label: 'Detalle' },
      ]"
    />
    <div class="container-fluid py-4">
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-ghost fa-2x text-muted mb-3"></i>
          <h5 class="fw-semibold">Este evento de ganancia no existe</h5>
          <p class="text-muted small mb-1">
            No se registró o fue anunciado por una operación que se revirtió.
          </p>
          <p v-if="missingId" class="text-muted small"><code>{{ missingId }}</code></p>
          <button type="button" class="btn btn-sm btn-primary mt-2" @click="goBack">
            <i class="fas fa-arrow-left me-1"></i>Volver
          </button>
        </div>
      </div>
    </div>
  </template>

  <template v-else>
  <PageHeader
    icon="fa-trophy"
    title="Detalle de Ganancia"
    :subtitle="`${event.game_name} · ${formatDate(event.created_at)}`"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Eventos de Ganancia', route: '/admin/win-event' },
      { label: 'Detalle' },
    ]"
  />

  <div class="container-fluid py-4 wed">
    <!-- ── Resumen ── -->
    <div class="wed-hero mb-4">
      <div class="wed-hero__game">
        <i :class="gameIcon"></i>
      </div>
      <div class="wed-hero__body">
        <div class="wed-hero__eyebrow">
          <i :class="PRIZE_KIND_ICONS[event.prize_kind] ?? 'fas fa-trophy'" class="me-1"></i>
          {{ event.prize_kind_label }} · {{ event.game_name }}
        </div>
        <div class="wed-hero__amount">{{ prizeMain }}</div>
        <div v-if="prizeLocal" class="wed-hero__local">{{ prizeLocal }}</div>
      </div>
      <div class="wed-hero__actions">
        <button type="button" class="btn btn-sm btn-light" @click="goBack">
          <i class="fas fa-arrow-left me-1"></i>Volver
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- ── Ganador ── -->
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header bg-transparent border-bottom">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-user text-muted me-2"></i>Ganador</h6>
          </div>
          <div class="card-body">
            <dl class="wed-dl">
              <dt>Nombre</dt>
              <dd class="fw-semibold">{{ winner.name }}</dd>
              <template v-if="winner.username">
                <dt>Usuario</dt>
                <dd>@{{ winner.username }}</dd>
              </template>
              <template v-if="winner.email">
                <dt>Email</dt>
                <dd>{{ winner.email }}</dd>
              </template>
              <dt>Cuenta</dt>
              <dd>
                <span class="badge" :class="winner.has_account ? 'bg-success' : 'bg-secondary'">
                  {{ winner.has_account ? 'Registrado en la plataforma' : 'Sin cuenta (externo)' }}
                </span>
              </dd>
              <template v-if="winner.user_id">
                <dt>ID de usuario</dt>
                <dd><code>{{ winner.user_id }}</code></dd>
              </template>
            </dl>
          </div>
        </div>
      </div>

      <!-- ── Evento ── -->
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header bg-transparent border-bottom">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-bolt text-muted me-2"></i>Evento</h6>
          </div>
          <div class="card-body">
            <dl class="wed-dl">
              <dt>Fecha</dt>
              <dd>{{ formatDate(event.created_at) }}</dd>
              <dt>Fuente</dt>
              <dd>{{ event.source_type_label }}</dd>
              <template v-if="event.reported_by">
                <dt>Registrado por</dt>
                <dd>{{ event.reported_by }}</dd>
              </template>
              <template v-if="event.prize_kind !== 'money'">
                <dt>Premio</dt>
                <dd>{{ event.prize_label }}</dd>
              </template>
              <template v-if="event.source_reference">
                <dt>Referencia de origen</dt>
                <dd><code>{{ event.source_reference }}</code></dd>
              </template>
              <dt>ID del evento</dt>
              <dd><code>{{ event.id }}</code></dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- ── Secciones del juego (dinámicas) ── -->
      <div
        v-for="section in detail.sections"
        :key="section.key"
        class="col-12"
        :class="{ 'col-lg-6': section.tables.length === 0 }"
      >
        <div class="card h-100">
          <div class="card-header bg-transparent border-bottom">
            <h6 class="mb-0 fw-semibold"><i :class="section.icon" class="text-muted me-2"></i>{{ section.title }}</h6>
          </div>
          <div class="card-body">
            <dl v-if="section.items.length" class="wed-dl">
              <template v-for="(item, i) in section.items" :key="i">
                <dt>{{ item.label }}</dt>
                <dd>
                  <span v-if="item.type === 'badge'" class="badge" :class="`bg-${item.variant ?? 'secondary'}`">{{ item.value }}</span>
                  <code v-else-if="item.type === 'code'" class="wed-code">{{ item.value }}</code>
                  <strong v-else-if="item.type === 'money'" class="text-success">{{ item.value }}</strong>
                  <template v-else-if="item.type === 'numbers' && numberGrid(item.value)">
                    <table class="wed-grid">
                      <thead v-if="numberGrid(item.value)!.headers">
                        <tr><th v-for="h in numberGrid(item.value)!.headers!" :key="h">{{ h }}</th></tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, r) in numberGrid(item.value)!.rows" :key="r">
                          <td v-for="(cell, c) in row" :key="c" :class="{ 'wed-grid__free': isFreeCell(cell) }">
                            {{ isFreeCell(cell) ? '★' : cell }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </template>
                  <span v-else>{{ itemValue(item) }}</span>
                </dd>
              </template>
            </dl>

            <div v-for="table in section.tables" :key="table.title" :class="{ 'mt-3': section.items.length }">
              <div class="small text-muted fw-semibold mb-2">{{ table.title }}</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr><th v-for="col in table.columns" :key="col.key" class="small text-muted">{{ col.label }}</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, r) in table.rows" :key="r" :class="{ 'wed-row--hl': row._highlight }">
                      <td v-for="col in table.columns" :key="col.key" class="small">
                        <span v-if="isBadgeCell(row[col.key])" class="badge" :class="`bg-${(row[col.key] as any).variant}`">
                          {{ (row[col.key] as any).value }}
                        </span>
                        <span v-else>{{ formatCell(row[col.key], col.key) }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!detail.has_game_detail" class="col-12">
        <div class="alert alert-light border small mb-0">
          <i class="fas fa-info-circle me-1"></i>
          No hay detalle específico del juego <strong>{{ event.game_name }}</strong> para este evento
          (registro manual, integración externa o juego sin resolver de detalle).
        </div>
      </div>
    </div>
  </div>
  </template>
</template>

<style scoped>
.wed-hero {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 22px 24px;
  border-radius: 16px;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9, #0369a1);
}
.wed-hero__game {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  background: rgba(255, 255, 255, .18);
}
.wed-hero__body { flex: 1; min-width: 0; }
.wed-hero__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  opacity: .85;
}
.wed-hero__amount {
  font-size: 28px;
  font-weight: 800;
  line-height: 1.2;
  word-break: break-word;
}
.wed-hero__local { font-size: 14px; opacity: .9; }

.wed-dl {
  display: grid;
  grid-template-columns: minmax(120px, 40%) 1fr;
  gap: 8px 16px;
  margin: 0;
}
.wed-dl dt {
  font-size: 13px;
  font-weight: 500;
  color: var(--ds-text-2);
}
.wed-dl dd {
  font-size: 13px;
  margin: 0;
  min-width: 0;
  word-break: break-word;
}
.wed-code { font-size: 12px; }

.wed-grid {
  border-collapse: collapse;
  font-size: 12px;
  font-weight: 600;
}
.wed-grid th,
.wed-grid td {
  width: 30px;
  height: 28px;
  text-align: center;
  border: 1px solid var(--ds-border);
}
.wed-grid th {
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
}
.wed-grid__free { color: #fbbf24; }

.wed-row--hl td { background: rgba(34, 197, 94, .12) !important; font-weight: 600; }

@media (max-width: 576px) {
  .wed-hero { flex-wrap: wrap; padding: 18px; }
  .wed-hero__amount { font-size: 22px; }
  .wed-dl { grid-template-columns: 1fr; gap: 2px 0; }
  .wed-dl dd { margin-bottom: 8px; }
}
</style>
