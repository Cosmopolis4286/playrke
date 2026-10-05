export interface ExternalPlayer {
  id: string
  link: string
  first_name: string
  last_name: string
  phone: string
  identification: string | null
  status: 'ACTIVE' | 'INACTIVE' | 'BLOCKED'
  expiration_date: string | null
  created_at?: string
  game?: { id: string; name: string } | null
  seller_user?: { id: string; username: string; first_name: string; last_name: string; email: string } | null
  country?: { id: string; name: string } | null
  purchase_code?: { id: string; purchase_code: string } | null
  converted_user?: { id: string; username: string; email: string } | null
  /** Comercializadora del vendedor que captó a este jugador (directa o vía agencia) — null si no se pudo resolver. */
  commerce?: { id: string; name: string } | null
  /** Agencia/sucursal del vendedor — null si reporta directo al comercio sin agencia intermedia. */
  subsidiary?: { id: string; name: string } | null
}

/** Fila del histórico de auditoría — cambios de estado/link/conversión (ver ExternalPlayerAdminService::mapAuditRow()). */
export interface ExternalPlayerAuditEntry {
  id: string
  date: string
  actor: string
  event: string
}

/** Fila del histórico de compras (bingo_card_payments). */
export interface ExternalPlayerPurchaseEntry {
  id: string
  date: string
  amount: number
  status: string
  room: string | null
}

/** Fila del histórico de premios (bingo_card_prize_payments). */
export interface ExternalPlayerPrizeEntry {
  id: string
  date: string
  amount: number
  status: string
  method: string | null
}

/** Detalle consolidado del modal de ficha — datos básicos + preview de las 3 fuentes de actividad. */
export interface ExternalPlayerDetail extends ExternalPlayer {
  recent_purchases: ExternalPlayerPurchaseEntry[]
  recent_prizes: ExternalPlayerPrizeEntry[]
  recent_audits: ExternalPlayerAuditEntry[]
  has_more_purchases: boolean
  has_more_prizes: boolean
  has_more_audits: boolean
}

export interface HistoryPage<T> {
  rows: T[]
  has_more: boolean
  current_page: number
}

export interface ExternalPlayerStatistics {
  total: number
  active: number
  inactive: number
  blocked: number
}

export interface ExternalPlayerFilters {
  search?: string
  status?: '' | 'ACTIVE' | 'INACTIVE' | 'BLOCKED'
  game_id?: string
  country_id?: string
  seller_user_id?: string
  /** Búsqueda por nombre/email del vendedor — más práctico que exigir un ID exacto en la UI. */
  seller_search?: string
  /** Comercializadora del vendedor que captó al jugador (jerarquía, no filtro de texto). */
  commerce_id?: string
  /** Agencia/sucursal del vendedor — se acota automáticamente al elegir un commerce_id. */
  subsidiary_id?: string
}

export interface GameOption {
  id: string
  name: string
}

/** Catálogo de comercios/agencias para los selects de filtro por jerarquía — ya acotado por rol server-side. */
export interface HierarchyOptions {
  commerces: { id: string; name: string }[]
  subsidiaries: { id: string; name: string; commerce_id: string }[]
}

export interface PaginatedResult<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
