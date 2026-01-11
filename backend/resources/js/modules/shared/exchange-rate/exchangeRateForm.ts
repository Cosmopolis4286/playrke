// ==========================
//   Imports
// ==========================
import { formCRUD } from '@/helpers/form/formCrudInit';
import { insertOtherCurrencies } from '@/helpers/selects/otherCurrencies';
import { SweetAlertIcon } from 'sweetalert2';
import { validateInput } from '@/utils/validateInput';
import { route } from '@/routes/commerceRoutes';
import { initAllSelects } from '@/helpers/selects/initAllSelects';

// Re-exportar route para que otros módulos puedan usarla directamente
export { route };

// ==========================
//   Constantes globales
// ==========================
export const formTitleSuffix = 'Comercio';
export const moduleId = 'commerce_id';
const modal = document.getElementById(`modal-${moduleId}`)!;

// ==========================
//   Referencias a elementos del formulario
// ==========================
const usernameInput = document.getElementById('username') as HTMLInputElement | null;
const emailInput = document.getElementById('email') as HTMLInputElement | null;

// ==========================
//   Flag de estado
// ==========================
let listenersAttached = false;

// ==========================
//   Campos requeridos
// ==========================
export const requiredFields: string[] =
    ['name', 'country', 'time-zone', 'currency', 'phone', 'username', 'email'];

// ==========================
//   Mapeo de acciones personalizadas
// ==========================
export const otherCasesMap: Record<string, {
    routeName: string;             // Nombre de la ruta backend
    text: string;                  // Texto a mostrar en el modal de confirmación
    icon: SweetAlertIcon;          // Icono de SweetAlert
    confirmButtonText: string;     // Texto del botón de confirmación
    method?: 'PUT' | 'DELETE';     // Método HTTP, opcional, por defecto PUT
}> = {
    '4': {  // Cambiar status de un comercio
        routeName: 'changeStatus',
        text: '¿Deseas cambiar el status de este comercio?',
        icon: 'question',
        confirmButtonText: 'Sí, cambiar',
        method: 'PUT',
    },
    '5': {  // Generar nueva contraseña
        routeName: 'changePassw',
        text: 'Generar nueva contraseña a este comercio',
        icon: 'info',
        confirmButtonText: 'Sí, generar',
        method: 'PUT',
    },
};

// ==========================
//   Función: insertInfo
// ==========================
/**
 * Inserta información en el formulario del modal según la acción (crear o editar)
 *
 * Esta función:
 * 1. Verifica que los elementos DOM requeridos existan.
 * 2. Resetea inputs editables (username, email).
 * 3. Inicializa automáticamente todos los selects dinámicos usando `initAllSelects`.
 * 4. En modo edición, rellena los campos del formulario con los datos proporcionados.
 * 5. Muestra el modal con Bootstrap 4.
 *
 * @param action - 'create' o 'edit'
 * @param data - Objeto con los datos a precargar en modo edición
 */
export function insertInfo(action: 'create' | 'edit', data: any = null): void {
    if (!usernameInput || !emailInput || !modal) {
        console.error('Elementos DOM requeridos no encontrados');
        return;
    }

    // Reset inputs editables
    usernameInput.readOnly = false;
    emailInput.readOnly = false;
    usernameInput.value = '';
    emailInput.value = '';

    // Limpiar otros inputs si es creación
    if (action === 'create') {
        (document.getElementById('name') as HTMLInputElement).value = '';
        (document.getElementById('phone') as HTMLInputElement).value = '';
    }

    // Preselección de valores para selects dinámicos
    const selectedValues: Record<string, string | number> = {};
    if (action === 'edit' && data) {
        selectedValues['country'] = data?.commerce?.country ?? '';
        selectedValues['time-zone'] = data?.user?.time_zone ?? '';
        // Se pueden agregar más selects dinámicos aquí
    }

    // Inicializa todos los selects dinámicos automáticamente
    initAllSelects(selectedValues);

    // Insertar otras monedas si es edición
    if (action === 'edit' && data?.otherCurrencies) {
        insertOtherCurrencies(data.otherCurrencies);
    }

    // Rellenar campos en modo edición
    if (action === 'edit' && data) {
        const { commerce, user } = data;

        (document.getElementById('name') as HTMLInputElement).value = commerce.name || '';
        (document.getElementById('phone') as HTMLInputElement).value = commerce.phone || '';
        (document.getElementById('code-rke') as HTMLInputElement).value = commerce.commn || '';

        usernameInput.value = user.username || '';
        emailInput.value = user.email || '';

        usernameInput.readOnly = true;
        emailInput.readOnly = true;

    }

    // Mostrar modal con Bootstrap 4
    $(modal).modal('show');
}

// ==========================
//   Función: initListeners
// ==========================
/**
 * Inicializa los listeners globales del formulario:
 * - Máscara de teléfono
 * - Eventos click para botones dinámicos
 * - Validación de input de comisión
 */
export function initListeners(): void {
    if (listenersAttached) return;
    listenersAttached = true;

    // Máscara para teléfono usando jQuery Mask
    if (typeof $ === 'function') {
        const $phone = $('#phone');
        if ($phone.length) $phone.mask('00000000000');
    }

    // Click en botones dinámicos btn-<moduleId>
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
