import type { Prize }      from './prize'
import type { Column }     from '../common/table/column-row'
import type { Pagination } from '../common/table/pagination'

export interface PrizePageProps {
    prizes: {
        columns: Column[]
        rows:    Prize[]
    }
    prizes_pagination: Pagination | null
}
