// commerceForm.ts
import { formCRUD } from '@/helpers/form/formCrudInit';
import { insertOtherCurrencies, initCurrencySelector } from '@/helpers/selects/insertOtherCurrencies';
import { SweetAlertIcon } from 'sweetalert2';
import { validateInput } from '@/helpers/validateInput';
import { route } from '@/routes/commerceRoutes';
import { initAllSelects } from '@/helpers/selects/initAllSelects';
import { fillFormFromObject } from '@/helpers/form/formHelpers';
import { getAppDataProperty } from '@/data/index';

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
    clearValidationErrors();

    // Valores preseleccionados para selects dinámicos
    const selectedValues: Record<string, string | number> = {};

    // Rellenar campos en edición
    if (action === 'edit' && data) {
        const { commerce, user } = data;

        // Debug: ver qué datos estamos recibiendo
        console.log('📦 Data recibida:', { commerce, user });
        console.log('🔑 IDs a preseleccionar:', {
            country_id: commerce?.country_id,
            currency_id: commerce?.currency_id,
            time_zone_id: user?.time_zone_id
        });

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
        // En modo creación, habilitar campos
        usernameInput.readOnly = false;
        emailInput.readOnly = false;
    }

    // 1. Inicializar todos los selects básicos con los valores preseleccionados
    console.log('🎯 Valores a preseleccionar:', selectedValues);
    initAllSelects(selectedValues);

    // Debug: verificar valores después de initAllSelects
    if (action === 'edit') {
        console.log('✅ Valores después de initAllSelects:', {
            country: (document.getElementById('country') as HTMLSelectElement)?.value,
            currency: (document.getElementById('currency') as HTMLSelectElement)?.value,
            timeZone: (document.getElementById('time-zone') as HTMLSelectElement)?.value,
        });

        // Disparar evento change en country para que actualice currency y time-zone
        const countrySelect = document.getElementById('country') as HTMLSelectElement;
        if (countrySelect && countrySelect.value) {
            console.log('🔥 Disparando evento change en country');
            $(countrySelect).trigger('change');
        }
    }

    // 2. DESPUÉS de que el evento change cargue las opciones, establecer los valores
    if (action === 'edit' && data) {
        const { commerce, user } = data;

        // Esperar a que el evento change de country termine de cargar opciones
        setTimeout(() => {
            // Debug: Verificar si los datos globales existen
            const currencies = getAppDataProperty('currencies', []);
            const timeZones = getAppDataProperty('timeZones', []);
            console.log('📊 Currencies disponibles:', currencies.length);
            console.log('📊 TimeZones disponibles:', timeZones.length);

            // Establecer currency manualmente recorriendo las opciones disponibles
            const currencySelect = document.getElementById('currency') as HTMLSelectElement;
            if (currencySelect && commerce?.currency_id) {
                console.log('🔍 Opciones en currency después del change:',
                    Array.from(currencySelect.options).map(o => ({ value: o.value, text: o.text }))
                );

                // Si no hay opciones cargadas, cargarlas manualmente
                if (currencySelect.options.length <= 1 && currencies.length > 0) {
                    console.log('⚙️ Cargando opciones de currency manualmente...');
                    // Filtrar currencies por país
                    const currenciesForCountry = currencies.filter((c: any) =>
                        String(c.country_id) === String(commerce.country_id)
                    );

                    // Limpiar y agregar opciones
                    currencySelect.innerHTML = '<option value="">Seleccionar moneda principal</option>';
                    currenciesForCountry.forEach((curr: any) => {
                        const option = document.createElement('option');
                        option.value = String(curr.id);
                        option.text = `(${curr.symbol}) ${curr.description}`;
                        currencySelect.appendChild(option);
                    });
                }

                currencySelect.value = String(commerce.currency_id);
                $(currencySelect).trigger('change');
                console.log('💰 Currency establecido a:', currencySelect.value);
            }

            // Establecer time-zone manualmente
            const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement;
            if (timeZoneSelect && user?.time_zone_id) {
                console.log('🔍 Opciones en time-zone después del change:',
                    Array.from(timeZoneSelect.options).map(o => ({ value: o.value, text: o.text }))
                );

                // Si no hay opciones cargadas, cargarlas manualmente
                if (timeZoneSelect.options.length <= 1 && timeZones.length > 0) {
                    console.log('⚙️ Cargando opciones de time-zone manualmente...');
                    // Filtrar time zones por país
                    const timeZonesForCountry = timeZones.filter((tz: any) =>
                        String(tz.country_id) === String(commerce.country_id)
                    );

                    // Limpiar y agregar opciones
                    timeZoneSelect.innerHTML = '<option value="">Seleccionar zona horaria</option>';
                    timeZonesForCountry.forEach((tz: any) => {
                        const option = document.createElement('option');
                        option.value = String(tz.id);
                        option.text = `${tz.name} (${tz.offset})`;
                        timeZoneSelect.appendChild(option);
                    });
                }

                timeZoneSelect.value = String(user.time_zone_id);
                $(timeZoneSelect).trigger('change');
                console.log('⏰ Time-zone establecido a:', timeZoneSelect.value);
            }

            // Insertar monedas adicionales
            if (data?.other_currencies) {
                insertOtherCurrencies(data.other_currencies);
            }

            // Insertar juegos permitidos
            if (data?.allowed_games) {
                insertOtherCurrencies(data.allowed_games);
            }
        }, 500); // Aumentado a 500ms
    }

    // Mostrar modal
    $(modal).modal('show');
}

/**
 * Limpia los errores de validación del formulario
 */
function clearValidationErrors(): void {
    const form = document.getElementById('commerce-form');
    if (!form) return;

    // Remover clases is-invalid
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Remover mensajes de error
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
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
