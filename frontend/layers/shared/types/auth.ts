/**
 * Tipos TypeScript para el sistema de autenticación.
 *
 * Define las interfaces y tipos utilizados en todo el sistema
 * de autenticación, tanto para admin como para player.
 */

export interface User {
  id: string
  username: string
  first_name: string
  last_name: string
  full_name: string
  email: string

  avatar?: string | null
  country_code?: string | null

  role?: string
  roles: string[]

  verified: boolean

  permissions?: string[] // Opcional, tu backend no lo envía
  preferences?: UserPreferences

  status?: 'active' | 'inactive' | 'banned'

  email_verified_at?: string | null
  created_at?: string
  updated_at?: string
}


export interface UserPreferences {
  theme: 'light' | 'dark' | 'system'
  language: 'es' | 'en' | 'pt'
  notifications: {
    email: boolean
    push: boolean
    sounds: boolean
  }
  gaming: {
    autoPlay: boolean
    soundEffects: boolean
    animations: boolean
    maxCartones: number
  }
  chat: boolean
}

export interface LoginCredentials {
  login: string // Email o username
  password: string
  remember?: boolean
}

export interface RegisterData {
  first_name: string
  last_name: string
  username: string
  email: string
  password: string
  password_confirmation: string
  country_id?: number
  country?: string
  phone?: string
  terms_accepted?: boolean
  terms?: boolean  // Alias para compatibilidad con formularios
}

export interface AuthResponse {
  success: boolean
  message?: string
  token?: string
  user?: User
  expires_at?: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
}

export interface AdminUser extends User {
  admin_level: 'super' | 'admin' | 'moderator' | 'support'
  access_areas: string[]
  last_login_at?: string
  login_count: number
}

export interface PlayerUser extends User {
  balance: number
  level: number
  experience: number
  achievements: Achievement[]
  stats: PlayerStats
}

export interface Achievement {
  id: string
  name: string
  description: string
  icon: string
  earned_at: string
  progress?: number
  max_progress?: number
}

export interface PlayerStats {
  games_played: number
  games_won: number
  total_winnings: number
  favorite_room?: string
  playtime_hours: number
  win_rate: number
  longest_streak: number
}

export type UserRole =
  | 'super_admin'
  | 'admin'
  | 'moderator'
  | 'support'
  | 'player'
  | 'vip_player'
  | 'seller'
  | 'treasurer'

export type UserPermission =
  | 'admin.access'
  | 'admin.users.view'
  | 'admin.users.edit'
  | 'admin.games.config'
  | 'admin.reports.view'
  | 'player.play'
  | 'player.chat'
  | 'player.purchase'
  | 'seller.access'
  | 'treasurer.access'