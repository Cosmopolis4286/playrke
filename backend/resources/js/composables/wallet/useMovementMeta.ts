import { computed, type ComputedRef } from 'vue'
import type { WalletPageMovementType } from '@js/types/wallet'

// ─── Mapeo code -> clase CSS de color para los tipos conocidos del sistema.
// Tipos custom nuevos creados desde el CRUD caen al fallback de color hex
// inline del catálogo (sin necesitar tocar este archivo).
const KNOWN_COLOR_CLASSES: Record<string, string> = {
  DEPOSIT:      'meta--deposit',
  WITHDRAWAL:   'meta--withdrawal',
  PURCHASE:     'meta--purchase',
  PRIZE:        'meta--prize',
  TRIVIA_ENTRY: 'meta--trivia-entry',
}

const GAME_GROUP_LABELS: Record<string, string> = {
  BINGO:  'Bingo',
  TRIVIA: 'Trivia',
}

const PRIZE_CHILDREN = [
  { icon: 'fas fa-coins', label: 'Premio digital', colorClass: 'meta--prize', description: 'Acreditado como saldo en tu billetera' },
  { icon: 'fas fa-gift',  label: 'Premio físico',  colorClass: 'meta--prize', description: 'Objeto a entregar, no acredita saldo automáticamente' },
]

export interface MovementMetaEntry {
  icon: string
  label: string
  colorClass: string
  colorStyle?: string
  sign: '+' | '-'
  description: string
}

export interface MovementTypeItem {
  icon: string
  label: string
  colorClass: string
  colorStyle?: string
  sign: '+' | '-'
  description: string
  children?: { icon: string; label: string; colorClass: string; description: string }[]
}

export interface MovementTypeSection {
  group: string | null
  groupLabel: string
  items: MovementTypeItem[]
}

/**
 * Deriva metadata visual (icono, color, signo) y agrupación por juego a
 * partir del catálogo MovementType recibido por props — el premio (type
 * === 'PRIZE') se subdivide visualmente por su naturaleza (digital/físico)
 * vía metadata.prize_type en el backend, no por el juego que lo originó.
 */
export function useMovementMeta(movementTypes: ComputedRef<WalletPageMovementType[]>) {
  const movementMeta = computed<Record<string, MovementMetaEntry>>(() => {
    const map: Record<string, MovementMetaEntry> = {}
    for (const type of movementTypes.value) {
      const colorClass = KNOWN_COLOR_CLASSES[type.code]
      map[type.code] = {
        icon: type.icon,
        label: type.label,
        colorClass: colorClass ?? '',
        colorStyle: colorClass ? undefined : `color: ${type.color}`,
        sign: type.is_credit ? '+' : '-',
        description: type.description ?? '',
      }
    }
    return map
  })

  function movMeta(type: string): MovementMetaEntry {
    return movementMeta.value[type] ?? { icon: 'fas fa-circle', label: type, colorClass: '', sign: '+', description: '' }
  }

  const movementTypeSections = computed<MovementTypeSection[]>(() => {
    const sorted = [...movementTypes.value].sort((a, b) =>
      (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.label.localeCompare(b.label)
    )

    const sections: MovementTypeSection[] = []
    const sectionByGroup = new Map<string | null, MovementTypeSection>()

    for (const type of sorted) {
      const group = type.game_group ?? null
      let section = sectionByGroup.get(group)
      if (!section) {
        section = { group, groupLabel: group ? (GAME_GROUP_LABELS[group] ?? group) : 'General', items: [] }
        sectionByGroup.set(group, section)
        sections.push(section)
      }

      const meta = movMeta(type.code)
      section.items.push({
        icon: meta.icon,
        label: meta.label,
        colorClass: meta.colorClass,
        colorStyle: meta.colorStyle,
        sign: meta.sign,
        description: meta.description,
        children: type.code === 'PRIZE' ? PRIZE_CHILDREN : undefined,
      })
    }

    return sections
  })

  return { movMeta, movementTypeSections }
}
