export type DemoRole = 'admin' | 'commerce' | 'customer' | 'seller' | 'player'

export interface DemoCredential {
  id: string
  role: DemoRole
  email: string
  password: string
  label: string
  description: string
}

export interface DemoCredentialsResponse {
  success: boolean
  data: DemoCredential[]
}
