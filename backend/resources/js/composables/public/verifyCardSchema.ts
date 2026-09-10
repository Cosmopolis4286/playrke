import { z } from 'zod'

/**
 * Mismas reglas que VerifyCardRequest::rules() en el backend (serial
 * requerido, teléfono = exactamente 4 dígitos) — validar esto en el
 * frontend evita un viaje redondo (o una redirección inútil desde la
 * Landing) solo para descubrir un 422 por formato.
 *
 * El serial real lo genera BingoCardManagementService::generateSerialNumber()
 * como "BINGO-" + 13 caracteres hex en mayúsculas (ej. BINGO-A1B2C3D4E5F67) —
 * el charset [A-Z0-9-] se valida aquí; la máscara del input (SERIAL_MASK) ya
 * fuerza mayúsculas y descarta cualquier otro carácter al tipear.
 */
export const verifyCardSchema = z.object({
  serial_number: z
    .string({ error: 'Ingresa el serial de tu cartón' })
    .trim()
    .min(1, 'Ingresa el serial de tu cartón')
    .regex(/^[A-Z0-9-]+$/, 'El serial solo contiene letras, números y guiones'),
  phone_last4: z
    .string({ error: 'Ingresa los últimos 4 dígitos de tu teléfono' })
    .trim()
    .length(4, 'Deben ser exactamente 4 dígitos')
    .regex(/^\d{4}$/, 'Solo se permiten números'),
})

export type VerifyCardFormData = z.infer<typeof verifyCardSchema>

/** IMask: fuerza mayúsculas y descarta cualquier carácter fuera de [A-Z0-9-]. */
export const SERIAL_MASK = {
  mask: /^[A-Z0-9-]*$/,
  prepare: (value: string) => value.toUpperCase(),
}

/** IMask: exactamente 4 dígitos numéricos. */
export const PHONE_LAST4_MASK = '0000'
