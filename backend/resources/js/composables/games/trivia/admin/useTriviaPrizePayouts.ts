import { ref } from 'vue'

export interface PrizePayout { rank: number; percent: number }

const DEFAULT_PAYOUTS: PrizePayout[] = [
  { rank: 1, percent: 0.60 },
  { rank: 2, percent: 0.25 },
  { rank: 3, percent: 0.15 },
]

/** Distribución de premio por posición (modelo "pool") — filas add/remove. */
export function useTriviaPrizePayouts(initial?: PrizePayout[]) {
  const prizePayouts = ref<PrizePayout[]>(initial && initial.length > 0 ? initial : [...DEFAULT_PAYOUTS])

  function addPrizePayout() {
    const nextRank = prizePayouts.value.length > 0
      ? Math.max(...prizePayouts.value.map(p => p.rank)) + 1
      : 1
    prizePayouts.value.push({ rank: nextRank, percent: 0 })
  }

  function removePrizePayout(index: number) {
    prizePayouts.value.splice(index, 1)
  }

  return { prizePayouts, addPrizePayout, removePrizePayout }
}

/** Inicializa desde una sala existente (Edit), o usa el default 60/25/15 (Create). */
export function initPrizePayoutsFromRoom(room: { prize_payouts?: unknown }): PrizePayout[] {
  if (Array.isArray(room.prize_payouts) && room.prize_payouts.length > 0) {
    return room.prize_payouts.map((p: any) => ({ rank: Number(p.rank), percent: Number(p.percent) }))
  }
  return [...DEFAULT_PAYOUTS]
}
