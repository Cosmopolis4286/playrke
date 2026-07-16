<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import type { Wallet, WalletMovement } from '@js/types/wallet'
import type { MovementMetaEntry } from '@js/composables/wallet/useMovementMeta'

const props = defineProps<{
  modelValue: boolean
  movement: WalletMovement | null
  meta: MovementMetaEntry | null
  activeWallet: Wallet | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
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

function formatAmount(value: number): string {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: props.activeWallet?.currency.decimal_places ?? 2,
    maximumFractionDigits: props.activeWallet?.currency.decimal_places ?? 2,
  }).format(value)
}

interface TimelineStep {
  label:    string
  sub:      string | null
  done:     boolean
  active:   boolean
  rejected: boolean
  icon:     string
  date:     string | null
}

const detailSteps = computed((): TimelineStep[] => {
  const mov = props.movement
  if (!mov) return []

  const isDep  = mov.type === 'DEPOSIT'
  const isWith = mov.type === 'WITHDRAWAL'

  if (isDep || isWith) {
    const done     = mov.status === 'COMPLETED'
    const rejected = mov.status === 'REJECTED'
    const pending  = mov.status === 'PENDING'

    return [
      {
        label:    'Solicitud recibida',
        sub:      isDep
          ? `Depósito de ${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} registrado`
          : `Retiro de ${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} solicitado`,
        done:     true,
        active:   false,
        rejected: false,
        icon:     'fa-paper-plane',
        date:     mov.created_at,
      },
      {
        label:    pending ? 'En revisión' : (done ? 'Revisión completada' : 'Revisión fallida'),
        sub:      pending
          ? 'Nuestro equipo está verificando tu solicitud'
          : done
            ? (isDep ? 'Comprobante verificado correctamente' : 'Solicitud de retiro aprobada')
            : (isDep ? 'El comprobante no pudo ser verificado' : 'La solicitud fue rechazada'),
        done:     done || rejected,
        active:   pending,
        rejected: false,
        icon:     pending ? 'fa-hourglass-half' : (done ? 'fa-check' : 'fa-times'),
        date:     !pending && mov.processed_at ? mov.processed_at : null,
      },
      {
        label:    done
          ? (isDep ? 'Fondos acreditados' : 'Retiro enviado')
          : rejected
            ? (isDep ? 'Depósito rechazado' : 'Retiro cancelado')
            : (isDep ? 'Pendiente de acreditación' : 'Pendiente de envío'),
        sub:      done
          ? (isDep
              ? `+${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} en tu billetera`
              : `${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} transferidos`)
          : rejected
            ? (isDep ? 'El monto no fue acreditado' : 'El saldo fue reintegrado a tu cuenta')
            : null,
        done,
        active:   false,
        rejected,
        icon:     done ? 'fa-check-circle' : (rejected ? 'fa-times-circle' : 'fa-circle'),
        date:     (done || rejected) && mov.processed_at ? mov.processed_at : null,
      },
    ]
  }

  // PURCHASE
  if (mov.type === 'PURCHASE') {
    return [
      {
        label:    'Pago procesado',
        sub:      `${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} descontados de tu saldo`,
        done:     true, active: false, rejected: false,
        icon:     'fa-check-circle',
        date:     mov.created_at,
      },
      {
        label:    'Cartón(es) asignados',
        sub:      mov.description ?? 'Tu participación en la sala fue registrada',
        done:     true, active: false, rejected: false,
        icon:     'fa-ticket-alt',
        date:     mov.created_at,
      },
    ]
  }

  // PRIZE
  if (mov.type === 'PRIZE') {
    return [
      {
        label:    '¡Premio ganado!',
        sub:      mov.description ?? 'Felicitaciones por tu victoria',
        done:     true, active: false, rejected: false,
        icon:     'fa-trophy',
        date:     mov.created_at,
      },
      {
        label:    'Fondos acreditados',
        sub:      `+${props.activeWallet?.currency.symbol ?? ''} ${formatAmount(mov.amount)} añadidos a tu billetera`,
        done:     true, active: false, rejected: false,
        icon:     'fa-check-circle',
        date:     mov.created_at,
      },
    ]
  }

  // TRIVIA_ENTRY
  return [
    {
      label:    'Entrada registrada',
      sub:      mov.description ?? 'Participación en sala de Trivia',
      done:     true, active: false, rejected: false,
      icon:     'fa-brain',
      date:     mov.created_at,
    },
  ]
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Detalle del movimiento"
    icon="fa-receipt"
    size="sm"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template v-if="movement && meta">

      <!-- Encabezado: ícono + tipo + monto -->
      <div class="mv-detail__header">
        <div class="mv-detail__icon" :class="meta.colorClass" :style="meta.colorStyle">
          <i :class="meta.icon + ' fa-lg'"></i>
        </div>
        <div class="mv-detail__header-info">
          <p class="mv-detail__type">{{ meta.label }}</p>
          <p
            class="mv-detail__amount"
            :class="meta.sign === '+' ? 'amount--positive' : 'amount--negative'"
          >
            {{ meta.sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.amount) }}
          </p>
        </div>
        <span
          class="badge-status mv-detail__badge"
          :class="{
            'badge-status--pending':   movement.status === 'PENDING',
            'badge-status--completed': movement.status === 'COMPLETED',
            'badge-status--rejected':  movement.status === 'REJECTED',
            'badge-status--cancelled': movement.status === 'CANCELLED',
          }"
        >
          <i :class="{
            'fas fa-clock':  movement.status === 'PENDING',
            'fas fa-check':  movement.status === 'COMPLETED',
            'fas fa-times':  movement.status === 'REJECTED',
            'fas fa-ban':    movement.status === 'CANCELLED',
          }"></i>
          {{
            movement.status === 'PENDING'   ? 'Pendiente'  :
            movement.status === 'COMPLETED' ? 'Completado' :
            movement.status === 'REJECTED'  ? 'Rechazado'  : 'Cancelado'
          }}
        </span>
      </div>

      <!-- Info financiera -->
      <div class="mv-detail__grid">
        <div class="mv-detail__cell mv-detail__cell--full">
          <span class="mv-detail__cell-label">ID de transacción</span>
          <span class="mv-detail__cell-value mv-detail__cell-value--mono">{{ movement.id }}</span>
        </div>
        <div class="mv-detail__cell">
          <span class="mv-detail__cell-label">Saldo anterior</span>
          <span class="mv-detail__cell-value">
            {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.balance_before) }}
          </span>
        </div>
        <div class="mv-detail__cell">
          <span class="mv-detail__cell-label">Saldo posterior</span>
          <span class="mv-detail__cell-value mv-detail__cell-value--highlight">
            {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.balance_after) }}
          </span>
        </div>
        <div class="mv-detail__cell">
          <span class="mv-detail__cell-label">Fecha</span>
          <span class="mv-detail__cell-value">{{ formatDate(movement.created_at) }}</span>
          <span class="mv-detail__cell-sub">{{ new Date(movement.created_at).toLocaleString('es-VE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
        </div>
        <div v-if="movement.description" class="mv-detail__cell mv-detail__cell--full">
          <span class="mv-detail__cell-label">Descripción</span>
          <span class="mv-detail__cell-value">{{ movement.description }}</span>
        </div>
        <div v-if="movement.admin_notes" class="mv-detail__cell mv-detail__cell--full">
          <span class="mv-detail__cell-label">Nota del equipo</span>
          <span class="mv-detail__cell-value">{{ movement.admin_notes }}</span>
        </div>
      </div>

      <!-- Comprobante (solo depósitos con receipt_url) -->
      <div v-if="movement.type === 'DEPOSIT' && movement.receipt_url" class="mv-detail__receipt">
        <p class="mv-detail__cell-label">Comprobante adjunto</p>
        <template v-if="/\.(jpg|jpeg|png|webp|gif)$/i.test(movement.receipt_path ?? '')">
          <img :src="movement.receipt_url" alt="Comprobante" class="mv-detail__receipt-img" />
        </template>
        <template v-else>
          <a :href="movement.receipt_url" target="_blank" rel="noopener" class="mv-detail__receipt-link">
            <i class="fas fa-file-pdf me-1"></i>
            {{ movement.receipt_path?.split('/').pop() ?? 'Ver comprobante' }}
          </a>
        </template>
        <p v-if="movement.reference_id" class="mv-detail__ref">
          Ref: <span class="mv-detail__ref-value">{{ movement.reference_id }}</span>
        </p>
      </div>

      <!-- Timeline de progreso -->
      <div class="mv-detail__timeline">
        <p class="mv-detail__timeline-title">Progreso</p>
        <ul class="mv-timeline">
          <li
            v-for="(step, i) in detailSteps"
            :key="i"
            class="mv-timeline__item"
            :class="{
              'mv-timeline__item--done':     step.done && !step.rejected,
              'mv-timeline__item--active':   step.active,
              'mv-timeline__item--rejected': step.rejected,
            }"
          >
            <div class="mv-timeline__dot">
              <i :class="'fas ' + step.icon"></i>
            </div>
            <div class="mv-timeline__body">
              <p class="mv-timeline__label">{{ step.label }}</p>
              <p v-if="step.sub" class="mv-timeline__sub">{{ step.sub }}</p>
              <p v-if="step.date" class="mv-timeline__date">
                <i class="fas fa-clock mv-timeline__date-icon"></i>{{ formatDate(step.date) }}
              </p>
            </div>
          </li>
        </ul>
      </div>

      <!-- Feedback contextual -->
      <div
        class="mv-detail__feedback"
        :class="{
          'mv-detail__feedback--pending':   movement.status === 'PENDING',
          'mv-detail__feedback--completed': movement.status === 'COMPLETED',
          'mv-detail__feedback--rejected':  movement.status === 'REJECTED',
        }"
      >
        <i :class="{
          'fas fa-hourglass-half': movement.status === 'PENDING',
          'fas fa-check-circle':  movement.status === 'COMPLETED',
          'fas fa-times-circle':  movement.status === 'REJECTED',
          'fas fa-ban':           movement.status === 'CANCELLED',
        }"></i>
        <span>
          <template v-if="movement.status === 'PENDING'">
            Tu solicitud está siendo revisada por nuestro equipo. Recibirás una notificación cuando sea procesada.
          </template>
          <template v-else-if="movement.status === 'COMPLETED'">
            {{
              movement.type === 'DEPOSIT'
                ? 'Los fondos han sido acreditados exitosamente a tu billetera.'
                : movement.type === 'WITHDRAWAL'
                ? 'Tu retiro fue procesado exitosamente.'
                : 'Transacción completada exitosamente.'
            }}
          </template>
          <template v-else-if="movement.status === 'REJECTED'">
            {{
              movement.type === 'DEPOSIT'
                ? 'Tu depósito fue rechazado. El monto no fue acreditado.'
                : 'Tu retiro fue rechazado. El monto ha sido reintegrado a tu saldo.'
            }}
          </template>
          <template v-else>
            Esta transacción fue cancelada.
          </template>
        </span>
      </div>

    </template>

    <!-- Footer: solo cerrar -->
    <template #footer>
      <button type="button" class="op-btn op-btn--cancel" @click="emit('update:modelValue', false)">
        <i class="fas fa-times"></i> Cerrar
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.amount--positive { color: #16a34a; }
.amount--negative { color: #e11d48; }

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

.op-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 20px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.op-btn--cancel {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1.5px solid var(--ds-border);
}
.op-btn--cancel:hover:not(:disabled) { background: var(--ds-surface-2); }

/* ══ Modal de detalle de movimiento ══ */
.mv-detail__header {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  background: var(--ds-surface-2);
  border-radius: 12px;
  padding: 1rem 1.1rem;
  margin-bottom: 1rem;
}
.mv-detail__icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.mv-detail__header-info { flex: 1; min-width: 0; }
.mv-detail__type {
  font-size: 0.78rem;
  color: var(--ds-text-muted);
  margin: 0 0 0.15rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.mv-detail__amount {
  font-size: 1.35rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}
.mv-detail__badge { margin-left: auto; flex-shrink: 0; align-self: flex-start; }

/* Info grid */
.mv-detail__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem 1rem;
  margin-bottom: 1.2rem;
}
.mv-detail__cell { display: flex; flex-direction: column; gap: 0.15rem; }
.mv-detail__cell--full { grid-column: 1 / -1; }
.mv-detail__cell-label {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 500;
}
.mv-detail__cell-value { font-size: 0.9rem; color: var(--ds-text-2); font-weight: 600; }
.mv-detail__cell-value--highlight { color: #667eea; }
.mv-detail__cell-sub { font-size: 0.72rem; color: var(--ds-text-muted); margin-top: 1px; }
.mv-detail__cell-value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--ds-text-2);
  word-break: break-all;
}

/* Timeline */
.mv-detail__timeline { margin-bottom: 1rem; }
.mv-detail__timeline-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ds-text-muted);
  margin: 0 0 0.75rem;
}
.mv-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.mv-timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: var(--ds-border);
}
.mv-timeline__item {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 0 0 1.1rem 0;
  position: relative;
}
.mv-timeline__item:last-child { padding-bottom: 0; }

.mv-timeline__dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  color: var(--ds-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  border: 2px solid var(--ds-border);
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.mv-timeline__item--done .mv-timeline__dot {
  background: rgba(34, 197, 94, .18);
  color: #16a34a;
  border-color: rgba(34, 197, 94, .4);
}
.mv-timeline__item--active .mv-timeline__dot {
  background: rgba(124, 58, 237, .18);
  color: #7c3aed;
  border-color: rgba(124, 58, 237, .4);
  animation: mv-pulse-dot 1.6s ease-in-out infinite;
}
.mv-timeline__item--rejected .mv-timeline__dot {
  background: rgba(220, 38, 38, .18);
  color: #dc2626;
  border-color: rgba(220, 38, 38, .4);
}
@keyframes mv-pulse-dot {
  0%, 100% { box-shadow: 0 0 0 0 rgba(124,58,237,0.25); }
  50%       { box-shadow: 0 0 0 6px rgba(124,58,237,0); }
}

.mv-timeline__body { padding-top: 4px; flex: 1; }
.mv-timeline__label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 0.15rem;
  line-height: 1.3;
}
.mv-timeline__item--active .mv-timeline__label { color: #7c3aed; }
.mv-timeline__item--rejected .mv-timeline__label { color: #dc2626; }

.mv-timeline__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0 0 0.2rem;
  line-height: 1.4;
}
.mv-timeline__date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}
.mv-timeline__date-icon { font-size: 0.62rem; }

/* Comprobante en modal detalle usuario */
.mv-detail__receipt {
  padding: 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.mv-detail__receipt-img {
  display: block;
  max-width: 100%;
  max-height: 200px;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.mv-detail__receipt-link {
  font-size: 0.82rem;
  color: #667eea;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.mv-detail__receipt-link:hover { text-decoration: underline; }
.mv-detail__ref { font-size: 0.75rem; color: var(--ds-text-2); margin: 0; }
.mv-detail__ref-value { font-family: ui-monospace, monospace; color: var(--ds-text-2); }

/* Feedback contextual */
.mv-detail__feedback {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  font-size: 0.83rem;
  line-height: 1.5;
}
.mv-detail__feedback i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
.mv-detail__feedback--pending   { background: rgba(245, 158, 11, .14); color: #d97706; }
.mv-detail__feedback--completed { background: rgba(34, 197, 94, .14); color: #4ade80; }
.mv-detail__feedback--rejected  { background: rgba(220, 38, 38, .14); color: #f87171; }
</style>
