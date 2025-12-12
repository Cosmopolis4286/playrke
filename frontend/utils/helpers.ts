/**
 * Utilidades helper generales
 *
 * Funciones auxiliares para tareas comunes:
 * - Manipulación de objetos
 * - Operaciones con arrays
 * - Delays y timers
 * - Generación de IDs
 * - Utilidades de navegador
 */

/**
 * Crea un delay/pausa
 *
 * @param ms Milisegundos a esperar
 * @returns Promise que se resuelve después del delay
 */
export function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms))
}

/**
 * Genera un ID único
 *
 * @param prefix Prefijo opcional
 * @returns ID único
 */
export function generateId(prefix: string = ''): string {
  const timestamp = Date.now().toString(36)
  const randomStr = Math.random().toString(36).substring(2, 9)

  return prefix ? `${prefix}-${timestamp}-${randomStr}` : `${timestamp}-${randomStr}`
}

/**
 * Genera un UUID v4
 *
 * @returns UUID v4
 */
export function generateUUID(): string {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

/**
 * Copia texto al portapapeles
 *
 * @param text Texto a copiar
 * @returns Promise que se resuelve con éxito/error
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  try {
    if (navigator.clipboard) {
      await navigator.clipboard.writeText(text)
      return true
    } else {
      // Fallback para navegadores antiguos
      const textarea = document.createElement('textarea')
      textarea.value = text
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
      return true
    }
  } catch (error) {
    console.error('Error copiando al portapapeles:', error)
    return false
  }
}

/**
 * Debounce de una función
 *
 * @param func Función a ejecutar
 * @param wait Tiempo de espera en ms
 * @returns Función con debounce
 */
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null

  return function executedFunction(...args: Parameters<T>) {
    const later = () => {
      timeout = null
      func(...args)
    }

    if (timeout) {
      clearTimeout(timeout)
    }

    timeout = setTimeout(later, wait)
  }
}

/**
 * Throttle de una función
 *
 * @param func Función a ejecutar
 * @param limit Límite de tiempo en ms
 * @returns Función con throttle
 */
export function throttle<T extends (...args: any[]) => any>(
  func: T,
  limit: number
): (...args: Parameters<T>) => void {
  let inThrottle: boolean = false

  return function executedFunction(...args: Parameters<T>) {
    if (!inThrottle) {
      func(...args)
      inThrottle = true
      setTimeout(() => {
        inThrottle = false
      }, limit)
    }
  }
}

/**
 * Clona profundamente un objeto
 *
 * @param obj Objeto a clonar
 * @returns Clon del objeto
 */
export function deepClone<T>(obj: T): T {
  if (obj === null || typeof obj !== 'object') {
    return obj
  }

  if (obj instanceof Date) {
    return new Date(obj.getTime()) as any
  }

  if (obj instanceof Array) {
    return obj.map(item => deepClone(item)) as any
  }

  if (obj instanceof Object) {
    const clonedObj = {} as T
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        clonedObj[key] = deepClone(obj[key])
      }
    }
    return clonedObj
  }

  return obj
}

/**
 * Merge profundo de objetos
 *
 * @param target Objeto destino
 * @param sources Objetos a mergear
 * @returns Objeto mergeado
 */
export function deepMerge<T extends object>(target: T, ...sources: Partial<T>[]): T {
  if (!sources.length) return target

  const source = sources.shift()

  if (source === undefined) return target

  if (isObject(target) && isObject(source)) {
    for (const key in source) {
      if (isObject(source[key])) {
        if (!target[key]) {
          Object.assign(target, { [key]: {} })
        }
        deepMerge(target[key] as any, source[key] as any)
      } else {
        Object.assign(target, { [key]: source[key] })
      }
    }
  }

  return deepMerge(target, ...sources)
}

/**
 * Verifica si un valor es un objeto
 *
 * @param item Item a verificar
 * @returns True si es objeto
 */
function isObject(item: any): item is object {
  return item && typeof item === 'object' && !Array.isArray(item)
}

/**
 * Obtiene un valor de un objeto anidado usando path
 *
 * @param obj Objeto
 * @param path Path (ej: 'user.address.city')
 * @param defaultValue Valor por defecto
 * @returns Valor encontrado o valor por defecto
 */
export function getNestedValue<T = any>(
  obj: any,
  path: string,
  defaultValue?: T
): T | undefined {
  const keys = path.split('.')
  let result = obj

  for (const key of keys) {
    if (result === null || result === undefined) {
      return defaultValue
    }
    result = result[key]
  }

  return result !== undefined ? result : defaultValue
}

/**
 * Establece un valor en un objeto anidado usando path
 *
 * @param obj Objeto
 * @param path Path (ej: 'user.address.city')
 * @param value Valor a establecer
 */
export function setNestedValue(obj: any, path: string, value: any): void {
  const keys = path.split('.')
  const lastKey = keys.pop()

  if (!lastKey) return

  let current = obj

  for (const key of keys) {
    if (!(key in current) || typeof current[key] !== 'object') {
      current[key] = {}
    }
    current = current[key]
  }

  current[lastKey] = value
}

/**
 * Agrupa un array de objetos por una propiedad
 *
 * @param array Array a agrupar
 * @param key Propiedad por la cual agrupar
 * @returns Objeto con arrays agrupados
 */
export function groupBy<T>(array: T[], key: keyof T): Record<string, T[]> {
  return array.reduce((result, item) => {
    const groupKey = String(item[key])
    if (!result[groupKey]) {
      result[groupKey] = []
    }
    result[groupKey].push(item)
    return result
  }, {} as Record<string, T[]>)
}

/**
 * Ordena un array de objetos por una propiedad
 *
 * @param array Array a ordenar
 * @param key Propiedad por la cual ordenar
 * @param order Orden (asc o desc)
 * @returns Array ordenado
 */
export function sortBy<T>(
  array: T[],
  key: keyof T,
  order: 'asc' | 'desc' = 'asc'
): T[] {
  return [...array].sort((a, b) => {
    const aVal = a[key]
    const bVal = b[key]

    if (aVal < bVal) return order === 'asc' ? -1 : 1
    if (aVal > bVal) return order === 'asc' ? 1 : -1
    return 0
  })
}

/**
 * Elimina duplicados de un array
 *
 * @param array Array con posibles duplicados
 * @param key Propiedad para comparar (opcional)
 * @returns Array sin duplicados
 */
export function unique<T>(array: T[], key?: keyof T): T[] {
  if (!key) {
    return [...new Set(array)]
  }

  const seen = new Set()
  return array.filter(item => {
    const val = item[key]
    if (seen.has(val)) {
      return false
    }
    seen.add(val)
    return true
  })
}

/**
 * Divide un array en chunks
 *
 * @param array Array a dividir
 * @param size Tamaño de cada chunk
 * @returns Array de chunks
 */
export function chunk<T>(array: T[], size: number): T[][] {
  const chunks: T[][] = []

  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size))
  }

  return chunks
}

/**
 * Shuffle (mezcla aleatoria) de un array
 *
 * @param array Array a mezclar
 * @returns Array mezclado
 */
export function shuffle<T>(array: T[]): T[] {
  const shuffled = [...array]

  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
  }

  return shuffled
}

/**
 * Obtiene elementos aleatorios de un array
 *
 * @param array Array
 * @param count Cantidad de elementos
 * @returns Array con elementos aleatorios
 */
export function randomItems<T>(array: T[], count: number): T[] {
  const shuffled = shuffle(array)
  return shuffled.slice(0, count)
}

/**
 * Suma valores de una propiedad en array de objetos
 *
 * @param array Array de objetos
 * @param key Propiedad a sumar
 * @returns Suma total
 */
export function sumBy<T>(array: T[], key: keyof T): number {
  return array.reduce((sum, item) => {
    const value = item[key]
    return sum + (typeof value === 'number' ? value : 0)
  }, 0)
}

/**
 * Promedio de valores de una propiedad
 *
 * @param array Array de objetos
 * @param key Propiedad
 * @returns Promedio
 */
export function averageBy<T>(array: T[], key: keyof T): number {
  if (array.length === 0) return 0
  return sumBy(array, key) / array.length
}

/**
 * Omite propiedades de un objeto
 *
 * @param obj Objeto
 * @param keys Propiedades a omitir
 * @returns Objeto sin las propiedades especificadas
 */
export function omit<T extends object, K extends keyof T>(
  obj: T,
  ...keys: K[]
): Omit<T, K> {
  const result = { ...obj }
  keys.forEach(key => delete result[key])
  return result
}

/**
 * Selecciona solo ciertas propiedades de un objeto
 *
 * @param obj Objeto
 * @param keys Propiedades a seleccionar
 * @returns Objeto con solo las propiedades especificadas
 */
export function pick<T extends object, K extends keyof T>(
  obj: T,
  ...keys: K[]
): Pick<T, K> {
  const result = {} as Pick<T, K>
  keys.forEach(key => {
    if (key in obj) {
      result[key] = obj[key]
    }
  })
  return result
}

/**
 * Verifica si un objeto está vacío
 *
 * @param obj Objeto a verificar
 * @returns True si está vacío
 */
export function isEmpty(obj: any): boolean {
  if (obj === null || obj === undefined) return true
  if (typeof obj === 'string') return obj.trim().length === 0
  if (Array.isArray(obj)) return obj.length === 0
  if (typeof obj === 'object') return Object.keys(obj).length === 0
  return false
}

/**
 * Limpia valores null/undefined de un objeto
 *
 * @param obj Objeto a limpiar
 * @returns Objeto limpio
 */
export function cleanObject<T extends object>(obj: T): Partial<T> {
  const result: any = {}

  Object.keys(obj).forEach(key => {
    const value = (obj as any)[key]
    if (value !== null && value !== undefined) {
      result[key] = value
    }
  })

  return result
}

/**
 * Genera un número aleatorio en un rango
 *
 * @param min Valor mínimo
 * @param max Valor máximo
 * @returns Número aleatorio
 */
export function randomNumber(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

/**
 * Genera un color aleatorio en formato hex
 *
 * @returns Color hex
 */
export function randomColor(): string {
  return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')
}

/**
 * Descarga un archivo
 *
 * @param data Datos del archivo
 * @param filename Nombre del archivo
 * @param mimeType Tipo MIME
 */
export function downloadFile(data: Blob | string, filename: string, mimeType: string = 'text/plain'): void {
  const blob = data instanceof Blob ? data : new Blob([data], { type: mimeType })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  link.click()

  window.URL.revokeObjectURL(url)
}

/**
 * Convierte un objeto a query string
 *
 * @param params Objeto con parámetros
 * @returns Query string
 */
export function toQueryString(params: Record<string, any>): string {
  const cleaned = cleanObject(params)
  const searchParams = new URLSearchParams()

  Object.entries(cleaned).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      value.forEach(v => searchParams.append(key, String(v)))
    } else {
      searchParams.append(key, String(value))
    }
  })

  return searchParams.toString()
}

/**
 * Convierte query string a objeto
 *
 * @param queryString Query string
 * @returns Objeto con parámetros
 */
export function fromQueryString(queryString: string): Record<string, any> {
  const params = new URLSearchParams(queryString)
  const result: Record<string, any> = {}

  params.forEach((value, key) => {
    if (result[key]) {
      if (Array.isArray(result[key])) {
        result[key].push(value)
      } else {
        result[key] = [result[key], value]
      }
    } else {
      result[key] = value
    }
  })

  return result
}

/**
 * Detecta si es dispositivo móvil
 *
 * @returns True si es móvil
 */
export function isMobile(): boolean {
  if (typeof navigator === 'undefined') return false

  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  )
}

/**
 * Detecta el tipo de dispositivo
 *
 * @returns Tipo de dispositivo
 */
export function getDeviceType(): 'mobile' | 'tablet' | 'desktop' {
  if (typeof window === 'undefined') return 'desktop'

  const width = window.innerWidth

  if (width < 768) return 'mobile'
  if (width < 1024) return 'tablet'
  return 'desktop'
}

/**
 * Scroll suave a un elemento
 *
 * @param elementId ID del elemento
 * @param offset Offset opcional
 */
export function scrollToElement(elementId: string, offset: number = 0): void {
  const element = document.getElementById(elementId)

  if (element) {
    const y = element.getBoundingClientRect().top + window.pageYOffset + offset

    window.scrollTo({
      top: y,
      behavior: 'smooth'
    })
  }
}

/**
 * Scroll al inicio de la página
 */
export function scrollToTop(): void {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

/**
 * Obtiene parámetros de la URL actual
 *
 * @returns Objeto con parámetros
 */
export function getUrlParams(): Record<string, string> {
  if (typeof window === 'undefined') return {}

  const params = new URLSearchParams(window.location.search)
  const result: Record<string, string> = {}

  params.forEach((value, key) => {
    result[key] = value
  })

  return result
}

/**
 * Retorna un valor por defecto si el primero es null/undefined
 *
 * @param value Valor a verificar
 * @param defaultValue Valor por defecto
 * @returns Valor o valor por defecto
 */
export function defaultTo<T>(value: T | null | undefined, defaultValue: T): T {
  return value !== null && value !== undefined ? value : defaultValue
}

/**
 * Retry de una función asíncrona
 *
 * @param fn Función a ejecutar
 * @param retries Cantidad de reintentos
 * @param delay Delay entre reintentos
 * @returns Promise con resultado
 */
export async function retry<T>(
  fn: () => Promise<T>,
  retries: number = 3,
  delay: number = 1000
): Promise<T> {
  try {
    return await fn()
  } catch (error) {
    if (retries <= 0) {
      throw error
    }

    await sleep(delay)
    return retry(fn, retries - 1, delay)
  }
}
