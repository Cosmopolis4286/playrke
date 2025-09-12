import { z } from 'zod';

/**
 * Utilidades de validación usando Zod - Equivalente a las Request validations de Laravel
 */

// Esquemas base reutilizables
export const baseSchemas = {
  email: z.string().email('Email no válido'),
  password: z.string().min(8, 'Password debe tener al menos 8 caracteres'),
  phone: z.string().regex(/^\+?[\d\s-()]{10,}$/, 'Número de teléfono no válido'),
  uuid: z.string().uuid('UUID no válido'),
  positiveNumber: z.number().positive('Debe ser un número positivo'),
  nonNegativeNumber: z.number().min(0, 'No puede ser negativo'),
  dateString: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Formato de fecha no válido (YYYY-MM-DD)'),
  timeString: z.string().regex(/^\d{2}:\d{2}(:\d{2})?$/, 'Formato de hora no válido (HH:MM o HH:MM:SS)')
};

// Validaciones específicas del negocio
export const gameSchemas = {
  bingoRoomId: z.string().min(1, 'ID de sala requerido'),
  cartonNumber: z.number().int().min(1).max(999999, 'Número de cartón no válido'),
  gameStatus: z.enum(['pending', 'active', 'finished', 'cancelled']),
  roundStatus: z.enum(['waiting', 'in_progress', 'completed'])
};

export const paymentSchemas = {
  amount: z.number().positive('Monto debe ser positivo').max(1000000, 'Monto demasiado alto'),
  currency: z.enum(['USD', 'COP', 'VES', 'BRL', 'EUR']),
  paymentMethod: z.enum(['pix', 'transbank', 'pagoMovil', 'binance', 'manual']),
  cryptoAddress: z.string().min(20, 'Dirección crypto no válida')
};

export const userSchemas = {
  role: z.enum(['admin', 'customer', 'seller', 'treasurer', 'commerce']),
  userStatus: z.enum(['active', 'inactive', 'suspended', 'banned'])
};

// Función helper para validar datos
export function validateData<T>(schema: z.ZodSchema<T>, data: unknown): T {
  try {
    return schema.parse(data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      const formattedErrors = error.errors.map(err => ({
        field: err.path.join('.'),
        message: err.message,
        code: err.code
      }));
      
      throw new ValidationError('Datos de entrada no válidos', formattedErrors);
    }
    throw error;
  }
}

// Función helper para validar datos parciales (para updates)
export function validatePartialData<T>(schema: z.ZodSchema<T>, data: unknown): Partial<T> {
  return validateData(schema.partial(), data);
}

// Clase de error personalizada para validaciones
export class ValidationError extends Error {
  public readonly errors: Array<{
    field: string;
    message: string;
    code: string;
  }>;
  
  constructor(message: string, errors: Array<{ field: string; message: string; code: string }>) {
    super(message);
    this.name = 'ValidationError';
    this.errors = errors;
  }
}

// Validadores específicos para tipos comunes

/**
 * Valida si una cadena es un email válido
 */
export function isValidEmail(email: string): boolean {
  return baseSchemas.email.safeParse(email).success;
}

/**
 * Valida si una cadena es una contraseña segura
 */
export function isValidPassword(password: string): boolean {
  const strongPasswordSchema = z.string()
    .min(8, 'Mínimo 8 caracteres')
    .regex(/[A-Z]/, 'Debe contener al menos una mayúscula')
    .regex(/[a-z]/, 'Debe contener al menos una minúscula')
    .regex(/[0-9]/, 'Debe contener al menos un número')
    .regex(/[^A-Za-z0-9]/, 'Debe contener al menos un carácter especial');
    
  return strongPasswordSchema.safeParse(password).success;
}

/**
 * Sanitiza una cadena para prevenir XSS básico
 */
export function sanitizeString(input: string): string {
  return input
    .replace(/[<>]/g, '') // Elimina < y >
    .replace(/javascript:/gi, '') // Elimina javascript:
    .replace(/on\w+=/gi, '') // Elimina event handlers
    .trim();
}

/**
 * Valida rango de números para paginación
 */
export function validatePagination(page?: string | number, limit?: string | number) {
  const pageNum = typeof page === 'string' ? parseInt(page) : page || 1;
  const limitNum = typeof limit === 'string' ? parseInt(limit) : limit || 10;
  
  return {
    page: Math.max(1, pageNum),
    limit: Math.min(100, Math.max(1, limitNum)) // Max 100 items per page
  };
}