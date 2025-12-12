/**
 * Tipos para autenticación y usuarios
 */

export interface User {
  id: string | number
  username: string
  email: string
  first_name?: string
  last_name?: string
  phone?: string
  country?: string
  roles: string[]
  permissions: string[]
  status?: 'active' | 'inactive' | 'banned'
  email_verified_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface LoginCredentials {
  username: string
  password: string
  remember?: boolean
}

export interface RegisterData {
  username: string
  email: string
  password: string
  password_confirmation: string
  first_name?: string
  last_name?: string
  phone?: string
  country?: string
  terms_accepted?: boolean
}

export interface AuthResponse {
  success: boolean
  message?: string
  token?: string
  user?: User
  errors?: Record<string, string[]>
}
