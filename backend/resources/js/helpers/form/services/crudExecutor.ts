// backend/resources/js/helpers/form/services/crudExecutor.ts
import { handleOtherCases } from '../services/crudOtherCasesService';
import { prepareCreate, prepareEdit } from '../services/crudFormService';

/**
 * Ejecuta una acción CRUD basada en el action recibido
 *
 * @param action - Acción CRUD (create | edit | delete)
 * @param encryptId - ID cifrado (requerido para edit/delete)
 */
export async function executeCrud(action: string, encryptId?: string): Promise<void> {
    switch (action) {
        case 'create':
            prepareCreate();
            break;

        case 'edit':
            if (!encryptId) {
                throw new Error('ID requerido para edición');
            }
            await prepareEdit(encryptId);
            break;

        case 'delete':
        default:
            await handleOtherCases(action, encryptId ?? '', null);
            break;
    }
}

