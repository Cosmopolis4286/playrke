import type { BingoRoomAutomaticFormData } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'
import { useBingoRoomBaseForm } from '../shared/useBingoRoomBaseForm'

export function useBingoRoomAutomaticForm(
    modelValueGetter: () => BingoRoomAutomaticFormData,
    emit: (event: 'update:modelValue', value: BingoRoomAutomaticFormData) => void,
    currenciesWithActiveRate: () => string[] = () => []
) {
    return useBingoRoomBaseForm(
        modelValueGetter,
        emit,
        () => true,
        () => true,
        () => false,
        currenciesWithActiveRate,
    )
}
