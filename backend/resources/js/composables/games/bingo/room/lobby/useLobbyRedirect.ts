import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const REDIRECT_DELAY = 3000

export function useLobbyRedirect(
  roomRouteKey: string | undefined,
  statusCode: () => string,
) {
  const redirecting  = ref(false)
  const redirectSecs = ref(0)
  let redirectTimer: ReturnType<typeof setTimeout>  | null = null
  let tickTimer:     ReturnType<typeof setInterval> | null = null

  function trigger() {
    if (redirecting.value || !roomRouteKey) return
    redirecting.value  = true
    redirectSecs.value = Math.round(REDIRECT_DELAY / 1000)

    tickTimer = setInterval(() => {
      redirectSecs.value = Math.max(0, redirectSecs.value - 1)
    }, 1000)

    redirectTimer = setTimeout(() => {
      if (tickTimer) clearInterval(tickTimer)
      router.visit(`/bingo/${roomRouteKey}/game`)
    }, REDIRECT_DELAY)
  }

  onMounted(() => {
    if (statusCode()?.toUpperCase() === 'IN_PROGRESS') trigger()
  })

  onUnmounted(() => {
    if (redirectTimer) clearTimeout(redirectTimer)
    if (tickTimer)     clearInterval(tickTimer)
  })

  watch(statusCode, (next) => {
    if (next?.toUpperCase() === 'IN_PROGRESS') trigger()
  })

  return { redirecting, redirectSecs }
}
