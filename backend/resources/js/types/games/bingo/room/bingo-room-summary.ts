import type { Currency } from '../../../currency'
import type { Country } from '../../../country'
import type { Game } from '../../../game'
import type { BingoRoom } from '../bingo-room'
import type { Column } from '../../../common/table/column-row'
import type { Pagination } from '../../../common/table/pagination'

export interface BingoRoomSummaryPageProps {
    title?: string,
    subtitle?: string,
    rooms: {
        columns: Column[],
        rows: BingoRoom[]
    },
    rooms_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    creator?: { type: string; id: string | number },
    gameModes?: { value: string | number; label: string }[],
    prizePoolTypes?: { value: string | number; label: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
}
