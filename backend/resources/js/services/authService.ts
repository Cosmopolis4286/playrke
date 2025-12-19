import { apiClient as api, clearAuthTokens } from '@/plugins/axios';
import type { AxiosResponse } from 'axios';

// Tipos para las respuestas de la API
interface AuthResponse {
  success: boolean;
  message: string;
  data?: {
    user: any;
    token: string;
    refresh_token: string;
    expires_in: number;
    token_type: string;
  };
  errors?: Array<{
    field: string;
    message: string;
    code: string;
  }>;
}

interface UserResponse {
  success: boolean;
  data?: {
    user: any;
    roles: string[];
    permissions: string[];
  };
  errors?: any[];
}

// Datos para login
interface LoginData {
  login: string; // email o username
  password: string;
  remember_me?: boolean;
}

// Datos para registro
interface RegisterData {
  username: string;
  email: string;
  password: string;
  first_name?: string;
  last_name?: string;
  country_id: string;
  phone_number?: string;
  referral_code?: string;
}

// Datos para cambio de contraseña
interface ChangePasswordData {
  current_password: string;
  new_password: string;
  confirm_password: string;
}

/**
 * Servicio de autenticación para conectar con el backend Node.js
 */
export class AuthService {
  
  /**
   * Iniciar sesión
   */
  static async login(credentials: LoginData): Promise<AuthResponse> {
    try {
      const response: AxiosResponse<AuthResponse> = await api.post('/auth/login', credentials);
      
      if (response.data.success && response.data.data) {
        // Guardar tokens en localStorage
        localStorage.setItem('auth_token', response.data.data.token);
        localStorage.setItem('refresh_token', response.data.data.refresh_token);
        localStorage.setItem('user_data', JSON.stringify(response.data.data.user));
        
        // Dispara evento personalizado para notificar el login
        window.dispatchEvent(new CustomEvent('auth:login', {
          detail: response.data.data.user
        }));
      }
      
      return response.data;
    } catch (error: any) {
      return this.handleAuthError(error);
    }
  }

  /**
   * Registrar usuario
   */
  static async register(userData: RegisterData): Promise<AuthResponse> {
    try {
      const response: AxiosResponse<AuthResponse> = await api.post('/auth/register', userData);
      
      if (response.data.success && response.data.data) {
        // Guardar tokens en localStorage
        localStorage.setItem('auth_token', response.data.data.token);
        localStorage.setItem('refresh_token', response.data.data.refresh_token);
        localStorage.setItem('user_data', JSON.stringify(response.data.data.user));
        
        // Dispara evento personalizado
        window.dispatchEvent(new CustomEvent('auth:register', {
          detail: response.data.data.user
        }));
      }
      
      return response.data;
    } catch (error: any) {
      return this.handleAuthError(error);
    }
  }

  /**
   * Cerrar sesión
   */
  static async logout(): Promise<void> {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.warn('Error al cerrar sesión en el servidor:', error);
    } finally {
      // Limpiar datos locales
      clearAuthTokens();
      
      // Dispara evento personalizado
      window.dispatchEvent(new CustomEvent('auth:logout'));
    }
  }

  /**
   * Obtener perfil del usuario autenticado
   */
  static async getProfile(): Promise<UserResponse> {
    try {
      const response: AxiosResponse<UserResponse> = await api.get('/auth/me');
      
      if (response.data.success && response.data.data) {
        // Actualizar datos del usuario en localStorage
        localStorage.setItem('user_data', JSON.stringify(response.data.data.user));
      }
      
      return response.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        // Token inválido, limpiar y redirigir
        this.logout();
      }
      throw error;
    }
  }

  /**
   * Cambiar contraseña
   */
  static async changePassword(passwordData: ChangePasswordData): Promise<AuthResponse> {
    try {
      const response: AxiosResponse<AuthResponse> = await api.put('/auth/password', passwordData);
      return response.data;
    } catch (error: any) {
      return this.handleAuthError(error);
    }
  }

  /**
   * Verificar si el usuario está autenticado
   */
  static isAuthenticated(): boolean {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');
    return !!(token && userData);
  }

  /**
   * Obtener datos del usuario desde localStorage
   */
  static getUser(): any | null {
    const userData = localStorage.getItem('user_data');
    if (userData) {
      try {
        return JSON.parse(userData);
      } catch {
        return null;
      }
    }
    return null;
  }

  /**
   * Obtener token de autenticación
   */
  static getToken(): string | null {
    return localStorage.getItem('auth_token');
  }

  /**
   * Verificar si el usuario tiene un rol específico
   */
  static hasRole(role: string): boolean {
    const user = this.getUser();
    return user?.userRoles?.some((userRole: any) => userRole.role.name === role) || false;
  }

  /**
   * Verificar si el usuario tiene alguno de los roles especificados
   */
  static hasAnyRole(roles: string[]): boolean {
    return roles.some(role => this.hasRole(role));
  }

  /**
   * Manejar errores de autenticación
   */
  private static handleAuthError(error: any): AuthResponse {
    if (error.response?.data) {
      return error.response.data;
    }
    
    return {
      success: false,
      message: 'Error de conexión con el servidor',
      errors: [{
        field: 'network',
        message: 'No se pudo conectar con el servidor',
        code: 'NETWORK_ERROR'
      }]
    };
  }

  /**
   * Inicializar servicio de autenticación
   * Verifica token al cargar la aplicación
   */
  static async initialize(): Promise<void> {
    if (this.isAuthenticated()) {
      try {
        await this.getProfile();
      } catch (error) {
        console.warn('Error al verificar autenticación:', error);
        this.logout();
      }
    }
  }
}

// Exportar también para compatibilidad
export default AuthService;