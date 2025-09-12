/**
 * Utilidades matemáticas - Migradas desde Laravel helpers/math.php
 */

/**
 * Truncar un número sin redondear
 * @param number - Número a truncar
 * @param decimals - Número de decimales (default: 2)
 * @returns Número truncado
 */
export function truncate(number: number, decimals: number = 2): number {
  const factor = Math.pow(10, decimals);
  return Math.floor(number * factor) / factor;
}

/**
 * Calcular porcentaje
 * @param value - Valor
 * @param total - Total
 * @returns Porcentaje calculado
 */
export function percent(value: number, total: number): number {
  return total === 0 ? 0 : (value / total) * 100;
}

/**
 * Redondear a N decimales
 * @param number - Número a redondear
 * @param decimals - Número de decimales
 * @returns Número redondeado
 */
export function roundTo(number: number, decimals: number = 2): number {
  return Number(Math.round(Number(number + 'e' + decimals)) + 'e-' + decimals);
}

/**
 * Formatear número como moneda
 * @param amount - Cantidad
 * @param currency - Código de moneda (USD, EUR, COP, etc.)
 * @param locale - Locale (default: es-ES)
 * @returns Número formateado como moneda
 */
export function formatCurrency(
  amount: number, 
  currency: string = 'USD', 
  locale: string = 'es-ES'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
}

/**
 * Generar número aleatorio entre min y max
 * @param min - Valor mínimo
 * @param max - Valor máximo
 * @returns Número aleatorio
 */
export function randomBetween(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}