import { executeCrud } from './services/crudExecutor';

alert('formCRUD called'); // Debugging line
export async function formCRUD(elem: HTMLElement): Promise<void> {
    await executeCrud(elem);
}
