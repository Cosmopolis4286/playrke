import { computed, watch } from 'vue'
import type { Ref, ComputedRef } from 'vue'

/**
 * Configuración de teléfono por código ISO-2 del país.
 *
 * IMPORTANTE: el valor que viaja en el modelo es el FORMATEADO por IMask
 * (maskEmitFormatted=true), porque los literales fijos (+58, +55, etc.)
 * son parte del valor y debemos guardar el teléfono completo con prefijo.
 *
 * Máscaras IMask:
 *   0        → dígito obligatorio
 *   {texto}  → literal fijo (no editable por el usuario)
 *   espacio/guión fuera de {} → separadores opcionales del patrón
 */
interface PhoneConfig {
    /** Código de discado sin "+" */
    dialCode: string
    /** Máscara IMask pattern */
    mask: string
    /** Regex que valida el valor FORMATEADO (con prefijo +XX ...) */
    pattern: RegExp
    /** Ejemplo para el placeholder */
    placeholder: string
    /** Texto de ayuda */
    hint: string
}

const PHONE_CONFIG: Record<string, PhoneConfig> = {
    /**
     * Venezuela: +58
     * Operadoras móviles: 0412, 0414, 0416, 0424, 0426
     * Fijos:              02XX-XXXXXXX
     * Formato almacenado: +58 0414-1234567  (11 dígitos locales)
     */
    VE: {
        dialCode: '58',
        mask: '+{58} 0000-0000000',
        pattern: /^\+58 0[2-4]\d{2}-\d{7}$/,
        placeholder: '+58 0414-1234567',
        hint: 'Ej: +58 0414-1234567'
    },

    /**
     * Brasil: +55
     * Celular: (DDD) 9XXXX-XXXX  → 11 dígitos locales
     * Formato almacenado: +55 (11) 91234-5678
     */
    BR: {
        dialCode: '55',
        mask: '+{55} (00) 00000-0000',
        pattern: /^\+55 \(\d{2}\) \d{5}-\d{4}$/,
        placeholder: '+55 (11) 91234-5678',
        hint: 'Ej: +55 (11) 91234-5678'
    },

    /**
     * Colombia: +57
     * Celular: 3XX XXX XXXX  → 10 dígitos locales
     * Formato almacenado: +57 312 345 6789
     */
    CO: {
        dialCode: '57',
        mask: '+{57} 000 000 0000',
        pattern: /^\+57 3\d{2} \d{3} \d{4}$/,
        placeholder: '+57 312 345 6789',
        hint: 'Ej: +57 312 345 6789'
    }
}

export interface UsePhoneFieldReturn {
    /** Máscara IMask dinámica (null = sin país seleccionado) */
    phoneMask: ComputedRef<string | null>
    /** Placeholder dinámico según el país */
    phonePlaceholder: ComputedRef<string>
    /** Texto de ayuda con el formato esperado */
    phoneHint: ComputedRef<string>
    /** Prefijo internacional listo para pre-rellenar: "+58 ", "+55 ", etc. */
    phoneDialPrefix: ComputedRef<string>
    /** Error de validación (null = válido o vacío) */
    phoneError: ComputedRef<string | null>
    /** true si el valor formateado cumple el patrón del país */
    isPhoneValid: ComputedRef<boolean>
}

/**
 * Composable reutilizable para campos de teléfono con validación por país.
 *
 * Requiere que FormInput use maskEmitFormatted=true para que el valor
 * almacenado sea el texto formateado (con prefijo +XX), no los dígitos crudos.
 *
 * @param isoCode2   - ISO-2 del país activo ('VE', 'BR', 'CO' o null)
 * @param phoneValue - Ref con el valor actual del campo (formateado)
 * @param update     - Función para limpiar el valor al cambiar de país
 */
export function usePhoneField(
    isoCode2: Ref<string | null | undefined> | ComputedRef<string | null | undefined>,
    phoneValue: Ref<string | undefined>,
    update: (value: string) => void
): UsePhoneFieldReturn {

    const activeConfig = computed<PhoneConfig | null>(() => {
        const iso = isoCode2.value?.toUpperCase()
        return iso ? (PHONE_CONFIG[iso] ?? null) : null
    })

    const phoneMask = computed(() => activeConfig.value?.mask ?? null)

    const phonePlaceholder = computed(() =>
        activeConfig.value?.placeholder ?? 'Selecciona un país primero'
    )

    const phoneHint = computed(() => activeConfig.value?.hint ?? '')

    /** Prefijo con espacio para pre-rellenar el campo al elegir país: "+58 " */
    const phoneDialPrefix = computed(() =>
        activeConfig.value ? `+${activeConfig.value.dialCode} ` : ''
    )

    const phoneError = computed<string | null>(() => {
        const val = phoneValue.value?.trim() ?? ''
        if (!val) return null
        const config = activeConfig.value
        if (!config) return null
        if (!config.pattern.test(val)) {
            return `Formato inválido. ${config.hint}`
        }
        return null
    })

    const isPhoneValid = computed(() => {
        const val = phoneValue.value?.trim() ?? ''
        if (!val || !activeConfig.value) return false
        return activeConfig.value.pattern.test(val)
    })

    // Al cambiar de país, limpiar el número anterior para evitar formatos mezclados
    watch(isoCode2, (newIso, oldIso) => {
        if (oldIso !== undefined && newIso !== oldIso) {
            update('')
        }
    })

    return {
        phoneMask,
        phonePlaceholder,
        phoneHint,
        phoneDialPrefix,
        phoneError,
        isPhoneValid
    }
}

export { PHONE_CONFIG }
export type { PhoneConfig }
