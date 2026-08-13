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
}

export interface GameOption {
  id: string
  name: string
}

export interface PaginatedResult<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
