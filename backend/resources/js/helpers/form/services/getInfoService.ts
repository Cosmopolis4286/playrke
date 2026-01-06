import axios from '@/plugins/axios';
import { formCrudState as crud } from '../state/formCrudState';

export async function getInfo(encryptId: string): Promise<void> {
    if (!encryptId || !crud.routeFn) return;

    const url = crud.routeFn('show', { id: encryptId });
    const { data } = await axios.get(url);

    if (data?.data) {
        crud.insertInfo?.('edit', data.data);
        $(crud.modalId!).modal('show');
    }
}
