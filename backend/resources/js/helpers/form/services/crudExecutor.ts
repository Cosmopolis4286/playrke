// backend/resources/js/helpers/form/services/crudExecutor.ts
import { formCrudState as crud } from '../state/formCrudState';
import { getCurrentEncryptId } from '@/alpine/tableSelectionListener';
import { handleOtherCases } from '../services/crudOtherCasesService';
import { prepareCreate, prepareEdit } from '../services/crudFormService';

export async function executeCrud(elem: HTMLElement): Promise<void> {
    const action = elem.dataset.action;
    if (!action) return;

    const encryptId = getCurrentEncryptId(crud.tableId);
    console.log('crud.tableId', crud.tableId);

    switch (action) {
        case 'create':
            prepareCreate();
            break;

        case 'edit':
            await prepareEdit(encryptId);
            break;

        case 'delete':
        default:
            await handleOtherCases(action, encryptId, elem);
            break;
    }
}
