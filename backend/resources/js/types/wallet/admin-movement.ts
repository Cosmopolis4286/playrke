export interface MovementUser {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
  username:   string | null
}

export interface MovementAdmin {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
}

export interface MovementCurrency {
  iso_code:       string
  symbol:         string
  decimal_places: number
}

export interface MovementMetadata {
  directory_id?:   string
  directory_type?: string
  [key: string]:   unknown
}

export interface Movement {
  id:             string
  type:           string
  status:         'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'
  amount:         number
  balance_before: number
  balance_after:  number
  description:    string | null
  reference_id:   string | null
  receipt_path:   string | null
  receipt_url:    string | null
  metadata:       MovementMetadata | null
  prize_type:     'DIGITAL' | 'PHYSICAL' | null
  game_id:        string | null
  admin_notes:    string | null
  processed_at:   string | null
  processed_by:   MovementAdmin | null
  created_at:     string
  currency:       MovementCurrency | null
  user:           MovementUser | null
}

export interface MovementsMeta {
  current_page: number
  last_page:    number
  per_page:     number
  total:        number
}
