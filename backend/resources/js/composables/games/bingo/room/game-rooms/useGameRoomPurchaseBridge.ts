import type { Ref } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

/**
 * Parche local del listado de salas tras una compra exitosa (prize_pool y
 * cartones propios del usuario), sin esperar un refetch/reload — nombrado
 * "Bridge" (no "Purchase") para no confundirse con useGameRoomPurchaseModal.ts,
 * que sigue siendo el dueño del flujo de compra en sí.
 */
export function useGameRoomPurchaseBridge(rooms: Ref<BingoGameRoom[]>, currentUserId: () => string | number | null | undefined) {
  function onPurchaseSuccess(
    roomId: string,
    result: { prizePool: number; cardCount: number },
  ): void {
    const idx = rooms.value.findIndex(r => r.id === roomId)
    if (idx === -1) return

    const room = rooms.value[idx]
    const userId = String(currentUserId() ?? '')
    const existing = room.players_room?.find(p => String((p as any).user_id ?? '') === userId)

    if (!existing) return

    rooms.value[idx] = {
      ...room,
      prize_pool: result.prizePool,
      players_room: room.players_room?.map(p =>
        String((p as any).user_id ?? '') === userId
          ? { ...p, card_count: (p.card_count ?? 0) + result.cardCount }
          : p
      ) ?? [],
    }
  }

  return { onPurchaseSuccess }
}
