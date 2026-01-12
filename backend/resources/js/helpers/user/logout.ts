import { axios, clearAuthTokens } from '@js/plugins/axios';
import { router } from '@inertiajs/vue3';

/**
 * Interfaz para las opciones de logout
 */
interface LogoutOptions {
    logoutUrl?: string;
    loginRedirectUrl?: string;
    showConfirmation?: boolean;
    confirmationMessage?: string;
}

/**
 * Realiza el proceso de logout del usuario
 *
 * @param options - Opciones de configuración para el logout
 * @returns Promise que se resuelve cuando el logout es exitoso
 *
 * @example
 * // Logout básico
 * await performLogout();
 *
 * // Logout con opciones personalizadas
 * await performLogout({
 *   logoutUrl: '/custom-logout',
 *   loginRedirectUrl: '/custom-login',
 *   showConfirmation: true
 * });
 */
export async function performLogout(options: LogoutOptions = {}): Promise<void> {
    const {
        logoutUrl = '/logout',
        loginRedirectUrl = '/login',
        showConfirmation = false,
        confirmationMessage = '¿Estás seguro de que deseas cerrar sesión?'
    } = options;

    // Mostrar confirmación si está habilitada
    if (showConfirmation) {
        const confirmed = confirm(confirmationMessage);
        if (!confirmed) {
            return;
        }
    }

    try {
        // Obtener token CSRF
        const csrfToken = getCsrfToken();

        // Realizar petición de logout
        await axios.post(
            logoutUrl,
            {},
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
            }
        );

        // Limpiar tokens y datos de usuario
        clearAuthTokens();
        clearUserData();

        // Redirigir usando window.location para forzar redirección inmediata
        window.location.href = loginRedirectUrl;

    } catch (error) {
        console.error('Error durante el logout:', error);

        // Incluso si hay error en el servidor, limpiar datos locales
        clearAuthTokens();
        clearUserData();

        // Redirigir automáticamente sin mostrar confirmación
        window.location.href = loginRedirectUrl;
    }
}

/**
 * Inicializa los eventos de logout para botones con clase .logout-btn
 *
 * @param options - Opciones de configuración para el logout
 *
 * @example
 * // HTML
 * <button class="logout-btn">Cerrar sesión</button>
 * <button class="logout-btn" data-logout-url="/custom-logout">Logout personalizado</button>
 *
 * // JS
 * initLogout();
 */
export function initLogout(options: LogoutOptions = {}): void {
    document.querySelectorAll<HTMLButtonElement>('.logout-btn').forEach((button) => {
        // Crear función handler específica para este botón
        const buttonHandler = async (event: Event) => {
            await handleLogoutClick(event, button, options);
        };

        // Remover listeners previos para evitar duplicados
        (button as any)._logoutHandler && button.removeEventListener('click', (button as any)._logoutHandler);

        // Guardar referencia al handler y agregar nuevo listener
        (button as any)._logoutHandler = buttonHandler;
        button.addEventListener('click', buttonHandler);
    });
}

/**
 * Maneja el evento de click en botones de logout
 */
async function handleLogoutClick(
    event: Event,
    button: HTMLButtonElement,
    defaultOptions: LogoutOptions
): Promise<void> {
    event.preventDefault();

    // Deshabilitar botón durante el proceso
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Cerrando sesión...';

    try {
        // Obtener opciones desde atributos data del botón
        const buttonOptions: LogoutOptions = {
            logoutUrl: button.dataset.logoutUrl || defaultOptions.logoutUrl,
            loginRedirectUrl: button.dataset.loginRedirectUrl || defaultOptions.loginRedirectUrl,
            showConfirmation: button.dataset.showConfirmation !== 'false'
        };

        await performLogout(buttonOptions);

    } catch (error) {
        console.error('Error en logout click handler:', error);
    } finally {
        // Restaurar botón
        button.disabled = false;
        button.textContent = originalText;
    }
}

/**
 * Obtiene el token CSRF desde meta tag o cookie
 */
function getCsrfToken(): string {
    // Intentar obtener desde meta tag
    const metaToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (metaToken) {
        return metaToken;
    }

    // Intentar obtener desde cookie
    const cookieToken = getCookie('XSRF-TOKEN');
    if (cookieToken) {
        return decodeURIComponent(cookieToken);
    }

    console.warn('No se encontró token CSRF');
    return '';
}

/**
 * Obtiene una cookie por nombre
 */
function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }
    return null;
}

/**
 * Limpia datos adicionales del usuario
 */
function clearUserData(): void {
    // Limpiar localStorage
    const keysToRemove = [
        'user_data',
        'user_preferences',
        'cart_data',
        'session_data'
    ];

    keysToRemove.forEach(key => {
        localStorage.removeItem(key);
    });

    // Limpiar sessionStorage
    sessionStorage.clear();
}

/**
 * Función de utilidad para logout programático
 *
 * @param immediate - Si es true, no muestra confirmación
 */
export async function logoutUser(immediate: boolean = false): Promise<void> {
    await performLogout({
        showConfirmation: !immediate
    });
}

// Inicialización automática cuando el DOM esté listo
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initLogout());
    } else {
        initLogout();
    }
}

// Exportar función principal como default
export default performLogout;
