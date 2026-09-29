/**
 * Formatos numéricos/fecha compartidos por los widgets del dashboard
 * (montos de wallet, fechas de actividad/RKE).
 */
export function useDashboardFormatters() {
  function formatUsd(v: number, decimals = 2) {
    return new Intl.NumberFormat('es-VE', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(v)
  }

  function formatDate(iso: string | null) {
    if (!iso) return '—'
    return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
  }

  function formatDateTime(iso: string | null) {
    if (!iso) return '—'
    return new Intl.DateTimeFormat('es-VE', {
      day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso))
  }

  return { formatUsd, formatDate, formatDateTime }
}
