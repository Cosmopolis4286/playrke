import { ref, computed, watch, Ref } from 'vue'

/**
 * API mínima que el formulario debe exponer
 */
export interface ModalFormExpose {
    submit: () => boolean
    isValid: boolean
    reset?: () => void
}

/**
 * Contrato del composable useModalForm
 */
interface UseModalFormReturn {
    showModalForm: Ref<boolean>
    modalTitle: Ref<string>
    formMode?: Ref<'create' | 'edit'>
    formRef: Ref<ModalFormExpose | null>
    handleSubmit: () => Promise<boolean>
}

/**
 * Maneja el ciclo de vida del modal con formulario
 *
 * Responsabilidades:
 * - Submit orquestado (form → backend)
 * - Validación previa
 * - Reset automático al cerrar
 * - Determinar modo create / edit
 * - Resolver título del modal (UI)
 *
 * ⚠️ No conoce backend ni datos del formulario
 *
 * @param exchangeRateData Ref reactivo del formulario
 * @param submitFn Función submit del composable de negocio
 */
export function useModalForm(
    exchangeRateData: Ref<Record<string, any>>,
    submitFn: () => Promise<boolean>
): UseModalFormReturn {
    /** Controla la visibilidad del modal */
    const showModalForm = ref(false)

    /** Referencia al formulario */
    const formRef = ref<ModalFormExpose | null>(null)

    /**
     * Modo actual del formulario
     * Se deriva del estado del modelo (create / edit)
     */
    const formMode = computed<'create' | 'edit'>(() =>
        exchangeRateData.value?.id ? 'edit' : 'create'
    )

    /**
     * Título del modal
     * Responsabilidad de UI, derivado del modo actual
     */
    const modalTitle = computed(() =>
        formMode.value === 'create'
            ? 'Registrar Tasa de Cambio'
            : 'Editar Tasa de Cambio'
    )

    /**
     * Submit del modal
     * 1. Valida formulario
     * 2. Ejecuta submit del backend
     */
    async function handleSubmit(): Promise<boolean> {
        if (!formRef.value?.submit()) return false
        return await submitFn()
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
        formRef,
        handleSubmit
    }
}
