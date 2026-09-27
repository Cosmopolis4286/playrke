import type { SelectOption } from "@js/types/common/form/form";

export interface BingoCardGeneratePageProps {
    title: string
    roomTypes: SelectOption[]
    gameModes: SelectOption[]
    prizePoolTypes: SelectOption[]
    /** false si el vendedor o su comercio/agencia no está ACTIVE (ver BingoCardGenerateService::resolveSellAvailability). */
    canSell: boolean
    blockedMessage: string | null
}
