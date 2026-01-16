import { ref, Ref } from 'vue'

interface Creator {
  type: string
  id: string | number
  // otros campos que pueda tener creator si los necesitas
}

interface UseExchangeRateReturn {
  loading: Ref<boolean>
  create: () => void
  edit: (id: string | number) => void
  changeStatus: (id: string | number) => void
  remove: (id: string | number) => void
  updateExchangeRates: () => Promise<void>
}

/**
 * Composable para manejar operaciones sobre tasas de cambio
 * @param {Creator} creator El creador (customer o user)
 * @returns {UseExchangeRateReturn} Funciones y estados para manejar exchange rate
 */
export function useExchangeRate(creator: Creator): UseExchangeRateReturn {
  const loading = ref<boolean>(false)

  /**
   * Crear nueva tasa de cambio
   */
  function create(): void {
    // lógica para crear
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
    create,
    edit,
    changeStatus,
    remove,
    updateExchangeRates
  }
}
