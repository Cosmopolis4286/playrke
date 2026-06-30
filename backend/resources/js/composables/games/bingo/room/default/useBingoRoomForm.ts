import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import { useBingoRoomBaseForm } from '../shared/useBingoRoomBaseForm'

export function useBingoRoomForm(
    modelValueGetter: () => BingoRoomFormData,
    emit: (event: 'update:modelValue', value: BingoRoomFormData) => void,
    automaticRoom: () => boolean,
    useBaseUsd: () => boolean = () => true,
    isEdit: () => boolean = () => false,
    currenciesWithActiveRate: () => string[] = () => []
) {
    return useBingoRoomBaseForm(modelValueGetter, emit, automaticRoom, useBaseUsd, isEdit, currenciesWithActiveRate)
}
