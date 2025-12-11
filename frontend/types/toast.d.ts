/**
 * Toast System Type Definitions
 * Sistema de notificaciones basado en Nuxt UI Toast
 */

export interface ToastOptions {
  duration?: number
  [key: string]: any
}

export interface CustomToastOptions {
  type?: 'success' | 'error' | 'info' | 'warning'
  title?: string
  message: string
  duration?: number
  [key: string]: any
}

export interface ToastInstance {
  success(msg: string, title?: string, opts?: ToastOptions): void
  error(msg: string, title?: string, opts?: ToastOptions): void
  info(msg: string, title?: string, opts?: ToastOptions): void
  warning(msg: string, title?: string, opts?: ToastOptions): void
  custom(options: CustomToastOptions): void
}
