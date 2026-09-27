import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import { useBingoRoomBaseForm } from '../shared/useBingoRoomBaseForm'
import type { BingoRoomValidationContext } from '../shared/bingoRoomFormSchema'

export function useBingoRoomForm(
    modelValueGetter: () => BingoRoomFormData,
    emit: (event: 'update:modelValue', value: BingoRoomFormData) => void,
    automaticRoom: () => boolean,
    useBaseUsd: () => boolean = () => true,
    isEdit: () => boolean = () => false,
    currenciesWithActiveRate: () => string[] = () => [],
    privateRoomTypeId: () => string | number | null = () => null,
    catalogCtx: () => Pick<BingoRoomValidationContext, 'requiresAmountAddedPrizePool' | 'activeJackpotTypeIds'> = () => ({})
) {
    return useBingoRoomBaseForm(modelValueGetter, emit, automaticRoom, useBaseUsd, isEdit, currenciesWithActiveRate, privateRoomTypeId, catalogCtx)
}
