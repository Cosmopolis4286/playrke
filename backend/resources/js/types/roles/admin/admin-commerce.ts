import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Game } from '../../game'
import type { TimeZone } from '../../time-zone'
import type { Commerce } from '../commerce/commerce'
import type { Column, Row } from '../../common/table/column-row'

export interface AdminCommercePageProps {
    commerces: Commerce[],
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    columns: Column[]
    rows: Row[]
}
