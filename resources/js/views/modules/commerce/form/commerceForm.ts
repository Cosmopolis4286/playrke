// commerceForm.ts
import { formCRUD } from '@/helpers/form/formCrudInit';
import { insertOtherCoins } from '@/helpers/insertOtherCoins';
import { SweetAlertIcon } from 'sweetalert2';
import { validateInput } from '@/helpers/validateInput';
import { route } from '@/routes/commerceRoutes';
import { initAllSelects } from '@/utils/initAllSelects';
import { getSelectedIds } from '@/alpine/tableSelectionListener';

const ids = getSelectedIds('commerce-table');
console.log('IDs seleccionados rápidamente:', ids);

// Re-exportar route para que otros módulos puedan usarla directamente
export { route };

// ==========================
// Constantes globales
// ==========================
export const formTitleSuffix = 'Comercio';
export const moduleId = 'commerce-form';

export const requiredFields: string[] = ['name', 'country', 'time-zone', 'coin', 'phone', 'username', 'email'];

// ==========================
// Mapeo de acciones personalizadas
// ==========================
export const otherCasesMap: Record<string, {
    routeName: string;
    text: string;
    icon: SweetAlertIcon;
    confirmButtonText: string;
    method?: 'PUT' | 'DELETE';
}> = {
    '4': {
        routeName: 'changeStatus',
        text: '¿Deseas cambiar el status de este comercio?',
        icon: 'question',
        confirmButtonText: 'Sí, cambiar',
        method: 'PUT',
    },
    '5': {
        routeName: 'changePassw',
        text: 'Generar nueva contraseña a este comercio',
        icon: 'info',
        confirmButtonText: 'Sí, generar',
        method: 'PUT',
    },
};

// ==========================
// Función: insertInfo
// ==========================
/**
 * Inserta información en el formulario del modal según la acción.
 *
 * @param action - 'create' | 'edit'
 * @param data - Datos para precargar en modo edición
 */
export function insertInfo(action: 'create' | 'edit', data: any = null): void {
    const modal = document.getElementById(`modal-${moduleId}`);
    if (!modal) return console.error('Modal no encontrado');

    const usernameInput = document.getElementById('username') as HTMLInputElement | null;
    const emailInput = document.getElementById('email') as HTMLInputElement | null;
    if (!usernameInput || !emailInput) return console.error('Inputs username/email no encontrados');

    // Reset de inputs editables
    usernameInput.readOnly = false;
    emailInput.readOnly = false;
    usernameInput.value = '';
    emailInput.value = '';

    if (action === 'create') {
        ['name', 'phone'].forEach(id => {
            const input = document.getElementById(id) as HTMLInputElement | null;
            if (input) input.value = '';
        });
    }

    // Valores preseleccionados para selects dinámicos
    const selectedValues: Record<string, string | number> = {};
    if (action === 'edit' && data) {
        selectedValues['country'] = data?.commerce?.country ?? '';
        selectedValues['time-zone'] = data?.user?.time_zone ?? '';
    }

    initAllSelects(selectedValues);

    // Insertar monedas adicionales
    if (action === 'edit' && data?.otherCoins) insertOtherCoins(data.otherCoins);

    // Rellenar campos en edición
    if (action === 'edit' && data) {
        const { commerce, user } = data;
        ['name', 'phone', 'code-rke'].forEach(id => {
            const input = document.getElementById(id) as HTMLInputElement | null;
            if (input) input.value = commerce[id as keyof typeof commerce] ?? '';
        });

        usernameInput.value = user.username ?? '';
        emailInput.value = user.email ?? '';
        usernameInput.readOnly = true;
        emailInput.readOnly = true;
    }

    // Mostrar modal
    $(modal).modal('show');
}

// ==========================
// Función: initListeners
// ==========================
let listenersAttached = false;

/**
 * Inicializa listeners globales del formulario
 */
export function initListeners(): void {
    if (listenersAttached) return;
    listenersAttached = true;

    const modal = document.getElementById(`modal-${moduleId}`);
    if (!modal) return;

    // Máscara de teléfono
    if (typeof $ === 'function') {
        const $phone = $('#phone');
        if ($phone.length) $phone.mask('00000000000');
    }

    // Click en botones dinámicos del formulario
    document.addEventListener('click', ({ target }) => {
        const element = (target as HTMLElement | null)?.closest<HTMLElement>(`.btn-${moduleId}`);

        if (!element) return;
        formCRUD(element).catch(e => console.error('Error en formCRUD:', e));
    });

    // Validación de input 'commission'
    document.addEventListener('input', ({ target }) => {
        if (target instanceof HTMLInputElement && target.id === 'commission') {
            validateInput(target, true, 3, 35);
        }
    });
}
