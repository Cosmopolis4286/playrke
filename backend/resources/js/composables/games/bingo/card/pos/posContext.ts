import { inject, provide } from 'vue'
import type { InjectionKey, UnwrapNestedRefs } from 'vue'

import type { usePosRooms } from './usePosRooms'
import type { usePosSale } from './usePosSale'
import type { usePosTodaySales } from './usePosTodaySales'

/**
 * Estado compartido del punto de venta. BingoCardPos.vue crea los
 * composables (envueltos en reactive()) y los provee; cada paso los inyecta
 * en vez de recibir decenas de props/emits.
 */
export interface PosContext {
    rooms: UnwrapNestedRefs<ReturnType<typeof usePosRooms>>
    sale: UnwrapNestedRefs<ReturnType<typeof usePosSale>>
    todaySales: UnwrapNestedRefs<ReturnType<typeof usePosTodaySales>>
    /** Abre el modal de confirmación de la venta. */
    requestConfirm: () => void
}

const POS_KEY: InjectionKey<PosContext> = Symbol('bingo-pos')

export function providePos(ctx: PosContext): void {
    provide(POS_KEY, ctx)
}

export function usePos(): PosContext {
    const ctx = inject(POS_KEY)
    if (!ctx) throw new Error('usePos() debe usarse dentro de BingoCardPos.vue')
    return ctx
}
