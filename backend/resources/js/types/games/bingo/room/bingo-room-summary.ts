import type { Currency } from '../../../currency'
import type { Country } from '../../../country'
import type { Game } from '../../../game'
import type { BingoRoom } from '../bingo-room'
import type { Column } from '../../../common/table/column-row'
import type { Pagination } from '../../../common/table/pagination'

export interface BingoRoomSummaryPageProps {
    title?: string,
    subtitle?: string,
    creator?: { type: string; id: string | number },
    rooms: {
        columns: Column[],
        rows: BingoRoom[]
    },
    rooms_pagination: Pagination | null,
    countries: Country[],
    currencies: Currency[],
    games: Game[],
    gameModes?: { id: string | number; description: string }[],
    prizePoolTypes?: { id: string | number; description: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
}
