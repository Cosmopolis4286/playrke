import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Game } from '../../game'
import type { TimeZone } from '../../time-zone'
import type { Treasurer } from '../treasurer/treasurer'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface AdminTreasurerPageProps {
    treasurers: {
        columns: Column[],
        rows: Treasurer[]
    },
    treasurers_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    /** Id del tesorero a abrir automáticamente (llegada desde el botón "Ver más" de Admin > Usuarios). */
    openId?: string | number | null
}
