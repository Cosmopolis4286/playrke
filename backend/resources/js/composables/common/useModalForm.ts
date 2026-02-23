import { ref, computed, watch, Ref, ComputedRef } from 'vue'

/**
 * API mínima que el formulario debe exponer
 */
export interface ModalFormExpose {
    submit: () => boolean
    isValid: boolean
    isActive?: boolean
    reset?: () => void
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
        if (!formRef.value?.submit()) return false
        const response = await submitFn()
        if (response) showModalForm.value = false
        return response
    }

    /**
     * Reset automático al cerrar el modal
     */
    watch(showModalForm, open => {
        if (!open) {
            formRef.value?.reset?.()
        }
    })

    return {
        showModalForm,
        modalTitle,
        formMode,
        formRef,
        handleSubmit,
    }
}
