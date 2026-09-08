import type { Currency } from '@/js/types/currency'
import type { BingoRoom } from '../bingo-room'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'
import type { SelectOption } from "@js/types/common/form/form"

export interface BingoRoomSummaryPageProps {
    title?: string,
    subtitle?: string,
    rooms: {
        columns: Column[],
        rows: BingoRoom[]
    },
    rooms_pagination: Pagination | null,
    currencies: Currency[],
    currenciesWithActiveRate: string[],
    roomTypes: { id: string | number; code: string; label: string }[],
    gameModes: SelectOption[],
    gameModeCatalog: { id: string | number; code: string; label: string }[],
    prizePoolTypes: { id: string | number; code: string; label: string }[],
    jackpotTypes: { id: string | number; code: string; label: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
    /** Catálogo de comercializadoras — solo poblado para admin/superadmin, ver BingoRoomContextService::summaryGameRooms(). */
    commerces?: { id: string | number; name: string }[]
}
