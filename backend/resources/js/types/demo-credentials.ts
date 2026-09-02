export type DemoRole = 'superadmin' | 'admin' | 'commerce' | 'subsidiary' | 'seller' | 'customer' | 'player'

export interface DemoCredential {
  id: string
  role: DemoRole
  email: string
  password: string
  label: string
  description: string
  /** Clase Font Awesome sin prefijo (ej. 'fa-store'), directo de Role.icon — igual que UserCreateRoleForm.vue. */
  icon: string | null
  /** Color de acento hex (ej. '#16a34a'), directo de Role.color. */
  color: string | null
  sort_order: number
}

export interface DemoCredentialsResponse {
  success: boolean
  data: DemoCredential[]
}
