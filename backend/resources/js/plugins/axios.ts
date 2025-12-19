import axios from 'axios';
import type { Method } from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
const WEB_BASE_URL = API_BASE_URL.replace(/\/api\/?$/, '');

/**
 * Instancia Axios para llamadas a API REST.
 * Configurada con baseURL, timeout, y envío de cookies.
 */
export const apiClient = axios.create({
    baseURL: API_BASE_URL,
    timeout: 10000,
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

/**
 * Instancia Axios para rutas web (sin /api), usada para CSRF, login, logout.
 */
export const webClient = axios.create({
    baseURL: WEB_BASE_URL,
    timeout: 10000,
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

/**
 * Obtiene el token CSRF desde Laravel Sanctum.
 * Llama a la ruta `/sanctum/csrf-cookie` para establecer la cookie CSRF.
 * @returns {Promise<void>}
 */
export async function getCsrfToken(): Promise<void> {
    try {
        await webClient.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Error al obtener el token CSRF:', error);
        throw error;
    }
}

/**
 * Elimina tokens y datos de autenticación almacenados en localStorage.
 */
export function clearAuthTokens(): void {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user_data');
}

/**
 * Obtiene el valor de una cookie por nombre.
 * @param {string} name - Nombre de la cookie.
 * @returns {string|null} Valor de la cookie o null si no existe.
 */
export function getCookie(name: string): string | null {
    const cookies = document.cookie ? document.cookie.split('; ') : [];
    for (const cookie of cookies) {
        const [key, value] = cookie.split('=');
        if (key === name) return decodeURIComponent(value);
    }
    return null;
}

/**
 * Interceptor para agregar el token CSRF automáticamente a las solicitudes del apiClient.
 */
apiClient.interceptors.request.use(
    (config) => {
        const xsrfToken = getCookie('XSRF-TOKEN');
        if (xsrfToken) {
            config.headers['X-XSRF-TOKEN'] = xsrfToken;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

/**
 * Interceptor para manejar respuestas, principalmente errores 401 (no autorizado).
 */
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const originalRequest = error.config;
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            clearAuthTokens();
            window.location.href = '/auth/login';
        }
        return Promise.reject(error);
    }
);

// Exportaciones adicionales
export { axios, Method };

// Exportación por defecto para importación sencilla
export default apiClient;
