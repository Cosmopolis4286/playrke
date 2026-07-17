import { ref, watch, onUnmounted, type ComputedRef } from 'vue'
import type { BingoVariantDef } from '@/js/modules/games/bingo/utils/bingoVariants'
import type { BingoCard } from '@/js/types/games/bingo/card/in-game/bingo-card'

interface DetectionDeps {
  cartons: ComputedRef<BingoCard[]>
  drawnNumbers: ComputedRef<number[]>
  variantDef: ComputedRef<BingoVariantDef>
  hotThreshold: ComputedRef<number>
  fullBingo: (card: BingoCard) => boolean
  isHot: (card: BingoCard) => boolean
  onBingoDetected: (cards: BingoCard[]) => void
  onCardHit: (cardId: string) => void
  onAnyHit: () => void
}

/**
 * Detección de BINGO + flash de "hit" + flash de "recién caliente" (fresh-hot) +
 * racha de aciertos consecutivos, todo derivado de un único watcher sobre
 * drawnNumbers. Las 3 Sets + contadores de versión + Maps de timers se mantienen
 * juntos deliberadamente: están interleaved en un solo paso por props.cartons
 * por rendimiento, y separarlos en watchers independientes perdería el orden
 * (p.ej. el early-continue `if (_bingoSet.has(id) || _freshHotSet.has(id)) continue`).
 */
export function useBingoCardDetection(deps: DetectionDeps) {
  const { cartons, drawnNumbers, variantDef, hotThreshold, fullBingo, isHot, onBingoDetected, onCardHit, onAnyHit } = deps

  // Sets internos (no reactivos) + contador de versión para forzar re-render.
  // Vue no detecta .add()/.delete() en un ref<Set> — el patrón version-bump
  // es la forma correcta: mutar el Set y luego incrementar el contador para
  // que los computed/bindings que leen el contador se recalculen.
  const _bingoSet = new Set<string>()
  const _hitSet = new Set<string>()
  const _freshHotSet = new Set<string>()
  const _bingoVer = ref(0)
  const _hitVer = ref(0)
  const _freshHotVer = ref(0)

  const hitTimers = new Map<string, ReturnType<typeof setTimeout>>()
  const freshHotTimers = new Map<string, ReturnType<typeof setTimeout>>()

  // Racha: cuántas bolas consecutivas cayeron en al menos un cartón del jugador
  const streak = ref(0)

  function isWinner(card: BingoCard) { void _bingoVer.value; return _bingoSet.has(card.id) }
  function isHit(card: BingoCard) { void _hitVer.value; return _hitSet.has(card.id) }
  function isFreshHot(card: BingoCard) { void _freshHotVer.value; return _freshHotSet.has(card.id) }

  onUnmounted(() => {
    hitTimers.forEach(clearTimeout)
    freshHotTimers.forEach(clearTimeout)
  })

  watch(drawnNumbers, (next, prev) => {
    if (next.length === 0) {
      _bingoSet.clear();    _bingoVer.value++
      hitTimers.forEach(clearTimeout)
      hitTimers.clear()
      _hitSet.clear();      _hitVer.value++
      freshHotTimers.forEach(clearTimeout)
      freshHotTimers.clear()
      _freshHotSet.clear(); _freshHotVer.value++
      streak.value = 0
      return
    }

    // Cuando el historial cambia de forma no monotónica (reconexión, corrección de sorteo),
    // revalidar qué cartones siguen siendo BINGO con el draw actualizado.
    if ((prev?.length ?? 0) > next.length) {
      let changed = false
      for (const id of [..._bingoSet]) {
        const card = cartons.value.find(c => c.id === id)
        if (!card || !fullBingo(card)) { _bingoSet.delete(id); changed = true }
      }
      if (changed) _bingoVer.value++
    }

    const prevSet = new Set(prev ?? [])
    const newNums = next.filter(n => !prevSet.has(n))

    // Flash en cartones que recibieron alguna de las bolas nuevas (también dispara sonido)
    let anyHit = false
    if (newNums.length > 0 && prev !== undefined) {
      const newNumSet = new Set(newNums)
      for (const card of cartons.value) {
        const hit = variantDef.value.columns.some(col => {
          const m = card.cardMatrix as Record<string, (number | null)[]> | null
          return m?.[col.label]?.some(n => n !== null && newNumSet.has(n)) ?? false
        })
        if (hit) {
          anyHit = true
          _hitSet.add(card.id); _hitVer.value++
          onCardHit(card.id)
          // Limpiar flash existente si llega otra bola antes de que expire
          if (hitTimers.has(card.id)) clearTimeout(hitTimers.get(card.id)!)
          const t = setTimeout(() => {
            _hitSet.delete(card.id); _hitVer.value++
            hitTimers.delete(card.id)
          }, 700)
          hitTimers.set(card.id, t)
        }
      }
      if (anyHit) {
        onAnyHit()
        streak.value++
      } else if (newNums.length > 0) {
        streak.value = 0
      }
    }

    const newWinners: BingoCard[] = []
    for (const card of cartons.value) {
      if (_bingoSet.has(card.id)) continue
      if (fullBingo(card)) {
        _bingoSet.add(card.id)
        if (prev !== undefined) newWinners.push(card)
      }
    }
    if (newWinners.length > 0) {
      _bingoVer.value++
      onBingoDetected(newWinners)
    }

    // Detectar cartones que acaban de cruzar el umbral HOT por primera vez
    if (prev !== undefined) {
      const prevDrawnSet = new Set(prev ?? [])
      for (const card of cartons.value) {
        if (_bingoSet.has(card.id) || _freshHotSet.has(card.id)) continue
        if (isHot(card)) {
          // Verificar que antes NO era hot (recalculando con el draw anterior)
          const prevHits = variantDef.value.columns.reduce((acc, col) => {
            const m = card.cardMatrix as Record<string, (number | null)[]> | null
            return acc + (m?.[col.label]?.filter(n => n !== null && prevDrawnSet.has(n as number)).length ?? 0)
          }, 0)
          if (prevHits < hotThreshold.value) {
            _freshHotSet.add(card.id); _freshHotVer.value++
            if (freshHotTimers.has(card.id)) clearTimeout(freshHotTimers.get(card.id)!)
            const t = setTimeout(() => {
              _freshHotSet.delete(card.id); _freshHotVer.value++
              freshHotTimers.delete(card.id)
            }, 1500)
            freshHotTimers.set(card.id, t)
          }
        }
      }
    }
  }, { immediate: true })

  return {
    streak,
    isWinner, isHit, isFreshHot,
    _freshHotVer, _freshHotSet,
  }
}
