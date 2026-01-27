import { useCrudForm } from '@/js/composables/form/useCrudForm'
import { handleOtherCases } from '../services/crudOtherCasesService'

/**
 * Composable que provee la función para ejecutar acciones CRUD,
 * orquestando llamadas a servicios y preparación de formularios.
 *
 * Separa la lógica de decidir qué acción ejecutar,
 * delegando acciones concretas a otros servicios o composables.
 *
 * @returns {Object} Objeto con método `executeCrud` para invocar acciones CRUD.
 */
export function useCrudExecutor() {
  // Obtenemos funciones para preparar formularios create/edit
  const { prepareCreate, prepareEdit } = useCrudForm()

  /**
   * Ejecuta una acción CRUD específica.
   *
   * - 'create': abre el modal para crear un nuevo registro.
   * - 'edit': abre el modal para editar, requiere un ID cifrado.
   * - Otros: delega a handleOtherCases para acciones como delete, changeStatus, etc.
   *
   * @param {string} action - Nombre de la acción CRUD (create, edit, delete, etc.)
   * @param {string[]} [encryptIds=[]] - IDs cifrados necesarios para la acción (por ejemplo, para editar o borrar)
   * @returns {Promise<void>} Promise que se resuelve cuando termina la acción (útil para esperar animaciones, fetch, etc.)
   */
  async function executeCrud(action: string, encryptIds: string[] = []): Promise<void> {
    switch (action) {
      case 'create':
        // Prepara el formulario y muestra modal para crear
        prepareCreate()
        break

      case 'edit': {
        // Editar requiere al menos un ID cifrado
        const [id] = encryptIds
        if (!id) {
          console.warn('[useCrudExecutor] ID requerido para acción de edición')
          return
        }
        // Prepara el formulario para editar y muestra modal
        await prepareEdit(id)
        break
      }

      default:
        // Para otras acciones, delega al manejador especializado
        await handleOtherCases(action, encryptIds)
        break
    }
  }

  // Exponer el método para usar en componentes o composables
  return {
    executeCrud
  }
}
