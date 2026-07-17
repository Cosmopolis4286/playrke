import { ref, watch, onUnmounted, type Ref } from 'vue'

/**
 * "Vendiendo rápido": detecta un salto de +2 o más cartones vendidos entre dos
 * actualizaciones de la prop (polling/websocket externo) y muestra el badge
 * unos segundos. Sin llamadas propias al backend — solo compara el valor
 * entrante con el anterior.
 */
export function useSellingFastIndicator(totalCards: Ref<number | undefined>) {
  const showSellingFast = ref(false)
  let sellingFastTimeout: ReturnType<typeof setTimeout> | null = null

  watch(totalCards, (next, prev) => {
    if (prev == null || next == null || next - prev < 2) return
    showSellingFast.value = true
    if (sellingFastTimeout) clearTimeout(sellingFastTimeout)
    sellingFastTimeout = setTimeout(() => { showSellingFast.value = false }, 8000)
  })

  onUnmounted(() => {
    if (sellingFastTimeout) clearTimeout(sellingFastTimeout)
  })

  return { showSellingFast }
}
