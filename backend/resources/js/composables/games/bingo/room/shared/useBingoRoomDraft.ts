import { watch, onMounted, onUnmounted, ref } from 'vue'
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
    const draftStatus = ref<'idle' | 'saving' | 'saved'>('idle')
    let savedTimer: ReturnType<typeof setTimeout> | null = null

    /**
     * Snapshot de los datos con los que se abrió el formulario (sala recién
     * cargada para editar, o formulario vacío en creación). Se usa para
     * distinguir un borrador real (cambios que el usuario hizo y no guardó)
     * de un draft huérfano que coincide con los datos originales — sin esto,
     * reabrir una sala sin haberla tocado mostraría "hay un borrador" solo
     * porque un cierre anterior del modal dejó ese snapshot en localStorage.
     */
    const initialSnapshot = JSON.stringify(form.value)

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
            if (JSON.stringify(envelope.data) === initialSnapshot) {
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
        draftStatus.value = 'saving'
        // Cancelar el timer de reset-a-idle para no sobreescribir 'saving' con 'idle'
        if (savedTimer) clearTimeout(savedTimer)
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
            try {
                const envelope: DraftEnvelope = {
                    v:       DRAFT_VERSION,
                    savedAt: new Date().toISOString(),
                    data:    value,
                }
                localStorage.setItem(storageKey, JSON.stringify(envelope))
                draftSavedAt.value  = envelope.savedAt
                draftStatus.value   = 'saved'
                // Vuelve a 'idle' después de 3s para que el indicador no quede fijo
                if (savedTimer) clearTimeout(savedTimer)
                savedTimer = setTimeout(() => { draftStatus.value = 'idle' }, 3000)
            } catch {
                draftStatus.value = 'idle'
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

    onUnmounted(() => {
        if (timer)      clearTimeout(timer)
        if (savedTimer) clearTimeout(savedTimer)
    })

    return { hasDraft, draftSavedAt, draftStatus, restoreDraft, discardDraft }
}
