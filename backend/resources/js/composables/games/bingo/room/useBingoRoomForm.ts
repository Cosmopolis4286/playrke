import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import { useBingoRoomBaseForm } from './useBingoRoomBaseForm'

export function useBingoRoomForm(
    modelValueGetter: () => BingoRoomFormData,
    emit: (event: 'update:modelValue', value: BingoRoomFormData) => void,
    automaticRoom: () => boolean
) {
    return useBingoRoomBaseForm(modelValueGetter, emit, automaticRoom)
}
