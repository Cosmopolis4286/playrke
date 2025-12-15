import axios from 'axios';
import type { Method } from 'axios';

// Configuración para el backend Laravel
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

// Crear instancia de axios para el API
export const apiClient = axios.create({
    baseURL: API_BASE_URL,
    timeout: 10000,
    withCredentials: true, // Para enviar cookies de autenticación
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Interceptor para incluir el token CSRF en las solicitudes
apiClient.interceptors.request.use(
    (config) => {
        // Primero, obtener el token CSRF si es necesario
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // No necesitamos enviar el token Bearer con Sanctum, ya que las cookies de sesión manejarán la autenticación
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Interceptor para manejar respuestas y errores de autenticación
apiClient.interceptors.response.use(
    (response) => {
        // Si la respuesta incluye un nuevo token, guardarlo en localStorage (esto es opcional si usas Sanctum)
        if (response.data?.data?.token) {
            localStorage.setItem('auth_token', response.data.data.token);
        }

        return response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Si hay un error 401 y no se ha reintentado, intentar renovar el token
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            // Intentar renovar el token (si es necesario, si usas token de refresco)
            const refreshToken = localStorage.getItem('refresh_token');
            if (refreshToken) {
                try {
                    const response = await apiClient.post('/auth/refresh', {
                        refresh_token: refreshToken
                    });

                    const { token, refresh_token } = response.data.data;
                    localStorage.setItem('auth_token', token);
                    localStorage.setItem('refresh_token', refresh_token);

                    // Reintentar la solicitud original con el nuevo token
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                    return apiClient(originalRequest);

                } catch (refreshError) {
                    // Si falla el refresh, limpiar los tokens y redirigir a login
                    clearAuthTokens();
                    window.location.href = '/auth/login';
                    return Promise.reject(refreshError);
                }
            } else {
                // No hay refresh token, redirigir a login
                clearAuthTokens();
                window.location.href = '/auth/login';
            }
        }

        return Promise.reject(error);
    }
);

// Helper para obtener cookies
function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }
    return null;
}

// Helper para limpiar los tokens de autenticación (solo si estás usando refresh tokens)
function clearAuthTokens(): void {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user_data');
}

// Obtener el token CSRF antes de enviar solicitudes que necesiten autenticación
const getCsrfToken = async () => {
    try {
        // Llamada al endpoint de Sanctum para obtener el token CSRF
        await apiClient.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Error al obtener el token CSRF:', error);
    }
};

// Configuración legacy para compatibilidad con Laravel (si es necesario)
const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.timeout = 10000;

// Exportar ambas instancias
export { axios, apiClient as api, type Method, clearAuthTokens, getCsrfToken };
export default apiClient;
