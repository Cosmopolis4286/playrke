import type { Ref, ComputedRef } from 'vue'
import type { DemoCredential } from './useDemoCredentials'

/**
 * Helpers para DemoCredentials component
 */

export function copyCredentials(
  role: string,
  currentAdminCredentials: ComputedRef<DemoCredential | null>,
  currentCommerceCredentials: ComputedRef<DemoCredential | null>,
  currentCustomerCredentials: ComputedRef<DemoCredential | null>,
  currentPlayerCredentials: ComputedRef<DemoCredential | null>,
  copyFeedback: Record<string, string>
) {
  const credentialsMap: Record<string, ComputedRef<DemoCredential | null>> = {
    admin: currentAdminCredentials,
    commerce: currentCommerceCredentials,
    customer: currentCustomerCredentials,
    player: currentPlayerCredentials,
  }

  const cred = credentialsMap[role]?.value
  if (!cred) return

  const text = `Email: ${cred.email}\nPassword: ${cred.password}`
  navigator.clipboard.writeText(text)
  copyFeedback[role] = 'Copiado!'
  setTimeout(() => {
    copyFeedback[role] = ''
  }, 2000)
}

export function copyAllCredentials(
  currentAdminCredentials: ComputedRef<DemoCredential | null>,
  currentCommerceCredentials: ComputedRef<DemoCredential | null>,
  currentCustomerCredentials: ComputedRef<DemoCredential | null>,
  playerCredentials: ComputedRef<DemoCredential[]>,
  copyFeedback: Record<string, string>
) {
  const allCreds: string[] = []

  if (currentAdminCredentials.value) {
    allCreds.push(`Admin: ${currentAdminCredentials.value.email} / ${currentAdminCredentials.value.password}`)
  }
  if (currentCommerceCredentials.value) {
    allCreds.push(`Commerce: ${currentCommerceCredentials.value.email} / ${currentCommerceCredentials.value.password}`)
  }
  if (currentCustomerCredentials.value) {
    allCreds.push(`Customer: ${currentCustomerCredentials.value.email} / ${currentCustomerCredentials.value.password}`)
  }
  playerCredentials.value.forEach((player) => {
    allCreds.push(`Player (${player.firstName}): ${player.email} / ${player.password}`)
  })

  navigator.clipboard.writeText(allCreds.join('\n'))
  copyFeedback.all = 'Todas copiadas!'
  setTimeout(() => {
    copyFeedback.all = ''
  }, 2000)
}

export function getDashboardRouteByRole(role?: string): string {
  if (!role) return '/player/lobby'

  const roleRoutes: Record<string, string> = {
    admin: '/admin/dashboard',
    player: '/player/lobby',
    commerce: '/commerce/dashboard',
    customer: '/customer/dashboard',
  }

  return roleRoutes[role] || '/player/lobby'
}

export function initStats(usageCount: Ref<number>, lastUsedRole: Ref<string>) {
  if (import.meta.client) {
    const savedCount = localStorage.getItem('demoCredentials:usageCount')
    const savedRole = localStorage.getItem('demoCredentials:lastUsedRole')

    if (savedCount) usageCount.value = Number.parseInt(savedCount, 10)
    if (savedRole) lastUsedRole.value = savedRole
  }
}

export function saveStats(role: string, usageCount: Ref<number>, lastUsedRole: Ref<string>) {
  usageCount.value++
  lastUsedRole.value = role

  if (import.meta.client) {
    localStorage.setItem('demoCredentials:usageCount', String(usageCount.value))
    localStorage.setItem('demoCredentials:lastUsedRole', role)
  }
}
