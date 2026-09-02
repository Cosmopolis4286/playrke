import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Game } from '../../game'
import type { TimeZone } from '../../time-zone'
import type { Commerce } from '../commerce/commerce'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface AdminCommercePageProps {
    commerces: {
        columns: Column[],
        rows: Commerce[]
    },
    commerces_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    /** Id de la comercializadora a abrir automáticamente (llegada desde el botón "Ver más" de Admin > Usuarios). */
    openId?: string | number | null
    /** ID real (sin hashear) del comercio marcado como principal/vinculado a la app. Null si ninguno lo está. */
    default_commerce_id: string | null
}
