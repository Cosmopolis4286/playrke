import { z } from 'zod'

export const loginSchema = z.object({
  login: z.string({ error: 'Ingresa tu email o usuario' }).trim().min(1, 'Ingresa tu email o usuario'),
  password: z.string({ error: 'Ingresa tu contraseña' }).min(1, 'Ingresa tu contraseña'),
})

export type LoginFormData = z.infer<typeof loginSchema>
