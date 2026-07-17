import { computed, type Ref } from 'vue'
import type { MovementType } from '@js/types/movementType'
import type { Movement, MovementUser, MovementAdmin } from '@js/types/wallet/admin-movement'

const KNOWN_TYPE_CLASSES: Record<string, string> = {
  DEPOSIT:      'mv-badge--deposit',
  WITHDRAWAL:   'mv-badge--withdrawal',
  PURCHASE:     'mv-badge--purchase',
  PRIZE:        'mv-badge--prize',
  TRIVIA_ENTRY: 'mv-badge--trivia-entry',
}

export const prizeTypeConfig: Record<'DIGITAL' | 'PHYSICAL', { label: string; icon: string }> = {
  DIGITAL:  { label: 'Digital', icon: 'fas fa-coins' },
  PHYSICAL: { label: 'Físico',  icon: 'fas fa-gift'  },
}

export const statusConfig: Record<string, { label: string; cls: string }> = {
  PENDING:   { label: 'Pendiente',  cls: 'mv-status mv-status--pending'   },
  COMPLETED: { label: 'Completado', cls: 'mv-status mv-status--completed' },
  CANCELLED: { label: 'Cancelado',  cls: 'mv-status mv-status--cancelled' },
  REJECTED:  { label: 'Rechazado',  cls: 'mv-status mv-status--rejected'  },
}

export const directoryTypeLabel: Record<string, string> = {
  pago_movil: 'Pago Móvil',
  pix:        'PIX',
  binance:    'Binance',
}

export function userName(u: MovementUser | null): string {
  if (!u) return '—'
  const full = [u.first_name, u.last_name].filter(Boolean).join(' ')
  return full || u.username || '—'
}

export function adminName(u: MovementAdmin | null): string {
  if (!u) return '—'
  return [u.first_name, u.last_name].filter(Boolean).join(' ') || u.email
}

export function formatAmount(mov: Movement): string {
  const dp  = mov.currency?.decimal_places ?? 2
  const sym = mov.currency?.symbol ?? ''
  const val = new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: dp,
    maximumFractionDigits: dp,
  }).format(mov.amount)
  const sign = ['DEPOSIT', 'PRIZE'].includes(mov.type) ? '+' : '-'
  return `${sign}${sym} ${val}`
}

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('es-VE', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

/**
 * Helpers visuales derivados del catálogo MovementType recibido por props
 * (admin/superadmin lo gestionan vía CRUD).
 */
export function useMovementFormatters(movementTypes: Ref<MovementType[]> | MovementType[]) {
  const typeConfig = computed<Record<string, { label: string; cls: string; style?: string; icon: string }>>(() => {
    const types = Array.isArray(movementTypes) ? movementTypes : movementTypes.value
    const map: Record<string, { label: string; cls: string; style?: string; icon: string }> = {}
    for (const type of types) {
      const knownClass = KNOWN_TYPE_CLASSES[type.code]
      map[type.code] = {
        label: type.label,
        cls:   knownClass ? `mv-badge ${knownClass}` : 'mv-badge',
        style: knownClass ? undefined : `background: ${type.color}1a; color: ${type.color}`,
        icon:  type.icon,
      }
    }
    return map
  })

  return {
    typeConfig,
    prizeTypeConfig, statusConfig, directoryTypeLabel,
    userName, adminName, formatAmount, formatDate,
  }
}
