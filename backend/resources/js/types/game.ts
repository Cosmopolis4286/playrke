/**
 * Información de un juego del catálogo
 */
export interface Game {
    readonly id: string                                    // Snowflake ID
    readonly name: string                                  // Nombre en mayúsculas (BINGO, TRIVIA...)
    readonly status: 'ACTIVE' | 'INACTIVE' | 'MAINTENANCE'
    readonly status_text?: string                          // Accessor backend: 'Activo', 'Inactivo', 'Mantenimiento'
    readonly is_active?: boolean
    readonly created_at?: string
    readonly updated_at?: string
}

export interface GamePageProps {
    games: Game[]
    roleUser: string
}
