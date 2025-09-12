import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

/**
 * Utilidades de tiempo - Migradas desde frontend timeFunctions.ts
 */

/**
 * Obtiene el desplazamiento GMT en formato ±HH:MM
 * @param date - Fecha específica (opcional)
 * @returns Formato GMT offset como "+02:00" o "-05:30"
 */
export function getGMTOffset(date?: Date): string {
  const targetDate = date || new Date();
  const offset = targetDate.getTimezoneOffset(); // en minutos, positivo para zonas detrás de GMT
  const sign = offset > 0 ? "-" : "+";
  const absOffset = Math.abs(offset);
  const hours = String(Math.floor(absOffset / 60)).padStart(2, "0");
  const minutes = String(absOffset % 60).padStart(2, "0");
  return `${sign}${hours}:${minutes}`;
}

/**
 * Convierte timestamp Unix a fecha formateada
 * @param unixTimestamp - Timestamp Unix en segundos
 * @param showSeconds - Incluir segundos en el formato
 * @param locale - Locale para formato (default: 'es-ES')
 * @returns Fecha y hora formateadas
 */
export function formatUnixTimestamp(
  unixTimestamp: number | string,
  showSeconds: boolean = false,
  locale: string = 'es-ES'
): string | undefined {
  if (!unixTimestamp) return undefined;

  const timestamp = typeof unixTimestamp === "string" 
    ? parseInt(unixTimestamp) 
    : unixTimestamp;
  
  const date = new Date(timestamp * 1000);

  const dateOptions: Intl.DateTimeFormatOptions = {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  };

  const timeOptions: Intl.DateTimeFormatOptions = {
    hour: "2-digit",
    minute: "2-digit",
  };
  
  if (showSeconds) {
    timeOptions.second = "2-digit";
  }

  const formattedDate = date.toLocaleDateString(locale, dateOptions);
  const formattedTime = date.toLocaleTimeString(locale, timeOptions);

  return `${formattedDate} - ${formattedTime}`;
}

/**
 * Obtiene timestamp Unix actual
 * @returns Timestamp Unix en segundos
 */
export function getCurrentUnixTimestamp(): number {
  return Math.floor(Date.now() / 1000);
}

/**
 * Convierte fecha ISO string a timestamp Unix
 * @param isoString - Fecha en formato ISO
 * @returns Timestamp Unix en segundos
 */
export function isoToUnixTimestamp(isoString: string): number {
  return Math.floor(new Date(isoString).getTime() / 1000);
}

/**
 * Añadir tiempo a una fecha
 * @param date - Fecha base
 * @param amount - Cantidad
 * @param unit - Unidad ('minutes', 'hours', 'days', etc.)
 * @returns Nueva fecha
 */
export function addTime(
  date: Date | string, 
  amount: number, 
  unit: 'second' | 'minute' | 'hour' | 'day' | 'month' | 'year'
): Date {
  return dayjs(date).add(amount, unit).toDate();
}

/**
 * Diferencia entre dos fechas
 * @param date1 - Primera fecha
 * @param date2 - Segunda fecha  
 * @param unit - Unidad de diferencia
 * @returns Diferencia en la unidad especificada
 */
export function diffTime(
  date1: Date | string,
  date2: Date | string,
  unit: 'second' | 'minute' | 'hour' | 'day' | 'month' | 'year' = 'second'
): number {
  return dayjs(date1).diff(dayjs(date2), unit);
}

/**
 * Formatear fecha para diferentes locales/usos
 * @param date - Fecha a formatear
 * @param format - Formato dayjs
 * @returns Fecha formateada
 */
export function formatDate(date: Date | string, format: string = 'YYYY-MM-DD HH:mm:ss'): string {
  return dayjs(date).format(format);
}

/**
 * Verificar si una fecha está en el pasado
 * @param date - Fecha a verificar
 * @returns true si está en el pasado
 */
export function isPastDate(date: Date | string): boolean {
  return dayjs(date).isBefore(dayjs());
}

/**
 * Verificar si una fecha está en el futuro
 * @param date - Fecha a verificar
 * @returns true si está en el futuro
 */
export function isFutureDate(date: Date | string): boolean {
  return dayjs(date).isAfter(dayjs());
}