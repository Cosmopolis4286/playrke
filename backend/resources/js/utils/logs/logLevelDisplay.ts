export function formatLevelName(level: string): string {
  const map: Record<string, string> = {
    error: 'Error',
    warn:  'Advertencia',
  }
  return map[level.toLowerCase()] ?? level
}

export function getLevelBadgeClass(level: string): string {
  const map: Record<string, string> = {
    error: 'badge-red',
    warn:  'badge-yellow',
  }
  return map[level.toLowerCase()] ?? 'badge-secondary'
}

export function getLevelIcon(level: string): string {
  const map: Record<string, string> = {
    error: 'fas fa-times-circle',
    warn:  'fas fa-exclamation-triangle',
  }
  return map[level.toLowerCase()] ?? 'fas fa-info-circle'
}
