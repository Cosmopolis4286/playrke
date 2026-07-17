import { ref, onUnmounted, type Ref } from 'vue'

/**
 * Resalta brevemente un cartón (anillo pulsante) sin depender de selectedId,
 * para no forzar la apertura del modal de detalle — llamado por el padre justo
 * después de hacer scrollIntoView vía data-card-id (ver Index.vue::focusCard).
 */
export function useBingoFocusPulse(activeFilter: Ref<'all' | 'hot' | 'win'>, cardIds: () => string[]) {
  const focusPulseId = ref<string | null>(null)
  let focusPulseTimer: ReturnType<typeof setTimeout> | null = null

  function pulseCard(id: string) {
    if (!cardIds().includes(id)) activeFilter.value = 'all'
    if (focusPulseTimer) clearTimeout(focusPulseTimer)
    focusPulseId.value = id
    focusPulseTimer = setTimeout(() => { focusPulseId.value = null; focusPulseTimer = null }, 1600)
  }

  onUnmounted(() => {
    if (focusPulseTimer) clearTimeout(focusPulseTimer)
  })

  return { focusPulseId, pulseCard }
}
