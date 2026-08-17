import { ref } from 'vue'
import type { Ref } from 'vue'
import { useEntityDraft } from '@/js/composables/common/useFormDraft'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/** TTL por defecto: salas normales tienen fecha/hora fija que vence rápido. */
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000
/** Salas automáticas no tienen fecha/hora fija — el borrador puede vivir más. */
const AUTOMATIC_TTL_MS = 7 * 24 * 60 * 60 * 1000

/**
 * Versión del shape de BingoRoomFormData para el sistema de borradores.
 * Súbela cuando el form gane/pierda/renombre campos de forma incompatible —
 * los drafts guardados con una versión distinta se descartan solos en vez de
 * restaurar datos con shape viejo (ver historial: gameMode → connectionType,
 * luego suma de accessCode/roomPassword/hostUserId).
 */
const SCHEMA_VERSION = 2

/**
 * Persiste el borrador del formulario de sala de bingo en localStorage.
 * Capa fina sobre useEntityDraft que añade:
 * - la clave de storage derivada automáticamente (entidad + scope + id)
 * - detección de "fecha/hora programada vencida" al restaurar
 * - saneo de roundsConfig[].bingoPattern contra el catálogo vigente, para no
 *   restaurar un patrón que ya no existe (ej. fue eliminado/renombrado)
 *
 * Uso:
 *   const { hasDraft, restoreDraft, discardDraft } = useBingoRoomDraft(form, {
 *     scope: automaticRoom ? 'automatic' : 'default',
 *     id: editingRoomId ?? 'create',
 *     automaticRoom,
 *     validBingoPatternIds: bingoPatternOptions.map(o => String(o.value)),
 *   })
 *
 * @param form           Ref reactivo del formulario (v-model completo).
 * @param opts.scope     Sub-variante del formulario (ej. 'default' vs 'automatic').
 * @param opts.id        ID de la sala en edición, o 'create' en modo creación.
 * @param opts.automaticRoom true si es una sala de inicio automático (sin fecha/hora fija → TTL más largo).
 * @param opts.validBingoPatternIds IDs de patrones vigentes (bingoPatternOptions) — si se pasa, se usa para limpiar referencias muertas al restaurar.
 * @param opts.debounceMs Intervalo de escritura a localStorage (default 800 ms).
 */
export function useBingoRoomDraft(
    form: Ref<BingoRoomFormData>,
    opts: {
        scope: 'default' | 'automatic'
        id: string | number
        automaticRoom?: boolean
        validBingoPatternIds?: string[]
        debounceMs?: number
        /** Ver UseFormDraftOptions.confirmOverwrite — se reenvía tal cual. */
        confirmOverwrite?: () => Promise<boolean>
    },
) {
    const { scope, id, automaticRoom = false, validBingoPatternIds, debounceMs = 800, confirmOverwrite } = opts
    const draftDateExpired = ref(false)

    const sanitize = (data: BingoRoomFormData): BingoRoomFormData => {
        if (!validBingoPatternIds || !data.roundsConfig?.length) return data
        const valid = new Set(validBingoPatternIds)
        return {
            ...data,
            roundsConfig: data.roundsConfig.map(round =>
                round.bingoPattern != null && !valid.has(String(round.bingoPattern))
                    ? { ...round, bingoPattern: null }
                    : round
            ),
        }
    }

    const { hasDraft, draftSavedAt, draftStatus, peekDraft, isDraftStale, restoreDraft: restoreRaw, discardDraft: discardRaw, flush } =
        useEntityDraft(form, { entity: 'bingo-room', scope, id }, {
            debounceMs,
            ttlMs: automaticRoom ? AUTOMATIC_TTL_MS : DEFAULT_TTL_MS,
            version: SCHEMA_VERSION,
            sanitize,
            confirmOverwrite,
        })

    function restoreDraft(): boolean {
        const data = peekDraft()
        if (data === null) return false
        const { date, time } = data
        draftDateExpired.value = !!(date && time && new Date(`${date}T${time}:00`) < new Date())
        return restoreRaw()
    }

    function discardDraft(): void {
        discardRaw()
        draftDateExpired.value = false
    }

    return { hasDraft, draftSavedAt, draftDateExpired, draftStatus, isDraftStale, restoreDraft, discardDraft, flush }
}
