import type { Currency } from '../../currency'
import type { Country } from '../../country'
import { Game } from '../../game'
import { TimeZone } from '../../time-zone'
import { Commerce } from '../commerce/commerce'
import { Column } from '../../common/table/column'

export interface AdminCommercePageProps {
    commerces: Commerce[],
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    timeZones: TimeZone[],
    columns: Column[]
}
