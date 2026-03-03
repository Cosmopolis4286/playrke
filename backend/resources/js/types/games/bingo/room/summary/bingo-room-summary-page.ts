import type { Currency } from '@/js/types/currency'
import type { BingoRoom } from '../bingo-room'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'
import type { SelectOption } from "@/js/types/common/form/form"

export interface BingoRoomSummaryPageProps {
    title?: string,
    subtitle?: string,
    rooms: {
        columns: Column[],
        rows: BingoRoom[]
    },
    rooms_pagination: Pagination | null,
    currencies: Currency[],
    roomTypes: SelectOption[],
    gameModes: SelectOption[],
    prizePoolTypes: { id: string | number; code: string; label: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
}
