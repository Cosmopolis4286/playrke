import { onUnmounted, ref } from 'vue'

export interface SaleToast { id: number; text: string }

export function useLobbyToasts() {
  const saleToasts = ref<SaleToast[]>([])
  let seq = 0
  const timers = new Set<ReturnType<typeof setTimeout>>()

  function pushSaleToast(totalCards: number) {
    const id = ++seq
    saleToasts.value.push({ id, text: `+1 cartón vendido · ${totalCards} total` })
    const t = setTimeout(() => {
      saleToasts.value = saleToasts.value.filter(t => t.id !== id)
      timers.delete(t)
    }, 3200)
    timers.add(t)
  }

  onUnmounted(() => { timers.forEach(clearTimeout) })

  return { saleToasts, pushSaleToast }
}
