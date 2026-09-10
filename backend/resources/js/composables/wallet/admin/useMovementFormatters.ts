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

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('es-VE', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

/**
 * SLA interno para movimientos PENDING sin resolver: hasta 4h es normal,
 * entre 4h y 24h se marca como advertencia, y más de 24h como crítico —
 * ayuda al admin a priorizar la cola sin tener que ordenar manualmente por
 * fecha en cada revisión.
 */
const AGE_WARNING_HOURS  = 4
const AGE_CRITICAL_HOURS = 24

export interface AgeInfo {
  label: string
  level: 'normal' | 'warning' | 'critical'
  cls: string
}

export function ageInfo(createdAt: string): AgeInfo {
  const hours = (Date.now() - new Date(createdAt).getTime()) / 3_600_000
  const level: AgeInfo['level'] =
    hours >= AGE_CRITICAL_HOURS ? 'critical' :
    hours >= AGE_WARNING_HOURS  ? 'warning'  : 'normal'

  const label =
    hours < 1  ? 'hace unos minutos' :
    hours < 24 ? `hace ${Math.floor(hours)}h` :
    `hace ${Math.floor(hours / 24)}d`

  return { label, level, cls: `mv-age mv-age--${level}` }
}

/**
 * Escala de resolución dinámica para el KPI "Resolución media" — antes se
 * mostraba siempre en minutos crudos (ej. "1847.3 min"), ilegible cuando el
 * promedio del día sube a horas o días. Reusa los mismos umbrales visuales
 * que ageInfo (4h/24h) para que el admin asocie el mismo código de color
 * entre "antigüedad de un pendiente" y "qué tan rápido se resuelve en general".
 */
const RESOLUTION_SLOW_MINUTES     = AGE_WARNING_HOURS * 60
const RESOLUTION_CRITICAL_MINUTES = AGE_CRITICAL_HOURS * 60

export interface ResolutionTimeInfo {
  label: string
  level: 'fast' | 'normal' | 'slow'
}

export function resolutionTimeInfo(minutes: number | null): ResolutionTimeInfo {
  if (minutes === null) {
    return { label: '—', level: 'normal' }
  }

  const level: ResolutionTimeInfo['level'] =
    minutes >= RESOLUTION_CRITICAL_MINUTES ? 'slow' :
    minutes >= RESOLUTION_SLOW_MINUTES     ? 'normal' : 'fast'

  const label =
    minutes < 60   ? `${Math.round(minutes)} min` :
    minutes < 1440 ? `${(minutes / 60).toFixed(1)} h` :
    `${(minutes / 1440).toFixed(1)} d`

  return { label, level }
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

  // is_credit resuelto desde el catálogo real (no una lista hardcodeada) para
  // que cualquier tipo nuevo (ej. BONUS_PRIZE) muestre el signo correcto sin
  // tener que tocar este archivo. Fallback a débito (signo '-') si el tipo no
  // está en catálogo — más seguro que asumir crédito por defecto.
  const isCreditByCode = computed<Record<string, boolean>>(() => {
    const types = Array.isArray(movementTypes) ? movementTypes : movementTypes.value
    const map: Record<string, boolean> = {}
    for (const type of types) {
      map[type.code] = type.is_credit
    }
    return map
  })

  function formatAmount(mov: Movement): string {
    const dp  = mov.currency?.decimal_places ?? 2
    const sym = mov.currency?.symbol ?? ''
    const val = new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: dp,
      maximumFractionDigits: dp,
    }).format(mov.amount)
    const sign = isCreditByCode.value[mov.type] ? '+' : '-'
    return `${sign}${sym} ${val}`
  }

  return {
    typeConfig, isCreditByCode,
    prizeTypeConfig, statusConfig, directoryTypeLabel,
    userName, adminName, formatAmount, formatDate,
  }
}
