/**
 * Formateadores puros de valores de sala (fecha, moneda, número, porcentaje)
 * y resolución de labels de estado/opciones para BingoRoomDetails.
 */
export const fmt = {
  date(dateStr?: string | null, timeStr?: string | null): string {
    if (!dateStr) return '-'
    const dateTime = timeStr ? `${dateStr}T${timeStr}` : dateStr
    const d = new Date(dateTime)
    if (isNaN(d.getTime())) return '-'
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  },

  currency(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(n)
  },

  /**
   * Monto en moneda local (no USD) — se usa para campos que ya vienen
   * convertidos a la moneda de cada currency_id (ej. metrics_by_currency,
   * card_price_by_currency, local_amount de premios). Se formatea sin
   * símbolo de moneda para no aparentar ser dólares; el ISO code se
   * muestra aparte (ej. "VES 780,00").
   */
  localAmount(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n)
  },

  number(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES').format(n)
  },

  pct(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return `${n}%`
  }
}

export const VARIANT_LABELS: Record<string, string> = {
  BINGO_75: 'Bingo 75 bolas (5×5)',
  BINGO_90: 'Bingo 90 bolas (3×9)',
  BINGO_80: 'Bingo 80 bolas (4×4)',
  BINGO_30: 'Speed Bingo 30 bolas (3×3)',
}

export function statusLabel(status?: string): string {
  return ({
    ACTIVE: 'Disponible', IN_PROGRESS: 'En Juego', FINISHED: 'Finalizada',
    REFUND: 'Reembolso', OFFLINE: 'Fuera de Línea', INACTIVE: 'Inactiva',
    DELETED: 'Eliminada', CANCELLED: 'Cancelada'
  }[status ?? ''] ?? status ?? '-')
}

export function statusClass(status?: string): string {
  return ({
    ACTIVE: 'badge-success', IN_PROGRESS: 'badge-primary', FINISHED: 'badge-secondary',
    REFUND: 'badge-warning', OFFLINE: 'badge-dark', INACTIVE: 'badge-light',
    DELETED: 'badge-danger', CANCELLED: 'badge-danger'
  }[status ?? ''] ?? 'badge-light')
}

export function resolveOption(opts: { value: string; label: string }[], id?: string | number | null): string {
  return id ? opts.find(o => String(o.value) === String(id))?.label ?? String(id) : '-'
}
