// useRoomRounds.ts
import type { RoundConfig } from '@/js/types/games/bingo/room/bingo-room'
import { normalizeToStep } from '../Utils/percentage'

/**
 * Rellena/ajusta el array roundsConfig al nuevo número de rondas.
 * - 1 ronda: fuerza FULL HOUSE y redistribuye el % disponible.
 * - N rondas: preserva configuraciones existentes y redistribuye equitativamente.
 */
export function syncRoundsConfig(
  count: number,
  roundsPct: number,
  currentConfigs: RoundConfig[],
  fullHouseId: string | number | null
): RoundConfig[] {
  const updated: RoundConfig[] = []

  for (let i = 0; i < count; i++) {
    const defaultPattern = count === 1 ? fullHouseId : null
    updated.push(
      currentConfigs[i] ?? {
        prizeType: 'percentage',
        bingoPattern: defaultPattern,
        prizeValue: null
      }
    )
  }

  if (count === 1 && fullHouseId !== null) {
    updated[0] = { ...updated[0], bingoPattern: fullHouseId }
  }

  return redistributePercentageRounds(updated, roundsPct)
}

/**
 * Distribuye equitativamente roundsPct entre las rondas con tipo 'percentage'.
 * Muta el array en copia inmutable.
 */
export function redistributePercentageRounds(
  configs: RoundConfig[],
  roundsPct: number
): RoundConfig[] {
  const total = Number(roundsPct) || 0
  const pctIndices = configs.reduce<number[]>((acc, r, i) => {
    if (r.prizeType === 'percentage') acc.push(i)
    return acc
  }, [])

  if (pctIndices.length === 0) return configs

  const perRound = total > 0 ? normalizeToStep(total / pctIndices.length, 0.01) : null
  return configs.map((r, i) =>
    pctIndices.includes(i) ? { ...r, prizeValue: perRound } : r
  )
}

/**
 * Calcula el máximo disponible para una ronda % = roundsPct - suma de las demás rondas %.
 */
export function getRoundMaxValue(
  index: number,
  configs: RoundConfig[],
  roundsPct: number,
  roundsPctMax: number
): number {
  const limit = Number(roundsPct) || roundsPctMax
  const othersSum = configs.reduce((sum, round, i) => {
    if (i === index || round.prizeType !== 'percentage') return sum
    return sum + (Number(round.prizeValue) || 0)
  }, 0)
  return Math.max(0, limit - othersSum)
}

/**
 * Actualiza un campo de una RoundConfig y gestiona los efectos secundarios:
 * - prizeType → limpiar campos del tipo anterior y redistribuir %
 * - prizeValue (%) → redistribuir el resto entre las demás rondas %
 */
export function updateRoundConfig(
  configs: RoundConfig[],
  index: number,
  field: keyof RoundConfig,
  value: RoundConfig[keyof RoundConfig],
  allowedCurrencies: (string | number)[],
  roundsPct: number
): RoundConfig[] {
  const copy = [...configs]
  const updated = { ...copy[index], [field]: value }

  if (field === 'prizeType') {
    if (value === 'percentage') {
      updated.prizeValueByCurrency = undefined
      copy[index] = updated
      return redistributePercentageRounds(copy, roundsPct)
    }

    if (value === 'fixed') {
      updated.prizeValue = null
      updated.prizeValueByCurrency = allowedCurrencies.reduce<Record<string, null>>(
        (acc, cur) => { acc[String(cur)] = null; return acc },
        {}
      )
      copy[index] = updated
      return redistributePercentageRounds(copy, roundsPct)
    }

    // prize
    updated.prizeValue = null
    updated.prizeValueByCurrency = undefined
    copy[index] = updated
    return redistributePercentageRounds(copy, roundsPct)
  }

  if (field === 'prizeValue' && updated.prizeType === 'percentage') {
    const total = Number(roundsPct) || 0
    const thisVal = Math.min(Number(value) || 0, total)
    updated.prizeValue = thisVal
    copy[index] = updated

    const others = copy.reduce<number[]>((acc, r, i) => {
      if (i !== index && r.prizeType === 'percentage') acc.push(i)
      return acc
    }, [])

    if (others.length > 0) {
      const remaining = Math.max(0, total - thisVal)
      const perOther = normalizeToStep(remaining / others.length, 0.01)
      others.forEach(i => {
        copy[i] = { ...copy[i], prizeValue: perOther > 0 ? perOther : null }
      })
    }

    return copy
  }

  if (field === 'bingoPattern' && updated.prizeType === 'percentage' && updated.prizeValue === null) {
    copy[index] = updated
    return redistributePercentageRounds(copy, roundsPct)
  }

  copy[index] = updated
  return copy
}

/**
 * Actualiza el precio fijo de una ronda para una moneda específica.
 */
export function updateRoundPriceByCurrency(
  configs: RoundConfig[],
  roundIndex: number,
  currency: string | number,
  value: string | number | null
): RoundConfig[] {
  const copy = [...configs]
  const key = String(currency)
  const current = { ...(copy[roundIndex].prizeValueByCurrency ?? {}) }
  const parsed = value === null || value === '' ? null : Number(value)
  current[key] = isNaN(Number(parsed)) ? null : (parsed as number)
  copy[roundIndex] = { ...copy[roundIndex], prizeValueByCurrency: current }
  return copy
}
