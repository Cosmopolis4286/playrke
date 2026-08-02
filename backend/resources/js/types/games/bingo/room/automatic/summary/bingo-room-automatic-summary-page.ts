import type { Currency } from '@/js/types/currency'
import type { BingoRoomAutomatic } from '../bingo-room-automatic'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'
import type { SelectOption } from "@js/types/common/form/form";

export interface BingoRoomAutomaticSummaryPageProps {
    title?: string,
    subtitle?: string,
    rooms: {
        columns: Column[],
        rows: BingoRoomAutomatic[]
    },
    rooms_pagination: Pagination | null,
    currencies: Currency[],
    currenciesWithActiveRate: string[],
    roomTypes: { id: string | number; code: string; label: string }[],
    gameModes: SelectOption[],
    gameModeCatalog: { id: string | number; code: string; label: string }[],
    prizePoolTypes: { id: string | number; code: string; label: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
}
