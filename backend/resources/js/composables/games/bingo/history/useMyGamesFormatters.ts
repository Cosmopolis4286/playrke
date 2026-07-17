export function formatDate(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('es-VE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

export function variantLabel(v: string): string {
  const map: Record<string, string> = {
    BINGO_75: '75 bolas',
    BINGO_90: '90 bolas',
    BINGO_80: '80 bolas',
    BINGO_30: '30 bolas',
  }
  return map[v] ?? v
}

export function roomStatusBadge(status: string | null): { label: string; cls: string } {
  const map: Record<string, { label: string; cls: string }> = {
    ACTIVE:      { label: 'Abierta',    cls: 'badge bg-success' },
    IN_PROGRESS: { label: 'En curso',   cls: 'badge bg-primary' },
    INACTIVE:    { label: 'Inactiva',   cls: 'badge bg-light' },
    FINISHED:    { label: 'Finalizada', cls: 'badge bg-secondary' },
    CANCELLED:   { label: 'Cancelada',  cls: 'badge bg-danger' },
    DELETED:     { label: 'Eliminada',  cls: 'badge bg-danger' },
  }
  return map[status ?? ''] ?? { label: status ?? '—', cls: 'badge bg-light' }
}

/** Salas que aún no terminaron: "No gané" sería una conclusión prematura y engañosa. */
export function isRoomOngoing(status: string | null): boolean {
  return status === 'ACTIVE' || status === 'IN_PROGRESS'
}

export function roundStatusLabel(s: string): string {
  const map: Record<string, string> = {
    PENDING:  'Pendiente',
    ACTIVE:   'En curso',
    FINISHED: 'Finalizada',
  }
  return map[s] ?? s
}

export function roundStatusCls(s: string): string {
  return s === 'FINISHED' ? 'badge bg-secondary' : s === 'ACTIVE' ? 'badge bg-primary' : 'badge bg-light'
}

export function prizeStatusCls(s: string): string {
  const map: Record<string, string> = {
    waiting:    'badge bg-warning text-dark',
    processing: 'badge bg-info text-dark',
    claimed:    'badge bg-success',
    cancelled:  'badge bg-danger',
    suspended:  'badge bg-light',
  }
  return map[s] ?? 'badge bg-light'
}

export function formatAmount(amount: number, isUsd: boolean, localAmount: number, symbol: string, decimals: number): string {
  if (isUsd) {
    return `${amount.toFixed(2)} USD`
  }
  const local = localAmount > 0 ? localAmount : amount
  return `${symbol}${local.toFixed(decimals)}`
}

export function formatPrizeAmount(prize: { usd_amount: number; local_amount: number; currency: string; currency_symbol: string; decimal_places: number; is_usd: boolean }): string {
  return formatAmount(prize.usd_amount, prize.is_usd, prize.local_amount, prize.currency_symbol, prize.decimal_places)
}

export function formatTotalAmount(d: { total_usd: number; total_local: number; currency: string; currency_symbol: string; decimal_places: number; is_usd: boolean }): string {
  return formatAmount(d.total_usd, d.is_usd, d.total_local, d.currency_symbol, d.decimal_places)
}

/** Gasto y neto vienen solo en moneda local (no traen su par USD por separado). */
export function formatLocalOnly(amount: number, symbol: string, decimals: number, isUsd: boolean): string {
  return isUsd ? `${amount.toFixed(2)} USD` : `${symbol}${amount.toFixed(decimals)}`
}
