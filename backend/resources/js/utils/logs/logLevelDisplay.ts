export function formatLevelName(level: string): string {
  const map: Record<string, string> = {
    emergency: 'Emergencia',
    alert:     'Alerta',
    critical:  'Crítico',
    error:     'Error',
    warn:      'Advertencia',
  }
  return map[level.toLowerCase()] ?? level
}

export function getLevelBadgeClass(level: string): string {
  const map: Record<string, string> = {
    emergency: 'badge-critical',
    alert:     'badge-critical',
    critical:  'badge-critical',
    error:     'badge-red',
    warn:      'badge-yellow',
  }
  return map[level.toLowerCase()] ?? 'badge-secondary'
}

export function getLevelIcon(level: string): string {
  const map: Record<string, string> = {
    emergency: 'fas fa-skull-crossbones',
    alert:     'fas fa-triangle-exclamation',
    critical:  'fas fa-radiation',
    error:     'fas fa-times-circle',
    warn:      'fas fa-exclamation-triangle',
  }
  return map[level.toLowerCase()] ?? 'fas fa-info-circle'
}

/**
 * Niveles que ameritan resaltado adicional en la UI (más allá del badge de
 * color normal) — sistema caído (emergency/alert) o error de alto impacto
 * (critical), a diferencia de un error común que ya es visible por sí solo.
 */
export function isHighSeverityLevel(level: string): boolean {
  return ['emergency', 'alert', 'critical'].includes(level.toLowerCase())
}
