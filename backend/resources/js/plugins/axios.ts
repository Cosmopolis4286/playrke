import axios, {
    type AxiosInstance,
    type AxiosResponse,
    type AxiosError,
    type Method,
    type InternalAxiosRequestConfig,
} from 'axios'

/**
 * URL base del backend (Laravel)
 *
 * NOTA:
 * - Se usan URLs relativas porque Blade + SPA
 *   viven en el mismo dominio.
 * - Evita problemas de CORS y cookies.
 */
const BACKEND_BASE_URL = ''

/**
 * Extensión del config interno de Axios
 * para soportar reintentos controlados.
 */
interface RetryAxiosRequestConfig extends InternalAxiosRequestConfig {
    _retry?: boolean
}

/**
 * Cliente Axios principal para comunicación con Laravel.
 *
 * Maneja:
 * - Cookies (Sanctum)
 * - CSRF
 * - Autenticación por sesión
 */
export const backendClient: AxiosInstance = axios.create({
    baseURL: BACKEND_BASE_URL,
    withCredentials: true,
    timeout: 10_000,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
})

/**
 * Solicita la cookie CSRF requerida por Sanctum.
 *
 * Laravel setea automáticamente `XSRF-TOKEN`.
 */
export async function getCsrfCookie(): Promise<void> {
    await backendClient.get('/sanctum/csrf-cookie')
}

/**
 * Limpia cualquier rastro de autenticación local.
 *
 * ⚠️ No borra cookies (eso lo hace el backend),
 * solo storage del frontend.
 */
export function clearAuthTokens(): void {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('user_data')
}

/**
 * Obtiene una cookie por nombre.
 *
 * @param name Nombre de la cookie
 */
function getCookie(name: string): string | null {
    const cookies = document.cookie?.split('; ') ?? []

    for (const cookie of cookies) {
        const [key, value] = cookie.split('=')
        if (key === name) {
            return decodeURIComponent(value)
        }
    }

    return null
}

/**
 * Determina si la request apunta a rutas de autenticación.
 *
 * Evita loops de redirección.
 */
function isAuthRoute(url?: string): boolean {
    if (!url) return false
    return url.includes('/auth') || url.includes('/login')
}

/* -------------------------------------------------------------------------- */
/*                               REQUEST INTERCEPTOR                          */
/* -------------------------------------------------------------------------- */

backendClient.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        const xsrfToken = getCookie('XSRF-TOKEN')

        if (xsrfToken) {
            config.headers['X-XSRF-TOKEN'] = xsrfToken
        }

        return config
    },
    (error: AxiosError) => Promise.reject(error)
)

/* -------------------------------------------------------------------------- */
/*                               RESPONSE INTERCEPTOR                         */
/* -------------------------------------------------------------------------- */

backendClient.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error: AxiosError) => {
        const config = error.config as RetryAxiosRequestConfig | undefined
        const status = error.response?.status

        if (
            status === 401 &&
            config &&
            !config._retry &&
            !isAuthRoute(config.url)
        ) {
            config._retry = true

            clearAuthTokens()

            // Redirección forzada a login
            window.location.href = '/auth/login'
        }

        return Promise.reject(error)
    }
)

/* -------------------------------------------------------------------------- */
/*                                   EXPORTS                                  */
/* -------------------------------------------------------------------------- */

export { axios, Method }
export default backendClient
