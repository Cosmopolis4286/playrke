import { ref, computed } from 'vue'

export interface DemoCredential {
  id: string
  email: string
  password: string
  firstName: string
  lastName: string
  role: string
  description: string
}

/**
 * Composable para manejar credenciales de demo en desarrollo
 *
 * Proporciona acceso a usuarios de prueba para desarrollo:
 * - player@test.com / password123
 * - admin@test.com / admin123
 * - vip@test.com / vip123
 */
export function useDemoCredentials() {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Credenciales hardcodeadas que coinciden con TestUserSeeder
  const credentials = ref<DemoCredential[]>([
    {
      id: 'player-1',
      email: 'player@test.com',
      password: 'password123',
      firstName: 'Juan',
      lastName: 'Pérez',
      role: 'player',
      description: 'Jugador de prueba',
    },
    {
      id: 'admin-1',
      email: 'admin@test.com',
      password: 'admin123',
      firstName: 'Admin',
      lastName: 'Sistema',
      role: 'admin',
      description: 'Administrador del sistema',
    },
    {
      id: 'vip-1',
      email: 'vip@test.com',
      password: 'vip123',
      firstName: 'VIP',
      lastName: 'Player',
      role: 'player',
      description: 'Jugador VIP de prueba',
    },
  ])

  // Filtros por rol
  const adminCredentials = computed(() =>
    credentials.value.filter(c => c.role === 'admin')
  )

  const playerCredentials = computed(() =>
    credentials.value.filter(c => c.role === 'player')
  )

  // Para compatibilidad con componente existente
  const commerceCredentials = computed(() => [] as DemoCredential[])
  const customerCredentials = computed(() => [] as DemoCredential[])

  const totalCredentials = computed(() => credentials.value.length)
  const isAvailable = computed(() => process.env.NODE_ENV === 'development')

  const refresh = async () => {
    // No hace nada ya que las credenciales son estáticas
    // Función aquí para compatibilidad con la API
  }

  return {
    credentials,
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    adminCredentials,
    commerceCredentials,
    customerCredentials,
    playerCredentials,
    totalCredentials,
    isAvailable,
    refresh,
  }
}
