import { api } from '@/plugins/axios';
import type { AxiosResponse } from 'axios';

// Tipos para el servicio de billeteras
export interface Currency {
  id: string;
  code: string;
  name: string;
  symbol: string;
  decimals: number;
}

export interface Wallet {
  id: string;
  userId: string;
  currency: Currency;
  balance: number;
  isActive: boolean;
  isPrimary: boolean;
  user: {
    id: string;
    username: string;
    email?: string;
  };
  createdAt: Date;
  updatedAt: Date;
}

export interface WalletBalance {
  walletId: string;
  balance: number;
  currency: Currency;
  lastUpdated: Date;
}

export interface Transaction {
  id: string;
  type: 'CREDIT' | 'DEBIT' | 'TRANSFER';
  amount: number;
  currency: Currency;
  description: string;
  status: 'PENDING' | 'COMPLETED' | 'FAILED' | 'CANCELLED';
  reference: string;
  metadata: Record<string, any>;
  createdAt: Date;
}

export interface TransactionHistory {
  walletId: string;
  transactions: Transaction[];
  total: number;
  page: number;
  totalPages: number;
}

export interface WalletSummary {
  totalWallets: number;
  totalBalance: number;
  activeCurrencies: string[];
  primaryWallet: Wallet | null;
  recentTransactions: Array<{
    id: string;
    type: string;
    amount: number;
    currency: string;
    description: string;
    createdAt: Date;
  }>;
}

export interface CreateWalletRequest {
  currencyId: string;
  initialBalance?: number;
  isPrimary?: boolean;
}

export interface FundsRequest {
  amount: number;
  description: string;
  metadata?: Record<string, any>;
}

export interface TransferRequest {
  fromWalletId: string;
  toWalletId: string;
  amount: number;
  description?: string;
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
 * Servicio para gestión de billeteras
 */
export class WalletService {
  
  /**
   * Crear nueva billetera
   */
  static async createWallet(walletData: CreateWalletRequest): Promise<ApiResponse<Wallet>> {
    try {
      const response: AxiosResponse<ApiResponse<Wallet>> = await api.post('/payments/wallets', walletData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener mis billeteras
   */
  static async getMyWallets(includeInactive = false): Promise<ApiResponse<Wallet[]>> {
    try {
      const response: AxiosResponse<ApiResponse<Wallet[]>> = await api.get('/payments/wallets', {
        params: { includeInactive }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener billetera por ID
   */
  static async getWalletById(walletId: string): Promise<ApiResponse<Wallet>> {
    try {
      const response: AxiosResponse<ApiResponse<Wallet>> = await api.get(`/payments/wallets/${walletId}`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener balance de billetera
   */
  static async getWalletBalance(walletId: string): Promise<ApiResponse<WalletBalance>> {
    try {
      const response: AxiosResponse<ApiResponse<WalletBalance>> = await api.get(`/payments/wallets/${walletId}/balance`);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Agregar fondos a billetera
   */
  static async addFunds(walletId: string, fundsData: FundsRequest): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/payments/wallets/${walletId}/add-funds`, fundsData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Retirar fondos de billetera
   */
  static async withdrawFunds(walletId: string, fundsData: FundsRequest): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post(`/payments/wallets/${walletId}/withdraw-funds`, fundsData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Transferir fondos entre billeteras
   */
  static async transferFunds(transferData: TransferRequest): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await api.post('/payments/wallets/transfer', transferData);
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener historial de transacciones
   */
  static async getTransactionHistory(walletId: string, limit = 20, offset = 0): Promise<ApiResponse<TransactionHistory>> {
    try {
      const response: AxiosResponse<ApiResponse<TransactionHistory>> = await api.get(`/payments/wallets/${walletId}/transactions`, {
        params: { limit, offset }
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Activar/desactivar billetera
   */
  static async toggleWalletStatus(walletId: string, isActive: boolean): Promise<ApiResponse<Wallet>> {
    try {
      const response: AxiosResponse<ApiResponse<Wallet>> = await api.put(`/payments/wallets/${walletId}/toggle-status`, {
        isActive
      });
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Obtener resumen de billeteras
   */
  static async getWalletSummary(): Promise<ApiResponse<WalletSummary>> {
    try {
      const response: AxiosResponse<ApiResponse<WalletSummary>> = await api.get('/payments/wallets/summary');
      return response.data;
    } catch (error: any) {
      return this.handleError(error);
    }
  }

  /**
   * Formatear cantidad con símbolo de moneda
   */
  static formatAmount(amount: number, currency: Currency): string {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: currency.code,
      minimumFractionDigits: currency.decimals,
      maximumFractionDigits: currency.decimals
    }).format(amount);
  }

  /**
   * Validar cantidad para transacción
   */
  static validateAmount(amount: number, balance?: number): {
    isValid: boolean;
    error?: string;
  } {
    if (amount <= 0) {
      return {
        isValid: false,
        error: 'El monto debe ser mayor a cero'
      };
    }

    if (balance !== undefined && amount > balance) {
      return {
        isValid: false,
        error: 'Saldo insuficiente'
      };
    }

    return { isValid: true };
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

export default WalletService;