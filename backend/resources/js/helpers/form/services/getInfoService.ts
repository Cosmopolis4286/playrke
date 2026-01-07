import axios from '@/plugins/axios';
import { formCrudState as crud } from '../state/formCrudState';

export async function getInfo(encryptId: string): Promise<void> {
    if (!encryptId) return;

    if (typeof crud.routeFn !== 'function') {
        if (import.meta.env.DEV) {
            console.warn('[CRUD] routeFn no definida');
        }
        return;
    }

    const url = crud.routeFn('show', { id: encryptId });

    console.log('[CRUD] GET →', url);
    const { data } = await axios.get(url);

    if (data?.data) {
        crud.insertInfo?.('edit', data.data);
        $(crud.modalId!).modal('show');
    }
}
