/**
 * Tipos de base de datos - Migrados desde Laravel models y migrations
 * Representa la estructura de las tablas principales
 */

// Tipo base para entidades con Snowflake ID
export interface BaseEntity {
  id: string; // Snowflake ID
  created_at: Date;
  updated_at: Date;
  deleted_at?: Date | null;
}

// Enums principales
export type UserStatus = 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'PENDING_VERIFICATION';
export type UserRole = 'admin' | 'customer' | 'seller' | 'treasurer' | 'commerce';
export type GameStatus = 'pending' | 'active' | 'finished' | 'cancelled';
export type RoundStatus = 'waiting' | 'in_progress' | 'completed';
export type PaymentMethod = 'pix' | 'transbank' | 'pagoMovil' | 'binance' | 'manual';
export type Currency = 'USD' | 'COP' | 'VES' | 'BRL' | 'EUR';

// Usuario base (tabla users)
export interface User extends BaseEntity {
  username: string;
  first_name?: string | null;
  last_name?: string | null;
  country_id: string;
  time_zone_id?: string | null;
  profile_photo_url?: string | null;
  phone_number?: string | null;
  email: string;
  email_verified_at?: Date | null;
  password: string;
  remember_token?: string | null;
  referral_code: string;
  referred_by_user_id?: string | null;
  status: UserStatus;
  last_login_at?: Date | null;
  last_login_ip?: string | null;
  is_email_notifications_enabled: boolean;
  is_two_factor_enabled: boolean;
  user_preferences?: Record<string, any> | null;
}

// País (tabla countries)
export interface Country extends BaseEntity {
  name: string;
  iso_code: string;
  phone_code?: string | null;
  is_active: boolean;
}

// Moneda (tabla currencies)
export interface CurrencyEntity extends BaseEntity {
  symbol: string;
  long_name: string;
  short_name: string;
  country_id: string;
  exchange_rate?: number | null;
  is_active: boolean;
}

// Zona horaria (tabla time_zones)
export interface TimeZone extends BaseEntity {
  name: string;
  identifier: string; // ej: "America/Sao_Paulo"
  offset: string; // ej: "-03:00"
  is_active: boolean;
}

// Juego (tabla games)
export interface Game extends BaseEntity {
  name: string;
  description?: string | null;
  status: GameStatus;
  config?: Record<string, any> | null;
}

// Sala de bingo (tabla bingo_rooms)
export interface BingoRoom extends BaseEntity {
  name: string;
  game_id: string;
  creator_id: string;
  max_players: number;
  min_players: number;
  status: GameStatus;
  start_time?: Date | null;
  end_time?: Date | null;
  prize_pool?: number | null;
  currency_id: string;
  config?: Record<string, any> | null;
}

// Ronda de bingo (tabla bingo_rounds)
export interface BingoRound extends BaseEntity {
  room_id: string;
  round_number: number;
  status: RoundStatus;
  start_time?: Date | null;
  end_time?: Date | null;
  drawn_numbers?: number[] | null;
  winner_user_id?: string | null;
  prize_amount?: number | null;
}

// Usuario en sala de bingo (tabla bingo_room_users)
export interface BingoRoomUser extends BaseEntity {
  room_id: string;
  user_id: string;
  carton_numbers?: number[] | null;
  joined_at: Date;
  status: 'active' | 'inactive' | 'winner';
  total_paid?: number | null;
}

// Billetera (tabla wallets)
export interface Wallet extends BaseEntity {
  user_id: string;
  currency_id: string;
  balance: number;
  locked_balance: number;
  last_transaction_at?: Date | null;
}

// Transacción (tabla transactions)
export interface Transaction extends BaseEntity {
  user_id: string;
  wallet_id?: string | null;
  type: 'deposit' | 'withdrawal' | 'game_bet' | 'game_win' | 'transfer' | 'fee';
  amount: number;
  currency_id: string;
  reference_id?: string | null;
  reference_type?: string | null;
  payment_method?: PaymentMethod | null;
  status: 'pending' | 'completed' | 'failed' | 'cancelled';
  metadata?: Record<string, any> | null;
  processed_at?: Date | null;
}

// Cliente (tabla customers)
export interface Customer extends BaseEntity {
  user_id: string;
  document_type_id?: string | null;
  document_number?: string | null;
  birth_date?: Date | null;
  address?: string | null;
  is_verified: boolean;
  verification_level: number;
  kyc_data?: Record<string, any> | null;
}

// Comercio (tabla commerces)
export interface Commerce extends BaseEntity {
  user_id: string;
  business_name: string;
  business_type?: string | null;
  tax_id?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
  is_verified: boolean;
  commission_rate?: number | null;
  settings?: Record<string, any> | null;
}

// Vendedor (tabla sellers)
export interface Seller extends BaseEntity {
  user_id: string;
  commerce_id?: string | null;
  subsidiary_id?: string | null;
  employee_code?: string | null;
  commission_rate?: number | null;
  sales_limit?: number | null;
  is_active: boolean;
  settings?: Record<string, any> | null;
}

// Tesorero (tabla treasurers)
export interface Treasurer extends BaseEntity {
  user_id: string;
  countries_allowed?: string[] | null;
  currencies_allowed?: string[] | null;
  daily_limit?: number | null;
  monthly_limit?: number | null;
  is_active: boolean;
  settings?: Record<string, any> | null;
}

// Configuración global (tabla settings)
export interface Settings extends BaseEntity {
  key: string;
  value: string;
  type: 'string' | 'number' | 'boolean' | 'json';
  description?: string | null;
  is_public: boolean;
}

// Tipos de utilidad para consultas

// Respuesta paginada
export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    total_pages: number;
    has_next_page: boolean;
    has_prev_page: boolean;
  };
}

// Filtros comunes
export interface BaseFilters {
  page?: number;
  limit?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  created_from?: string;
  created_to?: string;
}

// Filtros de usuario
export interface UserFilters extends BaseFilters {
  status?: UserStatus;
  country_id?: string;
  has_verified_email?: boolean;
  has_two_factor?: boolean;
  search?: string; // Para buscar por username, email, nombres
}

// Filtros de transacciones
export interface TransactionFilters extends BaseFilters {
  user_id?: string;
  type?: Transaction['type'];
  status?: Transaction['status'];
  currency_id?: string;
  amount_from?: number;
  amount_to?: number;
  payment_method?: PaymentMethod;
}