// commerceForm.ts
import { formCRUD } from '@/helpers/form/formCrudInit';
import { insertOtherCurrencies, initCurrencySelector, updateCurrency, updateTimeZones } from '@/helpers/selects/insertOtherCurrencies';
import { SweetAlertIcon } from 'sweetalert2';
import { validateInput } from '@/helpers/validateInput';
import { route } from '@/routes/commerceRoutes';
import { initAllSelects } from '@/helpers/selects/initAllSelects';
import { fillFormFromObject, clearValidationErrors, resetFormAndContainers } from '@/helpers/form/formHelpers';

// Re-exportar route para que otros módulos puedan usarla directamente
export { route };

// ==========================
// Constantes globales
// ==========================
export const formTitleSuffix = 'Comercio';
export const moduleId = 'commerce-form';

export const requiredFields: string[] = ['name', 'country', 'time-zone', 'currency', 'phone', 'username', 'email'];

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

    // Limpiar errores de validación previos
    clearValidationErrors('commerce-form');

    // Valores preseleccionados para selects dinámicos
    const selectedValues: Record<string, string | number> = {};

    // Rellenar campos en edición
    if (action === 'edit' && data) {
        const { commerce, user } = data;


        fillFormFromObject(commerce);

        usernameInput.value = user.username ?? '';
        emailInput.value = user.email ?? '';
        usernameInput.readOnly = true;
        emailInput.readOnly = true;

        // Valores preseleccionados para selects (usando los IDs de las foreign keys)
        selectedValues['country'] = commerce?.country_id ?? '';
        selectedValues['currency'] = commerce?.currency_id ?? '';
        selectedValues['time-zone'] = user?.time_zone_id ?? '';
    } else {
        // En modo creación, resetear formulario y ocultar contenedores dinámicos
        resetFormAndContainers('commerce-form', ['container-currency', 'container-time-zone']);

        // Habilitar campos de username y email
        usernameInput.readOnly = false;
        emailInput.readOnly = false;
    }

    // 1. Inicializar todos los selects básicos con los valores preseleccionados
    initAllSelects(selectedValues);

    // 2. En modo edición, cargar opciones de currency y time-zone programáticamente
    if (action === 'edit' && data) {
        const { commerce, user } = data;

        // Esperar a que initAllSelects termine de cargar el país
        setTimeout(() => {
            if (import.meta.env.DEV) {
                console.log('🔄 Modo edición - Cargando opciones para país:', commerce.country_id);
            }

            // Llamar directamente a las funciones para cargar opciones
            updateCurrency(commerce.country_id);
            updateTimeZones(commerce.country_id, String(user.time_zone_id));

            console.log(user);

            // Esperar a que las opciones se carguen
            setTimeout(() => {
                const currencySelect = document.getElementById('currency') as HTMLSelectElement;
                const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement;

                // Establecer currency
                if (currencySelect && commerce?.currency_id) {
                    if (import.meta.env.DEV) {
                        console.log('💰 Estableciendo currency:', commerce.currency_id);
                        console.log('💰 Opciones disponibles:', Array.from(currencySelect.options).map(o => ({ value: o.value, text: o.text })));
                    }

                    currencySelect.value = String(commerce.currency_id);
                    $(currencySelect).trigger('change');

                    // Mostrar el contenedor de currency
                    const containerCurrency = document.getElementById('container-currency');
                    containerCurrency?.classList.remove('d-none');
                }

                // Time-zone ya fue establecido por updateTimeZones
                if (timeZoneSelect) {
                    if (import.meta.env.DEV) {
                        console.log('🕐 Time-zone establecido:', timeZoneSelect.value);
                        console.log('🕐 Opciones disponibles:', Array.from(timeZoneSelect.options).map(o => ({ value: o.value, text: o.text })));
                    }

                    // Mostrar el contenedor de time-zone
                    const containerTimeZone = document.getElementById('container-time-zone');
                    containerTimeZone?.classList.remove('d-none');
                }

                // Insertar monedas adicionales
                if (data?.other_currencies && data.other_currencies.length > 0) {
                    if (import.meta.env.DEV) {
                        console.log('💵 Insertando other currencies:', data.other_currencies);
                    }
                    insertOtherCurrencies(data.other_currencies);
                }

                // Insertar juegos permitidos
                if (data?.allowed_games && data.allowed_games.length > 0) {
                    if (import.meta.env.DEV) {
                        console.log('🎮 Insertando allowed games:', data.allowed_games);
                    }
                    insertOtherCurrencies(data.allowed_games);
                }
            }, 100); // Tiempo para que las funciones terminen de cargar opciones
        }, 200); // Tiempo para que initAllSelects termine
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

    // Inicializar selector de moneda/zona horaria por país
    initCurrencySelector();

    // Máscara de teléfono
    if (typeof $ === 'function') {
        const $phone = $('#phone');
        if ($phone.length) $phone.mask('00000000000');
    }

    // Click en botones dinámicos del formulario
    document.addEventListener('click', async ({ target }) => {
        const element = (target as HTMLElement | null)?.closest<HTMLElement>(`.btn-${moduleId}`);

        if (!element) return;
        try {
            await formCRUD(element);
        } catch (e) {
            console.error('Error en formCRUD:', e);
        }
    });

    // Validación de input 'commission'
    document.addEventListener('input', ({ target }) => {
        if (target instanceof HTMLInputElement && target.id === 'commission') {
            validateInput(target, true, 3, 35);
        }
    });
}
