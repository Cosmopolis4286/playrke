import { watch, onMounted, onUnmounted, ref } from 'vue'
import type { Ref } from 'vue'

const DRAFT_VERSION = 1

interface DraftEnvelope<T> {
    v:       number
    savedAt: string
    data:    T
}

export interface UseFormDraftOptions {
    /** Intervalo de escritura a localStorage tras el último cambio (default 800 ms). */
    debounceMs?: number
    /** Borradores más viejos que esto se descartan solos en vez de ofrecerse para restaurar (default 24h). */
    ttlMs?: number
}

/**
 * Persiste el borrador de un formulario en localStorage mientras el usuario
 * lo edita, para no perder el trabajo si el modal/página se cierra por
 * accidente. Genérico sobre el tipo de datos del formulario.
 *
 * Uso:
 *   const { hasDraft, restoreDraft, discardDraft } = useFormDraft(form, 'my-form-create')
 *
 * @param form       Ref reactivo del formulario (v-model completo).
 * @param storageKey Clave única por contexto (creación vs. edición de la entidad X).
 */
export function useFormDraft<T>(
    form: Ref<T>,
    storageKey: string,
    options: UseFormDraftOptions = {},
) {
    const { debounceMs = 800, ttlMs = 24 * 60 * 60 * 1000 } = options

    const hasDraft   = ref(false)
    const draftSavedAt = ref<string | null>(null)

    let timer: ReturnType<typeof setTimeout> | null = null
    const draftStatus = ref<'idle' | 'saving' | 'saved'>('idle')
    let savedTimer: ReturnType<typeof setTimeout> | null = null

    /**
     * Snapshot de los datos con los que se abrió el formulario. Se usa para
     * distinguir un borrador real (cambios que el usuario hizo y no guardó)
     * de un draft huérfano que coincide con los datos originales — sin esto,
     * reabrir sin haber tocado nada mostraría "hay un borrador" solo porque
     * un cierre anterior dejó ese snapshot en localStorage.
     *
     * Se recalcula mientras no hay draft detectado todavía: si el valor
     * inicial llega o cambia de forma asíncrona (ej. un fetch termina
     * después del montaje), el snapshot "original" debe seguir esa
     * actualización en vez de quedar congelado con el valor vacío/parcial
     * del primer render.
     */
    let initialSnapshot = JSON.stringify(form.value)
    let snapshotLocked = false

    function checkDraft(): void {
        try {
            const raw = localStorage.getItem(storageKey)
            if (!raw) return
            const envelope: DraftEnvelope<T> = JSON.parse(raw)
            if (envelope.v !== DRAFT_VERSION) {
                localStorage.removeItem(storageKey)
                return
            }
            const savedAtMs = Date.parse(envelope.savedAt)
            if (!Number.isNaN(savedAtMs) && Date.now() - savedAtMs > ttlMs) {
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
    }

    /* ── Leer borrador al montar ── */
    onMounted(() => {
        checkDraft()
        snapshotLocked = true
    })

    /* ── Único deep watch sobre el form: mientras el snapshot no está fijado
     * (antes del primer onMounted) sigue al valor inicial para no confundir
     * datos que llegan async con cambios del usuario; en todo momento además
     * dispara el autosave con debounce. Fusionado en un solo watcher para no
     * pagar dos recorridos deep de Vue por cada cambio del formulario. ── */
    watch(form, (value) => {
        if (!snapshotLocked) {
            initialSnapshot = JSON.stringify(value)
            checkDraft()
        }

        draftStatus.value = 'saving'
        // Cancelar el timer de reset-a-idle para no sobreescribir 'saving' con 'idle'
        if (savedTimer) clearTimeout(savedTimer)
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
            try {
                const envelope: DraftEnvelope<T> = {
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

    function peekDraft(): T | null {
        try {
            const raw = localStorage.getItem(storageKey)
            if (!raw) return null
            const envelope: DraftEnvelope<T> = JSON.parse(raw)
            if (envelope.v !== DRAFT_VERSION) return null
            return envelope.data
        } catch {
            return null
        }
    }

    /** true si el form actual ya no difiere del borrador guardado — no hay nada que perder al descartar. */
    function isDraftStale(): boolean {
        const data = peekDraft()
        if (data === null) return true
        return JSON.stringify(data) === JSON.stringify(form.value)
    }

    function restoreDraft(): boolean {
        const data = peekDraft()
        if (data === null) return false
        form.value = data
        hasDraft.value = false
        return true
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

    return { hasDraft, draftSavedAt, draftStatus, peekDraft, isDraftStale, restoreDraft, discardDraft }
}
