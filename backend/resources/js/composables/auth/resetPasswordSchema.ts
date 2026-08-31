import { z } from 'zod'

export const resetPasswordSchema = z.object({
  token: z.string().min(1),
  email: z.email().trim().min(1),
  password: z
    .string({ error: 'La contraseña es obligatoria' })
    .min(8, 'La contraseña debe tener al menos 8 caracteres')
    .regex(/[A-Z]/, 'Debe contener al menos una letra mayúscula')
    .regex(/[a-z]/, 'Debe contener al menos una letra minúscula')
    .regex(/\d/, 'Debe contener al menos un número')
    .regex(/[!@#$%^&*(),.?":{}|<>]/, 'Debe contener al menos un carácter especial'),
  password_confirmation: z.string({ error: 'Debe repetir la contraseña' }),
}).refine(data => data.password === data.password_confirmation, {
  message: 'Las contraseñas no coinciden',
  path: ['password_confirmation'],
})

export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>
