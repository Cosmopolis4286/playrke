import type { Country } from '../../country'
import type { TimeZone } from '../../time-zone'
import type { Player } from '../player/player'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface AdminPlayerPageProps {
    players: {
        columns: Column[]
        rows: Player[]
    }
    players_pagination: Pagination | null
    countries: Country[]
    timeZones: TimeZone[]
}
