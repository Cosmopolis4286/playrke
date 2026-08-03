import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Navegación al lobby de una sala desde el listado de salas de juego.
 */
export function useGameRoomNavigation() {
  const redirectingToLobby = ref(false)

  function goToLobby(roomId: string): void {
    redirectingToLobby.value = true
    router.visit(`/bingo/${roomId}/lobby`, {
      onFinish: () => { redirectingToLobby.value = false },
    })
  }

  return {
    redirectingToLobby,
    goToLobby,
  }
}
