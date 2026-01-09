// services/crudOtherCasesService.ts
import axios from '@/plugins/axios'
import type { SweetAlertIcon } from 'sweetalert2'
import { formCrudState as crud } from '../state/formCrudState'
import { showToast, showToastConfirmAction } from '../ui/crudModalUI'
import type { OtherCase } from '@/helpers/module/types/formModule.types'
import { getSelectedIds } from '@/alpine/tableSelectionListener'
import { showPasswordModal } from '@/helpers/user/password'
import { route } from '@/routes/helper'

/**
 * Resuelve el caso de acción personalizado o global
 */
function resolveCase(action: string, status?: string, entityName = 'registro'): OtherCase | null {
  const globalActions: Record<string, OtherCase> = {
    delete: {
      routeName: 'delete',
      text: `¿Deseas eliminar este ${entityName}?`,
      icon: 'question',
      confirmButtonText: 'Sí, eliminar',
      method: 'PUT'
    },
    destroy: {
      routeName: 'destroy',
      text: `¿Deseas eliminar este ${entityName}? Esta acción es irreversible.`,
      icon: 'question',
      confirmButtonText: 'Sí, eliminar',
      method: 'DELETE'
    },
    changeStatus: {
      routeName: 'changeStatus',
      text: status
        ? `¿Deseas cambiar el estado a "${status}" de este ${entityName}?`
        : `¿Deseas cambiar el estado de este ${entityName}?`,
      icon: 'question',
      confirmButtonText: 'Sí, cambiar',
      method: 'PUT'
    },
    resetPassword: {
      routeName: 'resetPassword',
      text: `¿Deseas generar una nueva contraseña para este ${entityName}?`,
      icon: 'warning',
      confirmButtonText: 'Sí, generar',
      method: 'PUT'
    }
  }

  if (!globalActions[action] && !crud.otherCasesMap[action]) return null

  return {
    ...globalActions[action],
    ...crud.otherCasesMap[action]
  }
}

/**
 * Obtiene los IDs a procesar: seleccionados en tabla o ID individual
 */
function resolveIds(encryptId: string, elem?: HTMLElement): string[] {
  const tableId = elem?.closest('[data-table-id]')?.getAttribute('data-table-id')
  const selectedIds = tableId ? getSelectedIds(tableId) : []
  if (selectedIds.length > 0) return selectedIds
  if (encryptId) return [encryptId]
  return []
}

/**
 * Muestra confirmación al usuario
 */
async function confirmAction(
  text: string,
  icon: SweetAlertIcon,
  confirmButtonText: string
): Promise<boolean> {
  return await showToastConfirmAction(text, icon, confirmButtonText)
}

/**
 * Envía las peticiones axios para cada ID
 */
async function sendRequests(ids: string[], caseData: OtherCase, status?: string): Promise<any[]> {
  const requestData: Record<string, any> = {}
  if (status) requestData.status = status

  const promises = ids.map(id => {
    const fullUrl = route(crud.routes, caseData.routeName, { id }) ?? ''
    const url = fullUrl.replace(/^https?:\/\/[^\/]+/, '')

    return axios.request({
      method: (caseData.method ?? 'PUT').toLowerCase() as
        | 'get'
        | 'post'
        | 'put'
        | 'delete'
        | 'patch',
      url,
      data: requestData
    })
  })

  return Promise.all(promises)
}

/**
 * Muestra resultados según respuesta
 */
async function handleResult(action: string, ids: string[], results: any[]): Promise<void> {
  const passwordResponse = results.find((res: any) => res?.data?.password)

  if (passwordResponse?.data?.password) {
    await showPasswordModal(passwordResponse.data.password)
  } else {
    const successMessage =
      ids.length > 1
        ? `Acción completada exitosamente en ${ids.length} elementos`
        : 'Acción completada exitosamente'

    showToast(successMessage, 'success')
  }

  if (action !== 'resetPassword') {
    window.location.reload()
  }
}

/**
 * Función principal que orquesta todo el flujo
 */
export async function handleOtherCases(action: string, encryptId: string, elem?: HTMLElement) {
  const entityName = crud.formTitleSuffix?.toLowerCase() || 'registro'
  const status = elem?.dataset?.status

  const caseData = resolveCase(action, status, entityName)
  if (!caseData) return console.error(`Acción no reconocida: ${action}`)

  const idsToProcess = resolveIds(encryptId, elem)
  if (idsToProcess.length === 0) return console.error('No hay IDs seleccionados para procesar')

  if (!caseData.text) {
    console.error('caseData sin text:', caseData)
    showToast('Configuración inválida de la acción', 'error')
    return
  }

  const confirmText =
    idsToProcess.length > 1
      ? caseData.text.replace('este', `estos ${idsToProcess.length}`)
      : caseData.text

  try {
    const confirmed = await confirmAction(confirmText, caseData.icon!, caseData.confirmButtonText!)
    if (!confirmed) return showToast('Los cambios no se guardaron', 'info')

    const results = await sendRequests(idsToProcess, caseData, status)

    await handleResult(action, idsToProcess, results)
  } catch (error: any) {
    console.error(
      `Error al registrar la acción '${action}':`,
      error?.response?.data || error.message
    )
    showToast('Error al procesar la acción', 'error')
  }
}
