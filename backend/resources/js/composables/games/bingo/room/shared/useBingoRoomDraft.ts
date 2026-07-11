import { ref } from 'vue'
import type { Ref } from 'vue'
import { useFormDraft } from '@/js/composables/common/useFormDraft'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/** TTL por defecto: salas normales tienen fecha/hora fija que vence rápido. */
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000
/** Salas automáticas no tienen fecha/hora fija — el borrador puede vivir más. */
const AUTOMATIC_TTL_MS = 7 * 24 * 60 * 60 * 1000

/**
 * Persiste el borrador del formulario de sala de bingo en localStorage.
 * Capa fina sobre useFormDraft que añade la detección de "fecha/hora
 * programada vencida" propia de este formulario.
 *
 * Uso:
 *   const { hasDraft, restoreDraft, discardDraft } = useBingoRoomDraft(form, 'bingo-room-create')
 *
 * @param form         Ref reactivo del formulario (v-model completo).
 * @param storageKey   Clave única por contexto (creación vs. edición de sala X).
 * @param automaticRoom true si es una sala de inicio automático (sin fecha/hora fija → TTL más largo).
 * @param debounceMs   Intervalo de escritura a localStorage (default 800 ms).
 */
export function useBingoRoomDraft(
    form: Ref<BingoRoomFormData>,
    storageKey: string,
    automaticRoom = false,
    debounceMs = 800,
) {
    const draftDateExpired = ref(false)

    const { hasDraft, draftSavedAt, draftStatus, peekDraft, isDraftStale, restoreDraft: restoreRaw, discardDraft: discardRaw } =
        useFormDraft(form, storageKey, {
            debounceMs,
            ttlMs: automaticRoom ? AUTOMATIC_TTL_MS : DEFAULT_TTL_MS,
        })

    function restoreDraft(): boolean {
        const data = peekDraft()
        if (data === null) return false
        const { date, time } = data as Partial<BingoRoomFormData>
        draftDateExpired.value = !!(date && time && new Date(`${date}T${time}:00`) < new Date())
        return restoreRaw()
    }

    function discardDraft(): void {
        discardRaw()
        draftDateExpired.value = false
    }

    return { hasDraft, draftSavedAt, draftDateExpired, draftStatus, isDraftStale, restoreDraft, discardDraft }
}
