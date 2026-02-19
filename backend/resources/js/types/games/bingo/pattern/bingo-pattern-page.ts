import type { BingoPattern } from './bingo-pattern'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'

export interface BingoPatternPageProps {
    prizes: {
        columns: Column[]
        rows: BingoPattern[]
    }
    prizes_pagination: Pagination | null
}
