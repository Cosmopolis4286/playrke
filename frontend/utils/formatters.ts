/**
 * Utilidades para formateo de datos
 *
 * Proporciona funciones reutilizables para formatear:
 * - Fechas y horas
 * - Números y monedas
 * - Texto
 * - Tamaños de archivo
 */

/**
 * Formatea una fecha a un string legible
 *
 * @param date Fecha a formatear (string ISO, Date, o timestamp)
 * @param options Opciones de formato
 * @returns Fecha formateada
 */
export function formatDate(
  date: string | Date | number,
  options: Intl.DateTimeFormatOptions = {}
): string {
  const dateObj = typeof date === 'string' || typeof date === 'number' ? new Date(date) : date

  if (!dateObj || isNaN(dateObj.getTime())) {
    return 'Fecha inválida'
  }

  const defaultOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    ...options
  }

  return new Intl.DateTimeFormat('es-ES', defaultOptions).format(dateObj)
}

/**
 * Formatea una fecha con hora
 *
 * @param date Fecha a formatear
 * @returns Fecha y hora formateada
 */
export function formatDateTime(date: string | Date | number): string {
  return formatDate(date, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Formatea solo la hora
 *
 * @param date Fecha a formatear
 * @returns Hora formateada
 */
export function formatTime(date: string | Date | number): string {
  const dateObj = typeof date === 'string' || typeof date === 'number' ? new Date(date) : date

  if (!dateObj || isNaN(dateObj.getTime())) {
    return 'Hora inválida'
  }

  return new Intl.DateTimeFormat('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(dateObj)
}

/**
 * Formatea una fecha de forma relativa (hace X tiempo)
 *
 * @param date Fecha a formatear
 * @returns Fecha relativa
 */
export function formatRelativeTime(date: string | Date | number): string {
  const dateObj = typeof date === 'string' || typeof date === 'number' ? new Date(date) : date

  if (!dateObj || isNaN(dateObj.getTime())) {
    return 'Fecha inválida'
  }

  const now = new Date()
  const diffMs = now.getTime() - dateObj.getTime()
  const diffSecs = Math.floor(diffMs / 1000)
  const diffMins = Math.floor(diffSecs / 60)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  const diffMonths = Math.floor(diffDays / 30)
  const diffYears = Math.floor(diffDays / 365)

  if (diffSecs < 60) {
    return 'hace unos segundos'
  } else if (diffMins < 60) {
    return `hace ${diffMins} ${diffMins === 1 ? 'minuto' : 'minutos'}`
  } else if (diffHours < 24) {
    return `hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`
  } else if (diffDays < 30) {
    return `hace ${diffDays} ${diffDays === 1 ? 'día' : 'días'}`
  } else if (diffMonths < 12) {
    return `hace ${diffMonths} ${diffMonths === 1 ? 'mes' : 'meses'}`
  } else {
    return `hace ${diffYears} ${diffYears === 1 ? 'año' : 'años'}`
  }
}

/**
 * Formatea un número con separadores de miles
 *
 * @param num Número a formatear
 * @param decimals Cantidad de decimales (default: 0)
 * @returns Número formateado
 */
export function formatNumber(num: number, decimals: number = 0): string {
  if (num === null || num === undefined || isNaN(num)) {
    return '0'
  }

  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(num)
}

/**
 * Formatea un número como moneda
 *
 * @param amount Cantidad a formatear
 * @param currency Código de moneda (default: 'USD')
 * @returns Cantidad formateada como moneda
 */
export function formatCurrency(amount: number, currency: string = 'USD'): string {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return formatCurrencySymbol(0, currency)
  }

  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

/**
 * Formatea un número con el símbolo de moneda
 *
 * @param amount Cantidad a formatear
 * @param currency Código de moneda
 * @returns Cantidad con símbolo
 */
export function formatCurrencySymbol(amount: number, currency: string): string {
  const symbols: Record<string, string> = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    VES: 'Bs.',
    BRL: 'R$',
    COP: '$',
    ARS: '$',
    MXN: '$',
    USDT: '₮'
  }

  const symbol = symbols[currency] || currency
  const formatted = formatNumber(amount, 2)

  return `${symbol}${formatted}`
}

/**
 * Formatea un porcentaje
 *
 * @param value Valor a formatear (0-100 o 0-1)
 * @param decimals Cantidad de decimales (default: 0)
 * @param isDecimal Si el valor está en formato decimal (0-1)
 * @returns Porcentaje formateado
 */
export function formatPercentage(
  value: number,
  decimals: number = 0,
  isDecimal: boolean = false
): string {
  if (value === null || value === undefined || isNaN(value)) {
    return '0%'
  }

  const percentage = isDecimal ? value * 100 : value
  return `${formatNumber(percentage, decimals)}%`
}

/**
 * Formatea un tamaño de archivo
 *
 * @param bytes Tamaño en bytes
 * @param decimals Cantidad de decimales (default: 2)
 * @returns Tamaño formateado
 */
export function formatFileSize(bytes: number, decimals: number = 2): string {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(decimals))} ${sizes[i]}`
}

/**
 * Trunca un texto a una longitud específica
 *
 * @param text Texto a truncar
 * @param maxLength Longitud máxima
 * @param suffix Sufijo a agregar (default: '...')
 * @returns Texto truncado
 */
export function truncateText(text: string, maxLength: number, suffix: string = '...'): string {
  if (!text) return ''
  if (text.length <= maxLength) return text

  return text.substring(0, maxLength - suffix.length) + suffix
}

/**
 * Capitaliza la primera letra de un string
 *
 * @param text Texto a capitalizar
 * @returns Texto capitalizado
 */
export function capitalize(text: string): string {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
}

/**
 * Capitaliza la primera letra de cada palabra
 *
 * @param text Texto a capitalizar
 * @returns Texto con cada palabra capitalizada
 */
export function capitalizeWords(text: string): string {
  if (!text) return ''
  return text
    .split(' ')
    .map(word => capitalize(word))
    .join(' ')
}

/**
 * Convierte un snake_case a Title Case
 *
 * @param text Texto en snake_case
 * @returns Texto en Title Case
 */
export function snakeToTitle(text: string): string {
  if (!text) return ''
  return text
    .split('_')
    .map(word => capitalize(word))
    .join(' ')
}

/**
 * Convierte un camelCase a Title Case
 *
 * @param text Texto en camelCase
 * @returns Texto en Title Case
 */
export function camelToTitle(text: string): string {
  if (!text) return ''
  return text
    .replace(/([A-Z])/g, ' $1')
    .split(' ')
    .map(word => capitalize(word))
    .join(' ')
}

/**
 * Formatea un número de teléfono
 *
 * @param phone Número de teléfono
 * @returns Teléfono formateado
 */
export function formatPhone(phone: string): string {
  if (!phone) return ''

  // Remover caracteres no numéricos
  const cleaned = phone.replace(/\D/g, '')

  // Formatear según longitud
  if (cleaned.length === 10) {
    return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3')
  } else if (cleaned.length === 11) {
    return cleaned.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
  }

  return phone
}

/**
 * Oculta parte de un email
 *
 * @param email Email a ocultar
 * @returns Email parcialmente oculto
 */
export function maskEmail(email: string): string {
  if (!email || !email.includes('@')) return email

  const [localPart, domain] = email.split('@')
  const visibleChars = Math.min(3, Math.floor(localPart.length / 2))
  const masked = localPart.substring(0, visibleChars) + '***'

  return `${masked}@${domain}`
}

/**
 * Oculta parte de un número de tarjeta
 *
 * @param cardNumber Número de tarjeta
 * @returns Número parcialmente oculto
 */
export function maskCardNumber(cardNumber: string): string {
  if (!cardNumber) return ''

  const cleaned = cardNumber.replace(/\s/g, '')
  if (cleaned.length < 4) return cardNumber

  const lastFour = cleaned.slice(-4)
  return `**** **** **** ${lastFour}`
}

/**
 * Formatea un array como lista legible
 *
 * @param items Array de items
 * @param conjunction Conjunción a usar (default: 'y')
 * @returns Lista formateada
 */
export function formatList(items: string[], conjunction: string = 'y'): string {
  if (!items || items.length === 0) return ''
  if (items.length === 1) return items[0]
  if (items.length === 2) return `${items[0]} ${conjunction} ${items[1]}`

  const lastItem = items[items.length - 1]
  const otherItems = items.slice(0, -1).join(', ')

  return `${otherItems} ${conjunction} ${lastItem}`
}

/**
 * Pluraliza una palabra según un contador
 *
 * @param count Cantidad
 * @param singular Palabra en singular
 * @param plural Palabra en plural (opcional)
 * @returns Palabra pluralizada
 */
export function pluralize(count: number, singular: string, plural?: string): string {
  if (count === 1) return `${count} ${singular}`

  const pluralForm = plural || `${singular}s`
  return `${count} ${pluralForm}`
}

/**
 * Formatea un rango de fechas
 *
 * @param startDate Fecha de inicio
 * @param endDate Fecha de fin
 * @returns Rango formateado
 */
export function formatDateRange(
  startDate: string | Date | number,
  endDate: string | Date | number
): string {
  const start = formatDate(startDate, { month: 'short', day: 'numeric' })
  const end = formatDate(endDate, { month: 'short', day: 'numeric', year: 'numeric' })

  return `${start} - ${end}`
}

/**
 * Convierte minutos a formato legible (Xh Ym)
 *
 * @param minutes Minutos totales
 * @returns Tiempo formateado
 */
export function formatDuration(minutes: number): string {
  if (!minutes || minutes < 0) return '0m'

  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60

  if (hours === 0) return `${mins}m`
  if (mins === 0) return `${hours}h`

  return `${hours}h ${mins}m`
}

/**
 * Formatea segundos a formato MM:SS
 *
 * @param seconds Segundos totales
 * @returns Tiempo en formato MM:SS
 */
export function formatTimeMMSS(seconds: number): string {
  if (!seconds || seconds < 0) return '00:00'

  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60

  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
}

/**
 * Formatea un número grande de forma compacta (1K, 1M, etc)
 *
 * @param num Número a formatear
 * @param decimals Cantidad de decimales (default: 1)
 * @returns Número compacto
 */
export function formatCompactNumber(num: number, decimals: number = 1): string {
  if (num === null || num === undefined || isNaN(num)) {
    return '0'
  }

  if (num < 1000) {
    return String(num)
  }

  const units = ['', 'K', 'M', 'B', 'T']
  const order = Math.floor(Math.log10(num) / 3)
  const unitname = units[order]
  const numInUnit = num / Math.pow(1000, order)

  return `${numInUnit.toFixed(decimals)}${unitname}`
}
