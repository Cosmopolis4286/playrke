/**
 * Configuración de sala de juego para la vista de jugadores.
 * Representa los campos expuestos por la relación `config` en el API.
 */
export interface BingoGameRoomConfig {
  readonly room_id: string | number
  /** Precio del cartón en USD (moneda base del sistema) */
  readonly card_price_usd: number
  /** Código semántico del tipo de bote: 'ACCUMULATIVE' | 'FIXED' | 'MIXED' | 'PRIZE' */
  readonly prize_pool_type: string | null
  /** Timestamp Unix de inicio de la sala */
  readonly unix_date: number
  /** Fecha en formato ISO */
  readonly date: string
  /** Hora en formato HH:mm */
  readonly time: string
  /** Cantidad mínima de cartones vendidos para iniciar el sorteo (si aplica) */
  readonly min_cards_to_start?: number | null
  /** Máximo de jugadores permitidos en la sala (si aplica) */
  readonly max_players?: number | null
  /** Variante de bingo de la sala (BINGO_75, BINGO_90, BINGO_80, BINGO_30) */
  readonly game_variant?: string | null
  /** Si la sala permite que el jugador elija sus propios números */
  readonly allow_custom_card?: boolean | null
  /** Recargo porcentual sobre el precio base al comprar cartón personalizado (0 = sin recargo) */
  readonly custom_card_price_pct?: number | null
  /** Modalidad del cartón personalizado: 'paid' (recargo manual), 'by_range' (recargo automático), 'every_x' (gratis cada X cartones), 'free' (uso libre) */
  readonly custom_card_mode?: 'paid' | 'by_range' | 'every_x' | 'free' | null
  /** Cantidad de cartones para que el siguiente cartón personalizado sea gratis (solo si custom_card_mode = 'every_x') */
  readonly custom_card_every_x?: number | null
  /** Modalidad de "mover casilla libre" (recargo independiente, sumado a custom_card_price_pct); null = no permitido (solo BINGO_75) */
  readonly move_free_space_mode?: 'paid' | 'by_range' | 'free' | null
  /** % de recargo por mover la libre si move_free_space_mode = 'paid' o 'by_range' */
  readonly move_free_space_price_pct?: number | null
}

/**
 * Jugadores de la sala asociados al usuario actual.
 */
export interface BingoGameRoomPlayer {
  /** Cantidad de cartones del jugador */
  readonly card_count: number
  /** Si tiene automarcador activo */
  readonly automarker?: boolean
}

/**
 * Preview anónimo de un jugador en la sala, para prueba social visual.
 * Solo expone la inicial del nombre — nunca username/email completos.
 */
export interface BingoGameRoomPlayerPreview {
  readonly initial: string
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
  /** Tipo de sala */
  readonly room_type: 'PRIVATE' | 'PUBLIC' | 'VIP' | 'DEMO' | 'MIXED'
  /** Tipo de conexión (ONLINE, OFFLINE, MIXED). Nombre de campo pendiente de alinear con backend. */
  readonly game_mode?: string | null
  /** Configuración de la sala */
  readonly config?: BingoGameRoomConfig | null
  /** Cartones del jugador actual en esta sala */
  readonly players_room?: BingoGameRoomPlayer[] | null
  /** Preview anónimo (máx. 4) de jugadores en la sala, para prueba social */
  readonly players_preview?: BingoGameRoomPlayerPreview[] | null
  /**
   * Código de estado de la sala: 'ACTIVE' = disponible para comprar,
   * 'PLAYING' = en juego, etc.
   */
  readonly status_code: string
  /** Encrypted ID para rutas legacy */
  readonly encrypted?: string | null
  /** Precio del cartón para jugadores (si aplica) */
  readonly card_price?: number
  /** Símbolo de moneda del precio del cartón */
  readonly currency_symbol?: string
  /** Cantidad de rondas de la sala */
  readonly rounds_count?: number | null
  /** Total de cartones vendidos en la sala (todos los jugadores) */
  readonly total_cards?: number
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
  /** Precios del cartón por sala (para jugadores) */
  readonly cardPrices?: Record<string, { card_price: number; currency_symbol: string }> | null
  /** Texto del reglamento del bingo (HTML, configurable por admin) */
  readonly bingoRules?: string | null
  /** Path relativo de la imagen del reglamento del bingo */
  readonly imageBingoRules?: string | null
}
