export type BingoPatternStatus      = 'ACTIVE' | 'INACTIVE'
export type BingoPatternType        = 'UNIQUE' | 'MULTIPLE'
export type BingoPatternDefault     = 'Y' | 'N'

export interface BingoPattern {
    readonly id?:               number | string
    readonly name:              string
    readonly type_pattern:      BingoPatternType
    readonly type_description?: string
    readonly default_pattern:   BingoPatternDefault
    readonly boxes:             string
    readonly boxes_array?:      string[]
    readonly boxes_count?:      number
    readonly status:            BingoPatternStatus
    readonly status_description?: string
    readonly user_id?:          number | string
    readonly created_at?:       string
    readonly updated_at?:       string
}
