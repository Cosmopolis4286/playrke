import type { Currency } from '@/js/Services/walletService'
import type { BingoRoomAutomatic } from '../bingo-room-automatic'
import type { Column } from '@/js/types/common/table/column-row'
import type { Pagination } from '@/js/types/common/table/pagination'

export interface BingoRoomAutomaticSummaryPageProps {
    title?: string,
    subtitle?: string,
    rooms: {
        columns: Column[],
        rows: BingoRoomAutomatic[]
    },
    rooms_pagination: Pagination | null,
    currencies: Currency[],
    roomTypes: { id: string | number; label: string }[],
    gameModes: { id: string | number; label: string }[],
    prizePoolTypes: { id: string | number; code: string; label: string }[],
    bingoPatterns?: { id: string | number; name: string }[]
}
