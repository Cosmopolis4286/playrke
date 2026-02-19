import type { BingoPattern } from './bingo-pattern'
import type { Column } from '@/js/types/common/table/column-row'

export interface BingoPatternPageProps {
    bingoPatterns: {
        columns: Column[]
        rows:    BingoPattern[]
    }
}
