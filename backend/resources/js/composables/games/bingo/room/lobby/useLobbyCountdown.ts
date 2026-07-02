import { ref, onMounted, onUnmounted } from 'vue'

export function useLobbyCountdown(unixDate: number | null | undefined) {
  const countdown = ref('')
  const isLive    = ref(false)
  const isUrgent  = ref(false)
  let timer: ReturnType<typeof setInterval> | null = null

  function update() {
    if (!unixDate) { countdown.value = ''; return }
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
    const h = Math.floor(diff / 3600)
    const m = Math.floor((diff % 3600) / 60)
    const s = diff % 60
    countdown.value = h > 0
      ? `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
      : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  }

  onMounted(() => {
    if (!unixDate) return
    update()
    timer = setInterval(update, 1000)
  })

  onUnmounted(() => { if (timer) clearInterval(timer) })

  return { countdown, isLive, isUrgent }
}
