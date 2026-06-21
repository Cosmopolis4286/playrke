import { ref } from 'vue'
import echo from '@/js/plugins/echo'

interface PresenceMember {
  id: string
  username: string
}

/**
 * Contador reactivo de usuarios en línea, alimentado por el canal de
 * presencia global `online-users`. Es un singleton: solo se une al canal
 * una vez por sesión de la SPA (ver joinOnlinePresence en inertia.ts).
 */
export const onlineCount = ref(0)
export const isPresenceLive = ref(false)

let joined = false

/**
 * Une al usuario autenticado actual al canal de presencia global.
 * Debe llamarse una sola vez al iniciar la SPA (no por componente),
 * de lo contrario Echo registra múltiples suscripciones redundantes.
 */
export function joinOnlinePresence(): void {
  if (joined) return
  joined = true

  const channel = echo.join('online-users')

  channel.here((members: PresenceMember[]) => {
    onlineCount.value = members.length
    isPresenceLive.value = true
  })

  channel.joining(() => {
    onlineCount.value++
  })

  channel.leaving(() => {
    onlineCount.value = Math.max(0, onlineCount.value - 1)
  })

  channel.error(() => {
    isPresenceLive.value = false
  })
}
