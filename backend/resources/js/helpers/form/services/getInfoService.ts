// helpers/form/services/getInfoService.ts
import axios from '@/plugins/axios'
import { formCrudState as crud } from '../state/formCrudState'
import { route } from '@/routes/helper'
import { showToast } from '@/helpers/toastNotify'

export async function getInfo(encryptId: string): Promise<void> {
  if (!encryptId) return

  const url = route(crud.routes, 'show', { id: encryptId })

  try {
    const { data } = await axios.get(url)

    // Validar respuesta exitosa según tu API
    if (data?.success === false) {
      // Manejar error específico de API
      console.error('Error al cargar info:', data.message)
      showToast(data.message, 'error')
      // Podés lanzar error o mostrar notificación
      throw new Error(data.message || 'Error desconocido al cargar info')
    }

    console.log('Datos obtenidos:', data)

    if (data?.data) {
      crud.insertInfo?.('edit', data.data)
      $(crud.modalId!).modal('show')
    } else {
      // No hay datos pero no es error HTTP
      console.warn('No hay datos disponibles para el ID:', encryptId)
    }
  } catch (error: any) {
    // Captura errores HTTP o de red
    console.error('Error en getInfo:', error.message || error)
    showToast('Error en getInfo!', 'error')
  }
}
