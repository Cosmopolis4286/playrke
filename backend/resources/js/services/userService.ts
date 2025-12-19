import { apiClient as api } from '@/plugins/axios';
import type { AxiosResponse } from 'axios';

// Tipos para el servicio de usuarios
export interface UserProfile {
  id: string;
  username: string;
  email: string;
  firstName: string;
  lastName: string;
  fullName: string;
  phoneNumber?: string;
  profilePhotoUrl?: string;
  status: string;
  emailVerifiedAt?: Date;
  country?: {
    id: string;
    name: string;
    code: string;
    flag?: string;
  };
  timeZone?: {
    id: string;
    name: string;
    offset: string;
    abbreviation: string;
  };
  referralCode: string;
  roles: string[];
  createdAt: Date;
  updatedAt: Date;
}

export interface UserSettings {
  language: string;
  currency: string;
  timezone: string;
  emailNotifications: boolean;
  smsNotifications: boolean;
  pushNotifications: boolean;
  marketingEmails: boolean;
  twoFactorEnabled: boolean;
}

export interface UserStats {
  totalGamesPlayed: number;
  totalWinnings: number;
  totalDeposits: number;
  totalWithdrawals: number;
  accountAge: number;
  lastActivity: Date;
  favoriteGames: string[];
}

export interface ReferralInfo {
  code: string;
  totalReferrals: number;
  activeReferrals: number;
  totalEarnings: number;
  referrals: Array<{
    id: string;
    username: string;
    joinedAt: Date;
    isActive: boolean;
    earnings: number;
  }>;
}

export interface VerificationStatus {
  email: boolean;
  phone: boolean;
  identity: boolean;
  address: boolean;
  paymentMethod: boolean;
  completionLevel: number;
}

export interface UpdateProfileRequest {
  firstName?: string;
  lastName?: string;
  phoneNumber?: string;
  countryId?: string;
  timeZoneId?: string;
  profilePhotoUrl?: string;
}

export interface ChangePasswordRequest {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message: string;
  errors?: Array<{
    field: string;
    message: string;
    code: string;
  }>;
}

/**
 * Servicio para gestión de usuarios
 */
export class UserService {
  
  /**
   * Obtener mi perfil completo
   */
  static async getMyProfile(includeProfile = false): Promise<ApiResponse<UserProfile>> {
    try {
      const response: AxiosResponse<ApiResponse<UserProfile>> = await api.get('/users/me', {
        params: { includeProfile }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Actualizar mi perfil
   */
  static async updateProfile(profileData: UpdateProfileRequest): Promise<ApiResponse<UserProfile>> {
    try {
      const response: AxiosResponse<ApiResponse<UserProfile>> = await api.put('/users/me/profile', profileData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Cambiar contraseña
   */
  static async changePassword(passwordData: ChangePasswordRequest): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.put('/users/me/password', passwordData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Actualizar configuraciones
   */
  static async updateSettings(settings: Partial<UserSettings>): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.put('/users/me/settings', settings);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener información de referidos
   */
  static async getReferrals(): Promise<ApiResponse<ReferralInfo>> {
    try {
      const response: AxiosResponse<ApiResponse<ReferralInfo>> = await api.get('/users/me/referrals');
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener estado de verificación
   */
  static async getVerificationStatus(): Promise<ApiResponse<VerificationStatus>> {
    try {
      const response: AxiosResponse<ApiResponse<VerificationStatus>> = await api.get('/users/me/verification-status');
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Eliminar mi cuenta
   */
  static async deleteAccount(reason?: string): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.delete('/users/me', {
        data: { reason }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Subir foto de perfil
   */
  static async uploadProfilePhoto(file: File): Promise<ApiResponse<{ url: string }>> {
    try {
      const formData = new FormData();
      formData.append('photo', file);

      const response: AxiosResponse<ApiResponse<{ url: string }>> = await api.post('/users/me/photo', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Manejar errores de la API
   */
  private static handleError(error: any): ApiResponse {
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
}

export default UserService;