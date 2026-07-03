import { ref, computed, onUnmounted } from 'vue'

export function useLobbyLastSale() {
  const lastSaleAt = ref<number | null>(null)
  const elapsed    = ref(0)
  let ticker: ReturnType<typeof setInterval> | null = null

  function recordSale() {
    lastSaleAt.value = Date.now()
    elapsed.value    = 0
    if (!ticker) {
      ticker = setInterval(() => {
        if (lastSaleAt.value == null) return
        elapsed.value = Math.floor((Date.now() - lastSaleAt.value) / 1000)
      }, 1000)
    }
  }

  const lastSaleLabel = computed<string | null>(() => {
    if (lastSaleAt.value == null) return null
    if (elapsed.value < 5)   return 'ahora mismo'
    if (elapsed.value < 60)  return `hace ${elapsed.value}s`
    const m = Math.floor(elapsed.value / 60)
    return `hace ${m} min`
  })

  onUnmounted(() => { if (ticker) clearInterval(ticker) })

  return { recordSale, lastSaleLabel }
}
