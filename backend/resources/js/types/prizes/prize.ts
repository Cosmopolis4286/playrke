export type PrizeStatus     = 'ACTIVE' | 'INACTIVE'
export type PrizeRewardType = 'PRODUCT' | 'SERVICE'
export type PrizeKit        = 'Y' | 'N'

export interface PrizeSearchResult {
    id:    string | number
    code:  string
    name:  string
    price: string | number
}

export interface Prize {
    readonly id?:          number | string
    readonly code:         string
    readonly reward_type:  PrizeRewardType
    readonly name:         string
    readonly kit:          PrizeKit
    readonly num_items:    number
    readonly kit_items:    (string | number)[]
    readonly price:        string | number
    readonly description?: string | null
    readonly status:       PrizeStatus
    readonly user_id?:     number | string
}
