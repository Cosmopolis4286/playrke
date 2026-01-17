/**
 * Información básica de un juego
 */
export interface Game {
  readonly id: number // Identificador único del juego (ej: 1 para Bingo)
  readonly name: string // Nombre descriptivo del juego (ej: "Brasília Time")
  readonly status: string // Estado actual del juego (ej: 'ACTIVE', 'INACTIVE')
}
