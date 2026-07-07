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

  const canBuy = computed(() =>
    userRoles.value.some(ur =>
      ['commerce', 'seller', 'player'].includes(ur.role?.prefix ?? '')
    )
  )

  const isPlayer = computed(() =>
    userRoles.value.some(ur => ur.role?.prefix === 'player')
  )

  return { isAdmin, canBuy, isPlayer }
}
