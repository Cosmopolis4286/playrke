import { ref, computed, watch, Ref, ComputedRef } from 'vue'

/**
 * API mínima que el formulario debe exponer
 */
export interface ModalFormExpose {
    submit: () => boolean
    isValid: boolean
    isActive?: boolean
    reset?: () => void
    /** IDs de monedas seleccionadas sin tasa de cambio activa (formularios que lo soporten, ej. BingoRoomForm). */
    currenciesMissingRate?: string[]
    /** Quita del formulario las monedas sin tasa activa (ver currenciesMissingRate). */
    removeCurrenciesMissingRate?: () => void
    /** Estado de guardado del borrador local (formularios que lo soporten, ej. BingoRoomForm). */
    draftStatus?: 'idle' | 'saving' | 'saved'
    /** Descarta el borrador local (formularios que lo soporten, ej. BingoRoomForm) — llamar solo tras un submit confirmado por el backend. */
    discardDraft?: () => void
    /** Paso activo del wizard (formularios paginados por pasos, ej. BingoRoomForm). */
    step?: string
    canGoPrev?: boolean
    canGoNext?: boolean
    goPrev?: () => void
    goNext?: () => void
}

/**
 * Opciones de configuración para el modal
 */
export interface ModalFormOptions {
    /** Título base por defecto */
    defaultTitle: string
    /** Título para modo creación */
    createTitle?: string
    /** Título para modo edición */
    editTitle?: string
}

/**
 * Contrato del composable useModalForm
 */
interface UseModalFormReturn {
    showModalForm: Ref<boolean>
    modalTitle: ComputedRef<string>
    formMode: ComputedRef<'create' | 'edit'>
    formRef: Ref<ModalFormExpose | null>
    isSubmitting: Ref<boolean>
    handleSubmit: () => Promise<boolean>
}

/**
 * Maneja el ciclo de vida del modal con formulario
 */
export function useModalForm<T extends { id?: number | string }>(
    formData: Ref<T>,
    submitFn: () => Promise<boolean>,
    options: ModalFormOptions
): UseModalFormReturn {

    const {
        defaultTitle,
        createTitle = `Registrar ${defaultTitle}`,
        editTitle = `Editar ${defaultTitle}`,
    } = options

    /** Controla la visibilidad del modal */
    const showModalForm = ref(false)

    /** Referencia al formulario */
    const formRef = ref<ModalFormExpose | null>(null)

    /** Indica si hay una petición en curso */
    const isSubmitting = ref(false)

    /**
     * Modo actual del formulario
     */
    const formMode = computed<'create' | 'edit'>(() =>
        formData.value?.id ? 'edit' : 'create'
    )

    /**
     * Título del modal
     */
    const modalTitle = computed(() =>
        formMode.value === 'create' ? createTitle : editTitle
    )

    /**
     * Submit del modal
     */
    async function handleSubmit(): Promise<boolean> {
        if (isSubmitting.value) return false
        if (!formRef.value?.submit()) return false
        isSubmitting.value = true
        try {
            const response = await submitFn()
            if (response) {
                // Recién aquí el backend confirmó el guardado — antes de esto el
                // borrador debe seguir vivo por si el submit falla (validación de
                // servidor, red) y el usuario necesita reintentar sin perder los datos.
                formRef.value?.discardDraft?.()
                showModalForm.value = false
            }
            return response
        } finally {
            isSubmitting.value = false
        }
    }

    return {
        showModalForm,
        modalTitle,
        formMode,
        formRef,
        isSubmitting,
        handleSubmit,
    }
}
