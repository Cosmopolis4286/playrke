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
}

export interface ExternalPlayerStatistics {
  total: number
  active: number
  inactive: number
  blocked: number
}
