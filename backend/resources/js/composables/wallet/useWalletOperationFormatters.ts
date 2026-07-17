export function formatAmount(value: number, decimalPlaces: number): string {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: decimalPlaces,
    maximumFractionDigits: decimalPlaces,
  }).format(value)
}

export function formatOperationDate(iso: string): string {
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
