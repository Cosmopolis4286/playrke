export type BingoPatternStatus     = 'ACTIVE' | 'INACTIVE'
export type BingoPatternRewardType = 'PRODUCT' | 'SERVICE'
export type BingoPatternKit        = 'Y' | 'N'

export interface BingoPattern {
    readonly id?:          number | string
    readonly code:         string
    readonly reward_type:  BingoPatternRewardType
    readonly name:         string
    readonly kit:          BingoPatternKit
    readonly num_items:    number
    readonly price:        string | number
    readonly description?: string | null
    readonly status:       BingoPatternStatus
    readonly user_id?:     number | string
}
