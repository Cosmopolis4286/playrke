/**
 * Información de un juego del catálogo
 */
export interface Game {
    id: string                                    // Snowflake ID
    name: string                                  // Nombre en mayúsculas (BINGO, TRIVIA...)
    status: 'ACTIVE' | 'INACTIVE' | 'MAINTENANCE'
    status_text?: string                          // Accessor backend: 'Activo', 'Inactivo', 'Mantenimiento'
    is_active?: boolean
    created_at?: string
    updated_at?: string
}

export interface GamePageProps {
    games: Game[]
    gameModes: import('./gameMode').GameMode[]
    roomTypes: import('./roomType').RoomType[]
    prizePoolTypes: import('./prizePoolType').PrizePoolType[]
    roleUser: string
}
