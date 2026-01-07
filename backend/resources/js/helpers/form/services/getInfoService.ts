import axios from '@/plugins/axios';
import { formCrudState as crud } from '../state/formCrudState';
import { route } from '@/routes/helper';

export async function getInfo(encryptId: string): Promise<void> {
    if (!encryptId) return;

    const url = route(crud.routes, 'show', { id: encryptId });
    const { data } = await axios.get(url);

    if (data?.data) {
        crud.insertInfo?.('edit', data.data);
        $(crud.modalId!).modal('show');
    }
}
