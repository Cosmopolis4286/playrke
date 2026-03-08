/**
 * Configuración de sala de juego para la vista de jugadores.
 * Representa los campos expuestos por la relación `config` en el API.
 */
export interface BingoGameRoomConfig {
  readonly room_id: string | number
  /** Precio del cartón en moneda base */
  readonly card_price: number
  /** Tipo de premio: 1 = bote acumulado, otro = premio fijo */
  readonly prize_pool_type: number
  /** Timestamp Unix de inicio de la sala */
  readonly unix_date: number
  /** Fecha en formato ISO */
  readonly date: string
  /** Hora en formato HH:mm */
  readonly time: string
}

/**
 * Jugadores de la sala asociados al usuario actual.
 */
export interface BingoGameRoomPlayer {
  /** Cantidad de cartones del jugador */
  readonly ctns: number
  /** Si tiene automarcador activo */
  readonly automarker?: boolean
}

/**
 * Sala de juego de bingo vista desde el jugador/vendedor.
 * Basado en los datos expuestos por `viewGameRooms` context.
 */
export interface BingoGameRoom {
  /** ID snowflake (string para evitar truncamiento JS) */
  readonly id: string
  /** Código UUID legible */
  readonly uuid_code: string | null
  /** Premio acumulado */
  readonly prize_pool: number
  /** Total de jugadores */
  readonly total_players: number
  /** ID del usuario creador */
  readonly creator_user_id: string | number
  /** Configuración de la sala */
  readonly config?: BingoGameRoomConfig | null
  /** Cartones del jugador actual en esta sala */
  readonly players_room?: BingoGameRoomPlayer[] | null
  /**
   * Código de estado de la sala: 'ACTIVE' = disponible para comprar,
   * 'PLAYING' = en juego, etc.
   */
  readonly status_code: string
  /** Encrypted ID para rutas legacy */
  readonly encrypted?: string | null
}

/**
 * Datos de tiempo para las salas de juego.
 * Corresponde a `$dateTimeRooms` del blade original.
 */
export interface BingoGameRoomsDatetime {
  /** Timestamp del servidor en ms */
  readonly timestamp: string | number
  /** Lista de salas con su unix_date */
  readonly rooms: Array<{
    readonly encrypted: string
    readonly config: { readonly unix_date: number }
    readonly players_room: boolean | null
  }>
}

/**
 * Información de sala obtenida al hacer clic (fetch individual).
 * Corresponde a `res.room` del blade original.
 */
export interface BingoGameRoomDetail {
  readonly cardPrice: number
  readonly prizePool: number
  readonly prizePoolType: string
  readonly currency: string
  readonly dateTime: number
}

/**
 * Props de la página GameRooms (Inertia).
 */
export interface BingoGameRoomsPageProps {
  /** Título de la página */
  readonly title?: string
  /** Datos del creador */
  readonly creator?: { readonly id: string | number; readonly type: string }
  /** Moneda activa */
  readonly currency?: string | null
  /** Identificador del jugador */
  readonly player?: string | null
  /** Token del usuario */
  readonly userToken?: string | null
  /** ID encriptado del cliente */
  readonly customerId?: string | null
  /** Símbolo de la moneda (ej. USD, BS) */
  readonly currencySym?: string
  /** Tasa de cambio aplicada al prize_pool */
  readonly exchangeRate?: number
  /** Salas activas de juego */
  readonly rooms: BingoGameRoom[]
  /** Datos de fecha/hora del servidor + salas */
  readonly dateTimeRooms?: BingoGameRoomsDatetime | null
  /** Usuario creador (para rutas de fecha/hora) */
  readonly creatorUser?: string | null
}
