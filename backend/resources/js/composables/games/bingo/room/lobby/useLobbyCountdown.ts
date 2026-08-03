import { ref, watch, onUnmounted } from 'vue'

export function useLobbyCountdown(getUnixDate: (() => number | null | undefined) | number | null | undefined) {
  const countdown = ref('')
  const isLive    = ref(false)
  const isUrgent  = ref(false)
  let timer: ReturnType<typeof setInterval> | null = null

  const resolve = typeof getUnixDate === 'function' ? getUnixDate : () => getUnixDate

  function update() {
    const unixDate = resolve()
    if (unixDate == null) { countdown.value = ''; return }
    const diff = unixDate - Math.floor(Date.now() / 1000)
    if (diff <= 0) {
      isLive.value    = true
      isUrgent.value  = false
      countdown.value = 'EN VIVO'
      if (timer) { clearInterval(timer); timer = null }
      return
    }
    isLive.value   = false
    isUrgent.value = diff < 300
    // Máximo 3 unidades de tiempo, siempre las 3 más significativas —
    // mismo criterio que GameRoomsKpiHeader.vue. Antes, sin tope de días,
    // una sala programada a 3 días vista mostraba "72:15:40" en vez de
    // "3d 00h 15m" (h crecía sin límite más allá de 24).
    const days = Math.floor(diff / 86400)
    if (days >= 1) {
      const hours   = Math.floor((diff % 86400) / 3600)
      const minutes = Math.floor((diff % 3600) / 60)
      countdown.value = `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m`
      return
    }
    const h = Math.floor(diff / 3600)
    const m = Math.floor((diff % 3600) / 60)
    const s = diff % 60
    countdown.value = h > 0
      ? `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
      : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  }

  function start() {
    if (timer) { clearInterval(timer); timer = null }
    update()
    if (resolve() != null) timer = setInterval(update, 1000)
  }

  function stop() {
    if (timer) { clearInterval(timer); timer = null }
  }

  // Pausa el interval cuando la pestaña está oculta; resincroniza al volver.
  // Evita drift acumulado y ahorra CPU en background.
  function onVisibilityChange() {
    if (document.hidden) {
      stop()
    } else {
      update()
      if (resolve() != null && !isLive.value) timer = setInterval(update, 1000)
    }
  }

  document.addEventListener('visibilitychange', onVisibilityChange)

  // Reacciona si la fecha cambia (ej. backend la actualiza)
  if (typeof getUnixDate === 'function') {
    watch(getUnixDate, start, { immediate: true })
  } else {
    start()
  }

  onUnmounted(() => {
    stop()
    document.removeEventListener('visibilitychange', onVisibilityChange)
  })

  return { countdown, isLive, isUrgent }
}
