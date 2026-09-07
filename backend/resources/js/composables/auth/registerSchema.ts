import { z } from 'zod'
import { PHONE_CONFIG } from '@js/composables/form/usePhoneField'
import { PASSWORD_RULES } from './passwordRules'

/**
 * Schema Zod del formulario de registro. El patrón de "phone" se resuelve en
 * runtime según el país elegido (usePhoneField/PHONE_CONFIG) — sin país
 * seleccionado o sin config para ese ISO-2, el teléfono queda como opcional
 * en el schema (superRefine se encarga de exigirlo solo si el país sí tiene
 * config y el usuario ingresó algo).
 */
export function buildRegisterSchema(isoCode2: string | null | undefined) {
  const phoneConfig = isoCode2 ? PHONE_CONFIG[isoCode2.toUpperCase()] : undefined

  return z.object({
    email: z.email({ error: 'El E-mail no es válido' }).trim().min(1, 'El E-mail es obligatorio'),
    username: z
      .string({ error: 'El nombre de usuario es obligatorio' })
      .trim()
      .min(3, 'Debe tener al menos 3 caracteres')
      .max(30, 'Máximo 30 caracteres'),
    country: z.union([z.string(), z.number()]).refine(
      v => v !== '' && v !== null && v !== undefined,
      'Debe seleccionar un país'
    ),
    phone: z
      .string()
      .trim()
      .optional()
      .default('')
      .refine(
        v => !v || !phoneConfig || phoneConfig.pattern.test(v),
        phoneConfig ? `Formato inválido. ${phoneConfig.hint}` : 'Formato de teléfono inválido'
      ),
    password: z
      .string({ error: 'La contraseña es obligatoria' })
      .superRefine((value, ctx) => {
        for (const rule of PASSWORD_RULES) {
          if (!rule.test(value)) {
            ctx.addIssue({ code: 'custom', message: rule.message })
          }
        }
      }),
    password_confirmation: z.string({ error: 'Debe repetir la contraseña' }),
    terms: z.literal(true, { error: 'Debe aceptar los términos y condiciones' }),
  }).refine(data => data.password === data.password_confirmation, {
    message: 'Las contraseñas no coinciden',
    path: ['password_confirmation'],
  })
}

export type RegisterFormData = z.infer<ReturnType<typeof buildRegisterSchema>>
