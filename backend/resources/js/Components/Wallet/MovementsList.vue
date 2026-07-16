<script setup lang="ts">
import type { WalletMovement } from '@js/types/wallet'
import type { MovementMetaEntry } from '@js/composables/wallet/useMovementMeta'

defineProps<{
  movements: WalletMovement[]
  movementsLoading: boolean
  hasMore: boolean
  currencySymbol: string | undefined
  decimalPlaces: number | undefined
  movMeta: (type: string) => MovementMetaEntry
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'load-more'): void
  (e: 'open-detail', movement: WalletMovement): void
}>()

function formatDate(iso: string): string {
  const date = new Date(iso)
  const now  = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  const diffH   = diffMs / 3600000
  const timeStr = date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })
  const dateStr = new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
  if (diffMin < 1)  return 'Ahora mismo'
  if (diffMin < 60) return `Hace ${diffMin} min · ${timeStr}`
  if (diffH < 24)   return `Hoy, ${timeStr}`
  if (diffH < 48)   return `Ayer, ${timeStr}`
  return `${dateStr} · ${timeStr}`
}

function formatAmount(value: number, decimalPlaces: number | undefined) {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: decimalPlaces ?? 2,
    maximumFractionDigits: decimalPlaces ?? 2,
  }).format(value)
}
</script>

<template>
  <div class="wallet-card movements-card">
    <div class="movements-header">
      <h3 class="wallet-card__title">
        <i class="fas fa-history"></i> Movimientos recientes
        <span class="live-dot" title="Actualización en tiempo real"></span>
      </h3>
      <div class="movements-header__right">
        <button
          type="button"
          class="refresh-btn"
          :class="{ 'refresh-btn--loading': movementsLoading }"
          :disabled="movementsLoading"
          @click="emit('refresh')"
          title="Actualizar"
        >
          <i class="fas fa-sync-alt"></i>
        </button>
        <span class="movements-count">{{ movements.length }} movimientos</span>
      </div>
    </div>

    <!-- Cargando -->
    <div v-if="movementsLoading && !movements.length" class="movements-loading">
      <div class="spinner"></div>
      <p>Cargando movimientos...</p>
    </div>

    <!-- Sin movimientos -->
    <div v-else-if="!movements.length" class="movements-empty">
      <div class="movements-empty__icon"><i class="fas fa-receipt"></i></div>
      <h3>Sin movimientos</h3>
      <p>Aún no hay movimientos registrados en esta billetera.</p>
      <p class="movements-empty__hint">Los depósitos, retiros, premios y compras aparecerán aquí.</p>
    </div>

    <!-- Lista -->
    <template v-else>
      <ul class="movements-list">
        <li
          v-for="(mov, index) in movements"
          :key="mov.id"
          class="movement-row movement-row--clickable"
          :class="{ 'movement-row--first': index === 0 }"
          @click="emit('open-detail', mov)"
        >
          <div
            class="movement-row__icon"
            :class="movMeta(mov.type).colorClass"
            :style="movMeta(mov.type).colorStyle"
          >
            <i :class="movMeta(mov.type).icon"></i>
          </div>

          <div class="movement-row__info">
            <div class="movement-row__title-row">
              <p class="movement-row__label">{{ movMeta(mov.type).label }}</p>
              <span v-if="index === 0" class="badge-recent">Más reciente</span>
              <span
                v-if="mov.status"
                class="badge-status"
                :class="{
                  'badge-status--pending':   mov.status === 'PENDING',
                  'badge-status--completed': mov.status === 'COMPLETED',
                  'badge-status--rejected':  mov.status === 'REJECTED',
                  'badge-status--cancelled': mov.status === 'CANCELLED',
                }"
              >
                <i :class="{
                  'fas fa-clock':      mov.status === 'PENDING',
                  'fas fa-check':      mov.status === 'COMPLETED',
                  'fas fa-times':      mov.status === 'REJECTED',
                  'fas fa-ban':        mov.status === 'CANCELLED',
                }"></i>
                {{ mov.status === 'PENDING' ? 'Pendiente' : mov.status === 'COMPLETED' ? 'Completado' : mov.status === 'REJECTED' ? 'Rechazado' : 'Cancelado' }}
              </span>
            </div>
            <p class="movement-row__desc">{{ mov.description ?? movMeta(mov.type).description }}</p>
            <p class="movement-row__date">
              <i class="fas fa-clock"></i>
              {{ formatDate(mov.created_at) }}
            </p>
          </div>

          <div class="movement-row__amounts">
            <p
              class="movement-row__amount"
              :class="movMeta(mov.type).sign === '+' ? 'amount--positive' : 'amount--negative'"
            >
              {{ movMeta(mov.type).sign }}{{ currencySymbol }}&nbsp;{{ formatAmount(mov.amount, decimalPlaces) }}
            </p>
            <p class="movement-row__balance">
              Saldo: {{ currencySymbol }}&nbsp;{{ formatAmount(mov.balance_after, decimalPlaces) }}
            </p>
          </div>
        </li>
      </ul>

      <div v-if="hasMore || movementsLoading" class="movements-load-more">
        <button
          type="button"
          class="load-more-btn"
          :disabled="movementsLoading"
          @click="emit('load-more')"
        >
          <span v-if="movementsLoading" class="flex items-center gap-2">
            <span class="spinner spinner--sm"></span>
            Cargando más movimientos...
          </span>
          <span v-else>
            <i class="fas fa-arrow-down"></i> Ver más movimientos
          </span>
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.wallet-card {
  background: var(--ds-surface);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.wallet-card__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.wallet-card__title i { color: #667eea; }

.live-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  margin-left: 6px;
  vertical-align: middle;
  animation: live-pulse 2s ease-in-out infinite;
}

@keyframes live-pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
  50%       { opacity: 0.7; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
}

.movements-card {
  border-radius: 0 0 16px 16px;
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.10);
  padding: 0;
  overflow: hidden;
}

/* ── Movimientos ── */
.movements-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
}

.movements-header .wallet-card__title { margin: 0; }

.movements-header__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.refresh-btn {
  width: 28px;
  height: 28px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  border-radius: 8px;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.refresh-btn:hover:not(:disabled) { background: rgba(102, 126, 234, .1); border-color: #667eea; }
.refresh-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.refresh-btn--loading i { animation: spin 0.7s linear infinite; }

.movements-count {
  font-size: 0.75rem;
  font-weight: 600;
  color: #667eea;
  background: rgba(102, 126, 234, .14);
  padding: 3px 10px;
  border-radius: 20px;
}

.movements-loading,
.movements-empty {
  padding: 4rem 2rem;
  text-align: center;
  color: var(--ds-text-muted);
}

.movements-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.movements-empty__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--ds-text-muted);
  margin: 0 auto 1rem;
}

.movements-empty h3 { font-weight: 700; color: var(--ds-text-2); margin: 0 0 0.25rem; }
.movements-empty p  { margin: 0; font-size: 0.875rem; }
.movements-empty__hint { font-size: 0.78rem !important; color: var(--ds-text-muted); margin-top: 0.25rem !important; }

.movements-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.movement-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--ds-border);
  transition: background 0.15s;
}
.movement-row:hover { background: var(--ds-surface-2); }
.movement-row:last-child { border-bottom: none; }

.movement-row--clickable {
  cursor: pointer;
}

.movement-row--first {
  border-left: 3px solid #667eea;
}

.movement-row__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.movement-row__icon.meta--deposit      { background: rgba(34, 197, 94, .14); color: #16a34a; }
.movement-row__icon.meta--prize        { background: rgba(245, 158, 11, .14); color: #d97706; }
.movement-row__icon.meta--purchase     { background: rgba(225, 29, 72, .14); color: #e11d48; }
.movement-row__icon.meta--withdrawal   { background: rgba(37, 99, 235, .14); color: #2563eb; }
.movement-row__icon.meta--trivia-entry { background: rgba(124, 58, 237, .14); color: #7c3aed; }
.movement-row__icon.meta--trivia-prize { background: rgba(124, 58, 237, .14); color: #7c3aed; }

.movement-row__info { flex: 1; min-width: 0; }

.movement-row__title-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 2px;
}

.movement-row__label {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.movement-row__desc {
  font-size: 0.78rem;
  color: var(--ds-text-muted);
  margin: 0 0 2px;
}

.movement-row__date {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  display: flex;
  align-items: center;
  gap: 4px;
  margin: 0;
}

.badge-recent {
  font-size: 0.68rem;
  font-weight: 600;
  color: #667eea;
  background: rgba(102, 126, 234, .14);
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}

.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}
.badge-status--pending   { color: #d97706; background: rgba(245, 158, 11, .16); }
.badge-status--completed { color: #16a34a; background: rgba(34, 197, 94, .16); }
.badge-status--rejected  { color: #dc2626; background: rgba(220, 38, 38, .16); }
.badge-status--cancelled { color: var(--ds-text-2); background: var(--ds-surface-2); }

.movement-row__amounts { text-align: right; flex-shrink: 0; }

.movement-row__amount {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 2px;
}

.amount--positive { color: #16a34a; }
.amount--negative { color: #e11d48; }

.movement-row__balance {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}

/* ── Cargar más ── */
.movements-load-more {
  padding: 1.25rem 1.5rem;
  text-align: center;
  border-top: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
}

.load-more-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 24px;
  border: 1px solid #c7d2fe;
  background: var(--ds-surface);
  color: #667eea;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.load-more-btn:hover:not(:disabled) { background: rgba(102, 126, 234, .12); border-color: #667eea; }
.load-more-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Spinner ── */
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #c7d2fe;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
