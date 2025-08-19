import axios from "@/plugins/axios";

/**
 * Inicializa el evento de cierre de sesión (logout).
 * 
 * Busca todos los botones con la clase `.logout-btn` y les agrega un 
 * evento de click que envía una petición POST al endpoint de logout 
 * usando Axios, con el token CSRF incluido en los headers.
 *
 * @example
 * // HTML
 * <button class="logout-btn">Cerrar sesión</button>
 *
 * // JS (antes de usar la función, asegúrate de definir las variables globales)
 * window.logoutUrl = "/logout";
 * window.loginRedirectUrl = "/login";
 * window.csrfToken = "TOKEN_CSRF_GENERADO";
 *
 * // Inicializar
 * initLogout();
 */
export function initLogout(): void {
    document.querySelectorAll<HTMLButtonElement>('.logout-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const { logoutUrl = '/logout', loginRedirectUrl = '/login', csrfToken = '' } = window as any;

            try {
                await axios.post(
                    logoutUrl,
                    {}, // cuerpo vacío, solo headers
                    {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                        },
                    }
                );
                window.location.href = loginRedirectUrl;
            } catch (error) {
                console.error('Error en logout:', error);
                alert('Error al cerrar sesión');
            }
        });
    });
}
