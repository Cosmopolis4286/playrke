// backend/resources/js/helpers/form/services/crudExecutor.ts
import { handleOtherCases } from '../services/crudOtherCasesService'
import { prepareCreate, prepareEdit } from '../services/crudFormService'

/**
 * Ejecuta una acción CRUD basada en el action recibido
 *
 * @param action - Acción CRUD (create | edit | delete)
 * @param encryptIds - IDs cifrados (requerido para edit/delete/etc)
 */
export async function executeCrud(action: string, encryptIds?: string[]): Promise<void> {
  switch (action) {
    case 'create':
      prepareCreate()
      break

    case 'edit':
      if (!encryptIds[0]) {
        throw new Error('ID requerido para edición')
      }
      await prepareEdit(encryptIds[0])
      break

    case 'delete':
    default:
      await handleOtherCases(action, encryptIds)
      break
  }
}
