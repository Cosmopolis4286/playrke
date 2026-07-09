import { watch, onMounted, ref } from 'vue'
import type { Ref } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

const DRAFT_VERSION = 1

interface DraftEnvelope {
    v:       number
    savedAt: string
    data:    BingoRoomFormData
}

/**
 * Persiste el borrador del formulario de sala en localStorage.
 *
 * Uso:
 *   const { hasDraft, restoreDraft, discardDraft } = useBingoRoomDraft(form, 'bingo-room-create')
 *
 * @param form       Ref reactivo del formulario (v-model completo).
 * @param storageKey Clave única por contexto (creación vs. edición de sala X).
 * @param debounceMs Intervalo de escritura a localStorage (default 800 ms).
 */
export function useBingoRoomDraft(
    form: Ref<BingoRoomFormData>,
    storageKey: string,
    debounceMs = 800,
) {
    const hasDraft   = ref(false)
    const draftSavedAt = ref<string | null>(null)

    let timer: ReturnType<typeof setTimeout> | null = null

    /* ── Leer borrador al montar ── */
    onMounted(() => {
        try {
            const raw = localStorage.getItem(storageKey)
            if (!raw) return
            const envelope: DraftEnvelope = JSON.parse(raw)
            if (envelope.v !== DRAFT_VERSION) {
                localStorage.removeItem(storageKey)
                return
            }
            hasDraft.value    = true
            draftSavedAt.value = envelope.savedAt
        } catch {
            localStorage.removeItem(storageKey)
        }
    })

    /* ── Guardar con debounce en cada cambio del formulario ── */
    watch(form, (value) => {
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
            try {
                const envelope: DraftEnvelope = {
                    v:       DRAFT_VERSION,
                    savedAt: new Date().toISOString(),
                    data:    value,
                }
                localStorage.setItem(storageKey, JSON.stringify(envelope))
                draftSavedAt.value = envelope.savedAt
            } catch {
                // quota exceeded — ignorar silenciosamente
            }
        }, debounceMs)
    }, { deep: true })

    function restoreDraft(): boolean {
        try {
            const raw = localStorage.getItem(storageKey)
            if (!raw) return false
            const envelope: DraftEnvelope = JSON.parse(raw)
            if (envelope.v !== DRAFT_VERSION) return false
            form.value = envelope.data
            hasDraft.value = false
            return true
        } catch {
            return false
        }
    }

    function discardDraft(): void {
        localStorage.removeItem(storageKey)
        hasDraft.value     = false
        draftSavedAt.value = null
    }

    return { hasDraft, draftSavedAt, restoreDraft, discardDraft }
}
