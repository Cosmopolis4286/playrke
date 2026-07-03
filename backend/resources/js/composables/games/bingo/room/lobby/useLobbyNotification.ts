import { ref, readonly } from 'vue'

type PermissionState = 'default' | 'granted' | 'denied' | 'unsupported'

export function useLobbyNotification(roomName: string, gameUrl: string) {
  const supported  = 'Notification' in window
  const permission = ref<PermissionState>(
    supported ? (Notification.permission as PermissionState) : 'unsupported'
  )

  async function requestPermission(): Promise<boolean> {
    if (!supported || permission.value === 'granted') return permission.value === 'granted'
    const result = await Notification.requestPermission()
    permission.value = result as PermissionState
    return result === 'granted'
  }

  function notify() {
    if (!supported || Notification.permission !== 'granted') return
    const n = new Notification(`¡El Bingo ha comenzado! 🎱`, {
      body: `La sala "${roomName}" está en juego. Haz clic para entrar.`,
      icon: '/img/games/bingo/estelar_fd.png',
      tag:  'bingo-game-started',
    })
    n.onclick = () => {
      window.focus()
      n.close()
      window.location.href = gameUrl
    }
  }

  return { permission: readonly(permission), requestPermission, notify }
}
