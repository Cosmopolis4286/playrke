// Global type definitions for PlayRKE Frontend

export {}

declare global {
  // Inertia.js page props
  interface PageProps {
    auth: {
      user: User | null
    }
    flash: {
      message?: string
      error?: string
      success?: string
      warning?: string
    }
    errors: Record<string, string>
    ziggy?: any
  }

  // User types
  interface User {
    id: string
    name: string
    email: string
    email_verified_at?: string
    avatar?: string
    balance?: number
    permissions?: string[]
    roles?: string[]
    created_at: string
    updated_at: string
  }

  // UI Component types
  interface Toast {
    id: string
    type: 'success' | 'error' | 'warning' | 'info'
    title?: string
    message: string
    duration?: number
    temporary?: boolean
  }

  interface Notification {
    id: string
    type: 'success' | 'error' | 'warning' | 'info' | 'payment' | 'game'
    title: string
    message: string
    read: boolean
    timestamp: Date
    action?: {
      label: string
      url?: string
      method?: 'GET' | 'POST'
    }
  }

  // Game types
  interface Game {
    id: string
    name: string
    type: 'bingo' | 'casino' | 'sports'
    result: 'win' | 'loss'
    amount: number
    time: string
  }

  interface GameRoom {
    id: string
    name: string
    type: 'bingo' | 'casino' | 'sports'
    players: number
    maxPlayers: number
    minBet: number
    maxBet: number
    status: 'waiting' | 'active' | 'finished'
  }

  // Transaction types
  interface Transaction {
    id: string
    type: 'deposit' | 'withdraw' | 'win' | 'loss' | 'bet'
    amount: number
    status: 'completed' | 'pending' | 'failed' | 'cancelled'
    method: string
    description?: string
    created_at: string
  }

  // Stats types
  interface UserStats {
    totalWinnings: number
    totalLosses: number
    gamesPlayed: number
    winRate: number
    favoriteGame: string
  }

  // Form types
  interface LoginForm {
    email: string
    password: string
    remember: boolean
  }

  interface RegisterForm {
    name: string
    email: string
    password: string
    password_confirmation: string
    terms: boolean
  }

  // API Response types
  interface ApiResponse<T = any> {
    data: T
    message?: string
    errors?: Record<string, string[]>
    meta?: {
      current_page: number
      last_page: number
      per_page: number
      total: number
    }
  }

  // Event types
  interface GameEvent {
    type: 'game_start' | 'game_end' | 'player_join' | 'player_leave' | 'bet_placed'
    data: any
    timestamp: Date
  }

  // Utility types
  type ButtonVariant = 'primary' | 'secondary' | 'accent' | 'outline' | 'ghost' | 'danger'
  type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  
  type CardVariant = 'default' | 'elevated' | 'outlined' | 'glass' | 'gradient'
  type CardSize = 'sm' | 'md' | 'lg'
  
  type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  type InputSize = 'sm' | 'md' | 'lg'
  type InputVariant = 'default' | 'filled' | 'outlined'

  type ModalSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full'

  type Theme = 'light' | 'dark'
  type Color = 'primary' | 'secondary' | 'success' | 'warning' | 'danger'

  // Animation types
  type AnimationType = 'fade' | 'slide' | 'scale' | 'bounce' | 'flip' | 'zoom'
  type EasingFunction = 'ease' | 'ease-in' | 'ease-out' | 'ease-in-out' | 'linear'

  // Laravel specific types
  interface LaravelError {
    message: string
    errors?: Record<string, string[]>
  }

  // Window extensions
  interface Window {
    Laravel?: {
      csrfToken: string
    }
    Echo?: any
    Pusher?: any
  }
}

// Vue specific augmentations
declare module 'vue' {
  interface ComponentCustomProperties {
    $route: any
    $router: any
    $formatNumber: (num: number) => string
    $formatCurrency: (num: number) => string
  }
}

// Inertia.js augmentation
declare module '@inertiajs/core' {
  interface PageProps extends PageProps {}
}