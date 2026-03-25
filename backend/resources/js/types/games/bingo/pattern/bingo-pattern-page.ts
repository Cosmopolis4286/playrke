import type { BingoPattern } from './bingo-patterns'
import type { Column } from '@/js/types/common/table/column-row'

export interface BingoPatternPageProps {
    bingoPatterns: {
        columns:  Column[]
        rows:     BingoPattern[]
        roleUser: string | null
    }
}
