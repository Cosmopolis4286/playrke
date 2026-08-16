import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Game } from '../../game'
import type { TimeZone } from '../../time-zone'
import type { Customer } from '../customer/customer'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface AdminCustomerPageProps {
    customers: {
        columns: Column[],
        rows: Customer[]
    },
    customers_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    /** Id del cliente a abrir automáticamente (llegada desde el botón "Ver más" de Admin > Usuarios). */
    openId?: string | number | null
}
