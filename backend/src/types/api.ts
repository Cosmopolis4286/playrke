/**
 * Tipos para API - Request/Response schemas
 * Migrando desde Laravel Request classes y API responses
 */

import { z } from 'zod';
import type { 
  User, 
  UserStatus, 
  UserRole, 
  BingoRoom, 
  Transaction, 
  Wallet,
  PaymentMethod,
  Currency as CurrencyType
} from './database.js';

// Esquemas base para validación
const baseSchemas = {
  snowflakeId: z.string().min(1, 'ID requerido'),
  email: z.string().email('Email no válido'),
  password: z.string().min(8, 'Password debe tener al menos 8 caracteres'),
  username: z.string().min(3, 'Username debe tener al menos 3 caracteres').max(30),
  phone: z.string().regex(/^\+?[\d\s-()]{10,}$/, 'Número de teléfono no válido').optional(),
  url: z.string().url('URL no válida').optional(),
  positiveNumber: z.number().positive('Debe ser un número positivo'),
  nonNegativeNumber: z.number().min(0, 'No puede ser negativo'),
};

// === AUTH SCHEMAS ===

export const loginSchema = z.object({
  email: baseSchemas.email,
  password: z.string().min(1, 'Password requerido'),
  remember_me: z.boolean().optional()
});

export const registerSchema = z.object({
  username: baseSchemas.username,
  email: baseSchemas.email,
  password: baseSchemas.password,
  first_name: z.string().min(1).max(100).optional(),
  last_name: z.string().min(1).max(100).optional(),
  country_id: baseSchemas.snowflakeId,
  phone_number: baseSchemas.phone,
  referral_code: z.string().optional()
});

export const changePasswordSchema = z.object({
  current_password: z.string().min(1, 'Password actual requerido'),
  new_password: baseSchemas.password,
  confirm_password: z.string()
}).refine((data) => data.new_password === data.confirm_password, {
  message: "Las contraseñas no coinciden",
  path: ["confirm_password"]
});

// === USER SCHEMAS ===

export const updateUserSchema = z.object({
  first_name: z.string().max(100).optional(),
  last_name: z.string().max(100).optional(),
  phone_number: baseSchemas.phone,
  profile_photo_url: baseSchemas.url,
  time_zone_id: baseSchemas.snowflakeId.optional(),
  is_email_notifications_enabled: z.boolean().optional(),
  user_preferences: z.record(z.any()).optional()
});

export const updateUserStatusSchema = z.object({
  status: z.enum(['ACTIVE', 'INACTIVE', 'SUSPENDED', 'PENDING_VERIFICATION'])
});

// === GAME SCHEMAS ===

export const createBingoRoomSchema = z.object({
  name: z.string().min(1).max(100),
  max_players: z.number().int().min(2).max(1000),
  min_players: z.number().int().min(2),
  currency_id: baseSchemas.snowflakeId,
  start_time: z.string().datetime().optional(),
  prize_pool: baseSchemas.positiveNumber.optional(),
  config: z.record(z.any()).optional()
}).refine((data) => data.max_players >= data.min_players, {
  message: "max_players debe ser mayor o igual a min_players",
  path: ["max_players"]
});

export const joinBingoRoomSchema = z.object({
  carton_count: z.number().int().min(1).max(100),
  payment_method: z.enum(['wallet', 'pix', 'transbank', 'pagoMovil', 'binance']),
  auto_play: z.boolean().optional()
});

// === WALLET/PAYMENT SCHEMAS ===

export const depositSchema = z.object({
  amount: baseSchemas.positiveNumber,
  currency_id: baseSchemas.snowflakeId,
  payment_method: z.enum(['pix', 'transbank', 'pagoMovil', 'binance', 'manual']),
  payment_data: z.record(z.any()).optional()
});

export const withdrawSchema = z.object({
  amount: baseSchemas.positiveNumber,
  currency_id: baseSchemas.snowflakeId,
  destination: z.string().min(1),
  payment_method: z.enum(['pix', 'transbank', 'pagoMovil', 'binance']),
  withdrawal_data: z.record(z.any()).optional()
});

export const transferSchema = z.object({
  to_user_id: baseSchemas.snowflakeId,
  amount: baseSchemas.positiveNumber,
  currency_id: baseSchemas.snowflakeId,
  message: z.string().max(500).optional()
});

// === REQUEST/RESPONSE TYPES ===

// Auth types
export type LoginRequest = z.infer<typeof loginSchema>;
export type RegisterRequest = z.infer<typeof registerSchema>;
export type ChangePasswordRequest = z.infer<typeof changePasswordSchema>;

// User types  
export type UpdateUserRequest = z.infer<typeof updateUserSchema>;
export type UpdateUserStatusRequest = z.infer<typeof updateUserStatusSchema>;

// Game types
export type CreateBingoRoomRequest = z.infer<typeof createBingoRoomSchema>;
export type JoinBingoRoomRequest = z.infer<typeof joinBingoRoomSchema>;

// Payment types
export type DepositRequest = z.infer<typeof depositSchema>;
export type WithdrawRequest = z.infer<typeof withdrawSchema>;
export type TransferRequest = z.infer<typeof transferSchema>;

// Response types

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Array<{
    field: string;
    message: string;
    code: string;
  }>;
  meta?: {
    timestamp: string;
    request_id?: string;
    version?: string;
  };
}

export interface AuthResponse {
  user: Omit<User, 'password'>;
  token: string;
  expires_in: number;
  token_type: 'Bearer';
  refresh_token?: string;
}

export interface PaginatedApiResponse<T> extends ApiResponse<T[]> {
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    total_pages: number;
    has_next_page: boolean;
    has_prev_page: boolean;
  };
}

export interface UserProfile extends Omit<User, 'password'> {
  roles: UserRole[];
  permissions: string[];
  wallets: Wallet[];
  country: {
    id: string;
    name: string;
    iso_code: string;
  };
  time_zone?: {
    id: string;
    name: string;
    identifier: string;
    offset: string;
  };
}

export interface BingoRoomDetails extends BingoRoom {
  creator: {
    id: string;
    username: string;
    first_name?: string;
    last_name?: string;
  };
  currency: {
    id: string;
    symbol: string;
    short_name: string;
  };
  current_players: number;
  players: Array<{
    id: string;
    username: string;
    carton_count: number;
    joined_at: Date;
  }>;
  current_round?: {
    round_number: number;
    status: string;
    drawn_numbers: number[];
    start_time: Date;
  };
}

export interface TransactionDetails extends Transaction {
  user: {
    id: string;
    username: string;
    first_name?: string;
    last_name?: string;
  };
  currency: {
    id: string;
    symbol: string;
    short_name: string;
  };
  wallet?: {
    id: string;
    balance: number;
  };
}

// WebSocket event types
export interface WebSocketEvent<T = any> {
  type: string;
  data: T;
  timestamp: string;
  user_id?: string;
  room_id?: string;
}

export interface BingoNumberDrawnEvent {
  room_id: string;
  round_number: number;
  drawn_number: number;
  total_drawn: number;
  remaining_numbers: number[];
  timestamp: string;
}

export interface BingoGameStartEvent {
  room_id: string;
  round_number: number;
  start_time: string;
  players_count: number;
}

export interface BingoWinnerEvent {
  room_id: string;
  round_number: number;
  winner: {
    id: string;
    username: string;
    carton_numbers: number[];
  };
  prize_amount: number;
  winning_pattern: string;
  timestamp: string;
}