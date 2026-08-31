import { z } from 'zod'

export const forgotPasswordSchema = z.object({
  email: z.email({ error: 'Ingresa un correo electrónico válido' }).trim().min(1, 'El correo es obligatorio'),
})

export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>
