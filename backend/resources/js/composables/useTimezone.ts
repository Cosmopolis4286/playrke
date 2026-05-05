/**
 * Composable para manejo de zonas horarias en datetime-local inputs
 *
 * datetime-local siempre trabaja con hora local del navegador,
 * pero el backend espera UTC. Estas funciones manejan la conversión.
 */

/**
 * Convierte una fecha UTC (desde el servidor) a formato datetime-local (hora local)
 * @param utcDate - Fecha en formato ISO UTC (ej: "2026-05-04T20:00:00.000Z")
 * @returns Formato datetime-local (ej: "2026-05-04T17:00") en hora local del navegador
 */
export function utcToLocalInput(utcDate: string | null | undefined): string {
  if (!utcDate) return ''

  const date = new Date(utcDate)
  if (isNaN(date.getTime())) return ''

  // Ajustar al offset local del navegador
  const timezoneOffset = date.getTimezoneOffset() // en minutos
  const localDate = new Date(date.getTime() - timezoneOffset * 60 * 1000)

  // Slice(0, 16) da formato "YYYY-MM-DDTHH:mm"
  return localDate.toISOString().slice(0, 16)
}

/**
 * Convierte un valor de datetime-local (hora local del navegador) a UTC
 * @param localInput - Formato datetime-local (ej: "2026-05-04T20:00")
 * @returns Fecha en formato ISO UTC (ej: "2026-05-04T23:00:00.000Z") o null si es inválido
 */
export function localInputToUtc(localInput: string | null | undefined): string | null {
  if (!localInput) return null

  const date = new Date(localInput)
  if (isNaN(date.getTime())) return null

  return date.toISOString()
}

/**
 * Obtiene el datetime-local mínimo válido (ahora en hora local)
 * @returns Formato datetime-local para el atributo min del input
 */
export function getMinLocalDatetime(): string {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
}

/**
 * Formatea una fecha UTC para mostrar al usuario (formato legible local)
 * @param utcDate - Fecha UTC en formato ISO
 * @returns String formateado en hora local del navegador
 */
export function formatUtcForDisplay(utcDate: string | null | undefined): string {
  if (!utcDate) return ''

  const date = new Date(utcDate)
  if (isNaN(date.getTime())) return ''

  return date.toLocaleString()
}
