/**
 * Información de un país
 */
export interface Country {
  readonly id: number
  readonly name: string
  readonly iso_code: string // Código ISO, ej: "BR"
}

/**
 * Información básica de un juego
 */
export interface Game {
  readonly id: number // Identificador único del juego (ej: 1 para Bingo)
  readonly name: string // Nombre descriptivo del juego (ej: "Brasília Time")
  readonly status: string // Estado actual del juego (ej: 'ACTIVE', 'INACTIVE')
}

/**
 * Información de zona horaria
 */
export interface TimeZone {
  readonly id: string // Ej: "America/Sao_Paulo"
  readonly name: string // Nombre descriptivo, ej: "Brasília Time"
  readonly utc: string // Offset UTC, ej: "UTC-03"
  readonly country_id?: number // ID del país asociado (opcional)
}
