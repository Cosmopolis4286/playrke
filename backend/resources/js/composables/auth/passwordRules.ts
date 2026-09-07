/**
 * Única fuente de verdad para las reglas de fuerza de contraseña, consumida
 * tanto por registerSchema.ts (validación Zod) como por
 * PasswordStrengthMeter.vue (feedback visual en vivo) — antes cada uno
 * mantenía su propio array con las mismas 5 reglas, con riesgo de
 * desincronizarse si una cambiaba sin la otra.
 */
export interface PasswordRule {
  test: (value: string) => boolean
  label: string
  message: string
}

export const PASSWORD_RULES: PasswordRule[] = [
  {
    test: (v: string) => v.length >= 8,
    label: 'Al menos 8 caracteres',
    message: 'La contraseña debe tener al menos 8 caracteres',
  },
  {
    test: (v: string) => /[A-Z]/.test(v),
    label: 'Una letra mayúscula',
    message: 'Debe contener al menos una letra mayúscula',
  },
  {
    test: (v: string) => /[a-z]/.test(v),
    label: 'Una letra minúscula',
    message: 'Debe contener al menos una letra minúscula',
  },
  {
    test: (v: string) => /\d/.test(v),
    label: 'Un número',
    message: 'Debe contener al menos un número',
  },
  {
    test: (v: string) => /[!@#$%^&*(),.?":{}|<>]/.test(v),
    label: 'Un carácter especial',
    message: 'Debe contener al menos un carácter especial',
  },
]
