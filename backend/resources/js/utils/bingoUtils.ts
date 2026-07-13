/**
 * Abrevia un serial de cartón para mostrar en espacios reducidos.
 * Ej: "A1B2-C3D4-E5F6" → "A1B2-…-E5F6"
 * Si el serial no tiene guiones, devuelve los últimos `tail` caracteres.
 */
export function shortSerial(s: string, tail = 12): string {
  if (!s) return '—'
  const p = s.split('-')
  return p.length >= 3 ? `${p[0]}-…-${p[p.length - 1]}` : s.slice(-tail)
}
