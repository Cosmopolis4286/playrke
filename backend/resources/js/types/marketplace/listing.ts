export interface MarketplaceCurrency {
  id: string
  symbol: string
  code: string
}

export interface MarketplaceAsset {
  id: string
  code: string
  name: string
  category: string
  rarity: string
}

export interface Listing {
  id: string
  seller_id: string
  seller_name: string
  quantity: number
  price: number
  status: string
  currency: MarketplaceCurrency
  asset: MarketplaceAsset
  expires_at: string | null
  created_at: string
}

export interface AssetDefinitionInfo {
  code: string
  name: string
  category: string
  rarity: string
  tradable: boolean
  consumable: boolean
  marketplace_enabled: boolean
}

export interface PlayerAsset {
  id: string
  quantity: number
  status: string
  acquired_via: string
  definition: AssetDefinitionInfo
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
}
