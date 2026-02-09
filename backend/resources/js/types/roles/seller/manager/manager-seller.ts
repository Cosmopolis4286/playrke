import type { Currency } from '@/js/types/currency'
import type { Country } from '@/js/types/country'
import type { Game } from '@/js/types/game'
import type { TimeZone } from '@/js/types/time-zone'
import type { Seller } from '@/js/types/roles/seller/seller'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'

export interface ManagerSellerPageProps {
    commerces: {
        columns: Column[],
        rows: Seller[]
    },
    commerces_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[]
}
