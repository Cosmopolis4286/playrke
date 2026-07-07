import { ref, onUnmounted, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export function useDrawScheduler(routeKey: Ref<string>) {
  const drawStarting     = ref(false)
  const drawStartingSecs = ref(10)
  let   drawStartTimer: ReturnType<typeof setInterval> | null = null
  let   drawTriggered    = false

  function clearDrawTimer() {
    if (drawStartTimer !== null) { clearInterval(drawStartTimer); drawStartTimer = null }
    drawStarting.value     = false
    drawStartingSecs.value = 10
    drawTriggered          = false
  }

  async function triggerStartDraw() {
    try {
      await backendClient.post(`/bingo/${routeKey.value}/dev-start-draw`)
    } catch (e) {
      console.error('[BingoDraw] Error al iniciar sorteo:', e)
    }
  }

  function scheduleDrawStart() {
    clearDrawTimer()
    drawStarting.value     = true
    drawStartingSecs.value = 10
    drawStartTimer = setInterval(() => {
      drawStartingSecs.value -= 1
      if (drawStartingSecs.value <= 0) {
        if (!drawTriggered) { drawTriggered = true; triggerStartDraw() }
        clearDrawTimer()
      }
    }, 1_000)
  }

  onUnmounted(() => clearDrawTimer())

  return { drawStarting, drawStartingSecs, clearDrawTimer, scheduleDrawStart }
}
