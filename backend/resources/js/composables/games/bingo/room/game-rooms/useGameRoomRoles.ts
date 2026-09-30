import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface UserRole {
  role?: { prefix?: string }
}

interface AuthPageProps extends Record<string, unknown> {
  auth?: { user?: { id?: string | number; userRoles?: UserRole[] } }
}

/**
 * Computed reactivos de permisos del usuario autenticado.
 * Lee `auth.user.userRoles` una sola vez y deriva todos los permisos.
 */
export function useGameRoomRoles() {
  const page = usePage<AuthPageProps>()

  const userRoles = computed<UserRole[]>(
    () => (page.props.auth?.user?.userRoles ?? []) as UserRole[]
  )

  const isAdmin = computed(() =>
    userRoles.value.some(ur =>
      ur.role?.prefix === 'admin' || ur.role?.prefix === 'superadmin'
    )
  )

  // Solo el jugador compra cartones para sí mismo (con su billetera). Comercios
  // y vendedores únicamente generan cartones para jugadores externos, desde el
  // punto de venta (Generar Cartón, con comprobante) — no desde las salas.
  const canBuy = computed(() =>
    userRoles.value.some(ur => ur.role?.prefix === 'player')
  )

  const isPlayer = computed(() =>
    userRoles.value.some(ur => ur.role?.prefix === 'player')
  )

  return { isAdmin, canBuy, isPlayer }
}
