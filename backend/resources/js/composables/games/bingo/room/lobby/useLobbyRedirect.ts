import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export function useLobbyRedirect(
  roomRouteKey: string | undefined,
  statusCode: () => string,
) {
  const redirecting = ref(false)
  let timer: ReturnType<typeof setTimeout> | null = null

  function trigger(fromWs = false) {
    if (redirecting.value || !roomRouteKey) return
    redirecting.value = true
    const delay = fromWs ? 3000 : 1500
    timer = setTimeout(() => {
      router.visit(`/bingo/${roomRouteKey}/game`)
    }, delay)
  }

  onMounted(() => {
    if (statusCode()?.toUpperCase() === 'IN_PROGRESS') trigger(false)
  })

  onUnmounted(() => { if (timer) clearTimeout(timer) })

  watch(statusCode, (next) => {
    if (next?.toUpperCase() === 'IN_PROGRESS') trigger(true)
  })

  return { redirecting }
}
