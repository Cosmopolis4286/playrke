import { watch, onMounted, onUnmounted, ref } from 'vue'
import type { Ref } from 'vue'

interface DraftEnvelope<T> {
    v:       number
    savedAt: string
    data:    T
}

export interface UseFormDraftOptions<T> {
    /** Intervalo de escritura a localStorage tras el último cambio (default 800 ms). */
    debounceMs?: number
    /** Borradores más viejos que esto se descartan solos en vez de ofrecerse para restaurar (default 24h). */
    ttlMs?: number
    /**
     * Versión del esquema de `T` para este formulario. Súbela cuando cambies
     * de forma incompatible los campos del form (rename, campo requerido
     * nuevo, etc.) — los drafts guardados con una versión distinta se
     * descartan solos en vez de restaurar datos con shape viejo.
     * Default 1. Independiente entre distintos `storageKey`.
     */
    version?: number
    /**
     * Se aplica al draft leído (al detectarlo Y al restaurarlo) antes de
     * usarlo. Útil para reparar/completar campos que pudieron quedar
     * inconsistentes (ej. una FK que ya no existe) sin invalidar todo el
     * borrador. Debe devolver un objeto del mismo shape que `T`.
     */
    sanitize?: (data: T) => T
    /**
     * Se invoca la primera vez que el usuario edita el form mientras hay un
     * borrador guardado sin restaurar ni descartar (`hasDraft` true). Debe
     * resolver a `true` para permitir que el autosave arranque (y por lo
     * tanto empiece a sobrescribir ese borrador), o `false` para revertir el
     * cambio y dejar el borrador intacto. Si no se pasa, el autosave arranca
     * sin preguntar (comportamiento previo).
     */
    confirmOverwrite?: () => Promise<boolean>
}

/**
 * Persiste el borrador de un formulario en localStorage mientras el usuario
 * lo edita, para no perder el trabajo si el modal/página se cierra por
 * accidente. Genérico sobre el tipo de datos del formulario.
 *
 * Uso directo:
 *   const { hasDraft, restoreDraft, discardDraft } = useFormDraft(form, 'my-form-create')
 *
 * Para no armar `storageKey` a mano en cada formulario, ver `useEntityDraft`
 * en este mismo archivo — genera la clave a partir de (entidad, scope, id).
 *
 * @param form       Ref reactivo del formulario (v-model completo).
 * @param storageKey Clave única por contexto (creación vs. edición de la entidad X).
 */
export function useFormDraft<T>(
    form: Ref<T>,
    storageKey: string,
    options: UseFormDraftOptions<T> = {},
) {
    const { debounceMs = 800, ttlMs = 24 * 60 * 60 * 1000, version = 1, sanitize, confirmOverwrite } = options

    const hasDraft   = ref(false)
    const draftSavedAt = ref<string | null>(null)

    let timer: ReturnType<typeof setTimeout> | null = null
    const draftStatus = ref<'idle' | 'saving' | 'saved'>('idle')
    let savedTimer: ReturnType<typeof setTimeout> | null = null

    /** Último valor visto por el watcher — permite un flush inmediato (sin esperar el debounce) al desmontar. */
    let pendingValue: T | null = null

    /** true mientras se espera la respuesta de confirmOverwrite() — evita reabrir el diálogo por cada tecla mientras el primero sigue en pantalla. */
    let confirmingOverwrite = false
    /** true durante el único tick en que se reescribe form.value de vuelta al estado previo tras un "Cancelar" — evita que ese propio revert dispare de nuevo el diálogo. */
    let revertingOverwrite = false

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

    function readEnvelope(): DraftEnvelope<T> | null {
        try {
            const raw = localStorage.getItem(storageKey)
            if (!raw) return null
            const envelope: DraftEnvelope<T> = JSON.parse(raw)
            if (envelope.v !== version) {
                localStorage.removeItem(storageKey)
                return null
            }
            const savedAtMs = Date.parse(envelope.savedAt)
            if (!Number.isNaN(savedAtMs) && Date.now() - savedAtMs > ttlMs) {
                localStorage.removeItem(storageKey)
                return null
            }
            return envelope
        } catch {
            localStorage.removeItem(storageKey)
            return null
        }
    }

    function checkDraft(): void {
        const envelope = readEnvelope()
        if (!envelope) return
        if (JSON.stringify(envelope.data) === initialSnapshot) {
            localStorage.removeItem(storageKey)
            return
        }
        hasDraft.value    = true
        draftSavedAt.value = envelope.savedAt
    }

    function persist(value: T): void {
        try {
            const envelope: DraftEnvelope<T> = {
                v:       version,
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
        pendingValue = null
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
     * pagar dos recorridos deep de Vue por cada cambio del formulario.
     *
     * BUG previo: el formulario suele traer watchers propios con
     * `immediate: true` (ej. sincronizar roundsConfig según la cantidad de
     * rondas al abrir) que mutan `form` DURANTE el mismo setup(), antes de
     * que onMounted() fije snapshotLocked. Ese cambio "automático" (no hecho
     * por el usuario) disparaba este watcher con snapshotLocked aún false:
     * el snapshot se actualizaba correctamente (bien), pero el autosave de
     * abajo IGUAL se armaba con ese valor — persistiendo a los pocos ms un
     * "borrador" que en realidad era solo la sincronización inicial del
     * formulario, no una edición real. Resultado: abrir y cerrar el modal
     * sin tocar nada dejaba un draft guardado, que la próxima apertura
     * detectaba como "borrador real" sin serlo.
     * Fix: si esta pasada fue solo para fijar el snapshot inicial (aún no
     * montado), no arrancar el autosave — cortar acá mismo. ── */
    watch(form, (value, oldValue) => {
        if (!snapshotLocked) {
            initialSnapshot = JSON.stringify(value)
            checkDraft()
            return
        }

        if (revertingOverwrite) {
            revertingOverwrite = false
            return
        }

        // Hay un borrador guardado sin restaurar ni descartar y el usuario
        // está editando en vivo (no vía restoreDraft, que ya puso hasDraft en
        // false antes de que este watcher reaccione): sin este guard, el
        // autosave de abajo persistiría el nuevo valor a los pocos ms y
        // pisaría en silencio el borrador que el usuario todavía no vio ni
        // decidió qué hacer con él — el escenario reportado: "no restauré el
        // borrador que quería, edité sobre una plantilla limpia, y se borró".
        if (hasDraft.value && confirmOverwrite && !confirmingOverwrite) {
            confirmingOverwrite = true
            // Copia estructurada del valor previo: no confiar en la identidad de
            // `oldValue` (con watchers deep, si algo mutara `form.value` in-place
            // en vez de reemplazarlo, Vue podría entregar la misma referencia que
            // `value` y el revert de abajo no restauraría nada).
            const reverted: T = JSON.parse(JSON.stringify(oldValue))
            confirmOverwrite().then((proceed) => {
                confirmingOverwrite = false
                if (proceed) {
                    hasDraft.value = false
                    armAutosave(value)
                } else {
                    revertingOverwrite = true
                    form.value = reverted
                }
            })
            return
        }
        if (confirmingOverwrite) return

        armAutosave(value)
    }, { deep: true })

    function armAutosave(value: T): void {
        draftStatus.value = 'saving'
        pendingValue = value
        // Cancelar el timer de reset-a-idle para no sobreescribir 'saving' con 'idle'
        if (savedTimer) clearTimeout(savedTimer)
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => persist(value), debounceMs)
    }

    function peekDraft(): T | null {
        const envelope = readEnvelope()
        if (!envelope) return null
        return sanitize ? sanitize(envelope.data) : envelope.data
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

    /** Fuerza el guardado inmediato del último valor pendiente, sin esperar el debounce (usado al desmontar/cerrar). */
    function flush(): void {
        if (timer) { clearTimeout(timer); timer = null }
        if (pendingValue !== null) persist(pendingValue)
    }

    onUnmounted(() => {
        // El formulario se está cerrando (ej. modal destruido): un cambio
        // reciente que aún no cruzó el debounce no debe perderse.
        flush()
        if (savedTimer) clearTimeout(savedTimer)
    })

    return { hasDraft, draftSavedAt, draftStatus, peekDraft, isDraftStale, restoreDraft, discardDraft, flush }
}

/**
 * Azúcar sobre `useFormDraft` para no armar `storageKey` a mano en cada
 * formulario del proyecto: la clave se deriva de (entidad, scope, id).
 *
 * Uso:
 *   useEntityDraft(form, { entity: 'bingo-room', scope: 'default', id: editingRoomId ?? 'create' })
 *   useEntityDraft(form, { entity: 'directory-room', id: room?.id ?? 'create' })
 *
 * @param entity Nombre corto y estable de la entidad/formulario (ej. 'bingo-room', 'currency', 'directory-room').
 * @param scope  Sub-variante opcional dentro de la misma entidad (ej. 'default' vs 'automatic'). Omitir si no aplica.
 * @param id     Identificador del registro en edición, o 'create' en modo creación. Debe ser estable entre aperturas del mismo formulario.
 */
export function useEntityDraft<T>(
    form: Ref<T>,
    { entity, scope, id }: { entity: string; scope?: string; id: string | number },
    options: UseFormDraftOptions<T> = {},
) {
    const storageKey = ['form-draft', entity, scope, String(id)].filter(Boolean).join(':')
    return useFormDraft(form, storageKey, options)
}
