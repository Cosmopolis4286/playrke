/**
 * Contrato de WinEventDetailService::build() — vista admin del detalle de
 * una ganancia de cualquier juego. Las secciones son genéricas: cada juego
 * aporta las suyas desde su WinEventDetailResolver en el backend.
 */

export type WinEventDetailItemType = 'text' | 'money' | 'datetime' | 'badge' | 'code' | 'numbers'

export type WinEventDetailVariant = 'success' | 'warning' | 'danger' | 'info' | 'secondary'

export interface WinEventDetailItem {
  label: string
  value: unknown
  type: WinEventDetailItemType
  variant: WinEventDetailVariant | null
}

/** Celda de tabla: texto plano o badge `{ value, variant }`. */
export type WinEventDetailCell = string | number | null | { value: string; variant: WinEventDetailVariant }

export interface WinEventDetailTable {
  title: string
  columns: { key: string; label: string }[]
  /** `_highlight: true` resalta la fila (ej. el ganador dentro del ranking). */
  rows: (Record<string, WinEventDetailCell> & { _highlight?: boolean })[]
}

export interface WinEventDetailSection {
  key: string
  title: string
  icon: string
  items: WinEventDetailItem[]
  tables: WinEventDetailTable[]
}

export interface WinEventDetail {
  event: {
    id: string
    game_slug: string
    game_name: string
    prize_kind: 'money' | 'item' | 'service'
    prize_kind_label: string
    prize_label: string
    amount_usd: number | null
    amount_local: number | null
    currency: { iso_code: string; symbol: string; decimal_places: number } | null
    source_type: string
    source_type_label: string
    source_reference: string | null
    reported_by: string | null
    created_at: string | null
  }
  winner: {
    has_account: boolean
    user_id: string | null
    name: string
    username: string | null
    email: string | null
  }
  sections: WinEventDetailSection[]
  has_game_detail: boolean
}
