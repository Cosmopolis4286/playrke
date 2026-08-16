import type { Currency } from '@/js/types/currency'
import type { Country } from '@/js/types/country'
import type { Game } from '@/js/types/game'
import type { TimeZone } from '@/js/types/time-zone'
import type { Seller } from '@/js/types/roles/seller/seller'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'

export interface ManagerOption {
    id: number | string
    name: string
}

export interface ManagerSellerPageProps {
    sellers: {
        columns: Column[],
        rows: Seller[]
    },
    sellers_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    roleUser: string,
    /** Id del vendedor a abrir automáticamente (llegada desde el botón "Ver más" de Admin > Usuarios). */
    openId?: string | number | null
}
