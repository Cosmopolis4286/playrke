import { ref, Ref } from 'vue'

interface UseExchangeRateReturn {
    loading: Ref<boolean>
    showModalForm: Ref<boolean>
    submit: () => Promise<void>
    create: () => void
    edit: (id: string | number) => void
    changeStatus: (id: string | number) => void
    remove: (id: string | number) => void
    updateExchangeRates: () => Promise<void>
}

/**
 * Composable para manejar operaciones sobre tasas de cambio
 * @param {String} tokenId Token del usuario conectado por API
 * @returns {UseExchangeRateReturn} Funciones y estados para manejar exchange rate
 */
export function useExchangeRate(tokenId: String = ''): UseExchangeRateReturn {
    const loading = ref<boolean>(false)
    const showModalForm = ref<boolean>(false)

    async function submit(): Promise<void> {
        loading.value = true

        try {
            // 🔁 aquí luego usas Inertia o fetch real
            await new Promise(resolve => setTimeout(resolve, 800))

            console.log('Submit exchange rate')

            // cerrar modal solo si todo fue OK
            showModalForm.value = false
        } catch (error) {
            console.error('Error en submit:', error)
        } finally {
            loading.value = false
        }
    }

    /**
     * Crear nueva tasa de cambio
     */
    function create(): void {
        // lógica para crear
        showModalForm.value = true
        console.log('Crear tasa de cambio')
    }

    /**
     * Editar tasa de cambio
     * @param {string|number} id Id cifrado o identificador
     */
    function edit(id: string | number): void {
        console.log('Editar tasa de cambio:', id)
    }

    /**
     * Cambiar status de tasa de cambio
     * @param {string|number} id Id cifrado o identificador
     */
    function changeStatus(id: string | number): void {
        console.log('Cambiar status:', id)
    }

    /**
     * Eliminar tasa de cambio
     * @param {string|number} id Id cifrado o identificador
     */
    function remove(id: string | number): void {
        console.log('Eliminar tasa de cambio:', id)
    }

    /**
     * Actualizar tasas de cambio (fetch desde backend)
     * @returns {Promise<void>}
     */
    async function updateExchangeRates(): Promise<void> {
        loading.value = true
        try {
            const response = await fetch('/exchange-rate/getExchangeRates')
            const data = await response.json()
            alert(data.message)
            if (data.type === 'success') window.location.reload()
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
