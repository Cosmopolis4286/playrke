import axios from 'axios';
import type { Method } from 'axios';

/**
 * URL base del backend (Laravel)
 * - API_BASE_URL apunta a /api
 * - BACKEND_BASE_URL se deja vacío para usar URLs relativas (mismo dominio)
 *
 * NOTA: Como la app Blade y el backend Laravel corren en el mismo servidor,
 * usamos URLs relativas sin baseURL para evitar problemas de CORS.
 */
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
const BACKEND_BASE_URL = ''; // Usar URLs relativas

/**
 * Cliente principal para comunicar el frontend con el backend Laravel.
 * Maneja:
 * - Cookies (Sanctum)
 * - CSRF
 * - Autenticación por sesión
 */
export const backendClient = axios.create({
    baseURL: BACKEND_BASE_URL,
    withCredentials: true,
    timeout: 10000,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

/**
 * Solicita la cookie CSRF necesaria para Sanctum (SPA auth).
 * Laravel responderá seteando la cookie XSRF-TOKEN.
 *
 * @returns {Promise<void>}
 */
export async function getCsrfCookie(): Promise<void> {
    try {
        await backendClient.get('/sanctum/csrf-cookie');
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
 *
 * @param {string} name
 * @returns {string | null}
 */
function getCookie(name: string): string | null {
    const cookies = document.cookie?.split('; ') ?? [];
    for (const cookie of cookies) {
        const [key, value] = cookie.split('=');
        if (key === name) return decodeURIComponent(value);
    }
    return null;
}

/**
 * Interceptor REQUEST
 * Inyecta automáticamente el token CSRF en cada request.
 *
 * ⚠️ Importante:
 * No tipar manualmente `config` para evitar conflictos
 * con `InternalAxiosRequestConfig` de Axios v1+
 */
backendClient.interceptors.request.use(
    (config) => {
        const xsrfToken = getCookie('XSRF-TOKEN');

        if (xsrfToken) {
            config.headers.set('X-XSRF-TOKEN', xsrfToken);
        }

        // Log de debug para detectar llamadas duplicadas
        if (import.meta.env.DEV && config.url?.includes('/api/auth/')) {
            console.log(`📤 Request: ${config.method?.toUpperCase()} ${config.url}`, {
                retry: config._retry,
                timestamp: new Date().toISOString()
            });
        }

        return config;
    },
    (error) => Promise.reject(error)
);

/**
 * Interceptor RESPONSE
 * Maneja errores globales de autenticación.
 */
backendClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const originalRequest = error.config;

        // NO reintentar rutas de autenticación para evitar duplicaciones
        const isAuthRoute = originalRequest?.url?.includes('/api/auth/');

        if (error.response?.status === 401 && !originalRequest._retry && !isAuthRoute) {
            originalRequest._retry = true;
            clearAuthTokens();
            window.location.href = '/auth/login';
        }

        return Promise.reject(error);
    }
);

// Exportaciones adicionales
export { axios, Method };

export default backendClient;
