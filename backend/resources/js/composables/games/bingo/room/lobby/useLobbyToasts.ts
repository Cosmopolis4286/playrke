import { ref } from 'vue'

export interface SaleToast { id: number; text: string }

export function useLobbyToasts() {
  const saleToasts = ref<SaleToast[]>([])
  let seq = 0

  function pushSaleToast(totalCards: number) {
    const id = ++seq
    saleToasts.value.push({ id, text: `+1 cartón vendido · ${totalCards} total` })
    setTimeout(() => {
      saleToasts.value = saleToasts.value.filter(t => t.id !== id)
    }, 3200)
  }

  return { saleToasts, pushSaleToast }
}
