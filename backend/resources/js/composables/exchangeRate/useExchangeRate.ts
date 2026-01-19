import { ref, Ref } from 'vue'

/**
 * Contrato público del composable useExchangeRate
 */
interface UseExchangeRateReturn {
    /** Estado global de carga (submit, fetch, etc.) */
    loading: Ref<boolean>

    /** Controla la visualización del modal */
    showModalForm: Ref<boolean>

    /**
     * Envía el formulario de tasa de cambio.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => void

    /** Cambia el estado de una tasa */
    changeStatus: (id: string | number) => void

    /** Elimina una tasa */
    remove: (id: string | number) => void

    /** Sincroniza tasas desde backend */
    updateExchangeRates: () => Promise<void>
}

/**
 * Composable para manejar operaciones sobre tasas de cambio.
 *
 * Responsabilidades:
 * - Control de estado del modal
 * - Submit del formulario
 * - Operaciones CRUD
 * - Estados de carga
 *
 * ⚠️ No controla UI directamente (el modal decide cerrar)
 *
 * @param tokenId Token del usuario autenticado (opcional)
 * @returns {UseExchangeRateReturn}
 */
export function useExchangeRate(tokenId: string = ''): UseExchangeRateReturn {
    const loading = ref<boolean>(false)
    const showModalForm = ref<boolean>(false)

    /**
     * Envía el formulario de tasa de cambio.
     *
     * @returns {Promise<boolean>}
     * - true  → operación exitosa (cerrar modal)
     * - false → error (mantener modal abierto)
     */
    async function submit(): Promise<boolean> {
        loading.value = true

        try {
            // 🔁 Simulación de request (reemplazar por Inertia / fetch real)
            await new Promise(resolve => setTimeout(resolve, 800))

            console.log('Submit exchange rate')

            return true
        } catch (error) {
            console.error('Error en submit:', error)
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Abre el modal para crear una nueva tasa
     */
    function create(): void {
        showModalForm.value = true
    }

    /**
     * Abre el modal para editar una tasa existente
     *
     * @param id Identificador de la tasa
     */
    function edit(id: string | number): void {
        console.log('Editar tasa de cambio:', id)
        showModalForm.value = true
    }

    /**
     * Cambia el estado (activo/inactivo) de una tasa
     *
     * @param id Identificador de la tasa
     */
    function changeStatus(id: string | number): void {
        console.log('Cambiar status:', id)
    }

    /**
     * Elimina una tasa de cambio
     *
     * @param id Identificador de la tasa
     */
    function remove(id: string | number): void {
        console.log('Eliminar tasa de cambio:', id)
    }

    /**
     * Actualiza tasas de cambio desde backend
     */
    async function updateExchangeRates(): Promise<void> {
        loading.value = true

        try {
            const response = await fetch('/exchange-rate/getExchangeRates')
            const data = await response.json()

            alert(data.message)

            if (data.type === 'success') {
                window.location.reload()
            }
        } catch (error) {
            console.error('Error actualizando tasas:', error)
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        showModalForm,
        submit,
        create,
        edit,
        changeStatus,
        remove,
        updateExchangeRates
    }
}
