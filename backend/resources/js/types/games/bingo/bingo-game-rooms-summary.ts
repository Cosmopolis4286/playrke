import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Game } from '../../game'
import type { TimeZone } from '../../time-zone'
import type { Commerce } from '../../roles/commerce/commerce'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface BingoGameRoomsSummaryPageProps {
    title?: string,
    commerces: {
        columns: Column[],
        rows: Commerce[]
    },
    commerces_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[]
}
