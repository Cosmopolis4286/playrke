// services/crudOtherCasesService.ts
import axios from '@/plugins/axios'
import Swal from 'sweetalert2'
import type { SweetAlertIcon } from 'sweetalert2'
import { formCrudState as crud } from '../state/formCrudState'
import { showToast, showToastConfirmAction } from '../ui/crudModalUI'
import type { OtherCase } from '@/helpers/module/types/formModule.types'
import { getSelectedIds } from '@/alpine/tableSelectionListener'
import { route } from '@/routes/helper'

/**
 * Resuelve el caso de acción personalizado o global
 */
function resolveCase(action: string, status?: string, entityName = 'registro'): OtherCase | null {
  const globalActions: Record<string, OtherCase> = {
    delete: {
      routeName: 'destroy',
      text: `¿Deseas eliminar este ${entityName}?`,
      icon: 'question' as SweetAlertIcon,
      confirmButtonText: 'Sí, eliminar',
      method: 'DELETE'
    },
    'change-status': {
      routeName: 'changeStatus',
      text: status
        ? `¿Deseas cambiar el estado a "${status}" de este ${entityName}?`
        : `¿Deseas cambiar el estado de este ${entityName}?`,
      icon: 'question' as SweetAlertIcon,
      confirmButtonText: 'Sí, cambiar',
      method: 'PUT'
    },
    'reset-password': {
      routeName: 'resetPassword',
      text: `¿Deseas generar una nueva contraseña para este ${entityName}?`,
      icon: 'warning' as SweetAlertIcon,
      confirmButtonText: 'Sí, generar',
      method: 'PUT'
    }
  }
  return globalActions[action] || crud.otherCasesMap[action] || null
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
 * Modal para mostrar contraseña generada con botón copiar
 */
async function showPasswordModal(password: string) {
  await Swal.fire({
    title: 'Nueva Contraseña Generada',
    html: `
      <div style="text-align: left;">
        <p><strong>Contraseña:</strong></p>
        <div style="position: relative; display: flex; align-items: center; gap: 8px;">
          <div id="password-display" style="flex: 1; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 16px; word-break: break-all;">
            ${password}
          </div>
          <button id="copy-password-btn" type="button" style="padding: 10px 15px; background-color: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center;" title="Copiar al portapapeles">
            <i class="fas fa-copy"></i>
            <span>Copiar</span>
          </button>
        </div>
        <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">
          <i class="fas fa-info-circle"></i> Guarda esta contraseña en un lugar seguro. No podrás verla nuevamente.
        </p>
      </div>
    `,
    icon: 'success',
    confirmButtonText: 'Entendido',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      const copyBtn = document.getElementById('copy-password-btn')
      if (!copyBtn) return

      copyBtn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(password)
          const btnText = copyBtn.querySelector('span')
          const btnIcon = copyBtn.querySelector('i')

          if (btnText && btnIcon) {
            btnIcon.className = 'fas fa-check'
            btnText.textContent = 'Copiado'
            copyBtn.style.backgroundColor = '#10b981'

            setTimeout(() => {
              btnIcon.className = 'fas fa-copy'
              btnText.textContent = 'Copiar'
              copyBtn.style.backgroundColor = '#6366f1'
            }, 2000)
          }
        } catch {
          showToast('Error al copiar la contraseña', 'error')
        }
      })
    }
  })
}

/**
 * Muestra resultados según respuesta
 */
async function handleResult(ids: string[], results: any[]): Promise<void> {
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
}

/**
 * Función principal que orquesta todo el flujo
 */
export async function handleOtherCases(action: string, encryptId: string, elem?: HTMLElement) {
  const entityName = crud.formTitleSuffix?.toLowerCase() || 'registro'
  const status = elem?.dataset?.status

  console.log(
    `Iniciando acción '${action}' para ${encryptId ? `ID: ${encryptId}` : 'IDs seleccionados'}`
  )

  const caseData = resolveCase(action, status, entityName)
  if (!caseData) return console.error(`Acción no reconocida: ${action}`)

  const idsToProcess = resolveIds(encryptId, elem)
  if (idsToProcess.length === 0) return console.error('No hay IDs seleccionados para procesar')

  const confirmText =
    idsToProcess.length > 1
      ? caseData.text.replace('este', `estos ${idsToProcess.length}`)
      : caseData.text

  try {
    const confirmed = await confirmAction(confirmText, caseData.icon!, caseData.confirmButtonText!)
    if (!confirmed) return showToast('Los cambios no se guardaron', 'info')

    const results = await sendRequests(idsToProcess, caseData, status)

    await handleResult(idsToProcess, results)
  } catch (error: any) {
    console.error(
      `Error al registrar la acción '${action}':`,
      error?.response?.data || error.message
    )
    showToast('Error al procesar la acción', 'error')
  }
}
