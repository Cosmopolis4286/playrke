import { computed, ref, watch } from 'vue'
import type { Ref, ComputedRef } from 'vue'

/**
 * Configuración de teléfono por código ISO-2 del país.
 *
 * Cada entrada define:
 * - dialCode    : prefijo internacional (sin "+")
 * - areaDigits  : dígitos del código de área
 * - localDigits : dígitos del número local (sin dialCode ni área)
 * - totalDigits : total de dígitos del número local (sin dialCode)
 * - pattern     : RegExp sobre el valor completo incluyendo dialCode
 * - mask        : string de máscara imask (0 = dígito)
 * - placeholder : ejemplo visible en el campo
 * - hint        : texto de ayuda corto
 */
interface PhoneConfig {
    dialCode: string
    areaDigits: number
    localDigits: number
    totalDigits: number        // areaDigits + localDigits
    pattern: RegExp
    mask: string               // IMask pattern
    placeholder: string
    hint: string
}

const PHONE_CONFIG: Record<string, PhoneConfig> = {
    // Venezuela: +58 | área 3 dígitos (0414) | local 7 dígitos → total 11 (sin +58)
    VE: {
        dialCode: '58',
        areaDigits: 4,          // 04XX
        localDigits: 7,
        totalDigits: 11,        // 04XX-XXXXXXX
        pattern: /^\+58\s?0?(4|2)\d{2}[\s-]?\d{3}[\s-]?\d{4}$/,
        mask: '+{58} 0000-0000000',
        placeholder: '+58 0414-1234567',
        hint: '+58 04XX-XXXXXXX'
    },
    // Brasil: +55 | DDD 2 dígitos | celular 9 dígitos o fijo 8 → usamos 9 (padrão)
    BR: {
        dialCode: '55',
        areaDigits: 2,          // DDD
        localDigits: 9,
        totalDigits: 11,
        pattern: /^\+55\s?\(?\d{2}\)?\s?9?\d{4}[\s-]?\d{4}$/,
        mask: '+{55} (00) 00000-0000',
        placeholder: '+55 (11) 91234-5678',
        hint: '+55 (DDD) 9XXXX-XXXX'
    },
    // Colombia: +57 | área 1–3 dígitos | celular 10 dígitos sin +57 (3XX XXX XXXX)
    CO: {
        dialCode: '57',
        areaDigits: 3,          // 3XX (operadora)
        localDigits: 7,
        totalDigits: 10,
        pattern: /^\+57\s?3\d{2}[\s-]?\d{3}[\s-]?\d{4}$/,
        mask: '+{57} 000 000 0000',
        placeholder: '+57 312 345 6789',
        hint: '+57 3XX XXX XXXX'
    }
}

const FALLBACK_CONFIG: PhoneConfig = {
    dialCode: '',
    areaDigits: 0,
    localDigits: 0,
    totalDigits: 15,
    pattern: /^.{6,20}$/,
    mask: '',
    placeholder: 'Número de teléfono',
    hint: ''
}

export interface UsePhoneFieldReturn {
    /** Máscara IMask dinámica según el país seleccionado (null = sin máscara) */
    phoneMask: ComputedRef<string | null>
    /** Placeholder dinámico */
    phonePlaceholder: ComputedRef<string>
    /** Texto de ayuda (hint) */
    phoneHint: ComputedRef<string>
    /** Prefijo internacional ("+58", "+55", "+57") */
    dialCode: ComputedRef<string>
    /** Error de validación (null = válido o vacío) */
    phoneError: ComputedRef<string | null>
    /** true si el valor actual es válido según las reglas del país */
    isPhoneValid: ComputedRef<boolean>
    /** Prefija automáticamente el dialCode cuando el usuario empieza a escribir */
    onPhoneFocus: (currentValue: string, update: (v: string) => void) => void
    /** Configuración completa del país activo */
    activeConfig: ComputedRef<PhoneConfig>
}

/**
 * Composable reutilizable para campos de teléfono con validación por país.
 *
 * Uso:
 * ```ts
 * const iso = computed(() => selectedCountry?.iso_code_2 ?? null)
 * const { phoneMask, phonePlaceholder, phoneError } = usePhoneField(iso, phoneValue)
 * ```
 *
 * @param isoCode2  - Ref/ComputedRef con el código ISO-2 del país activo (ej: 'VE')
 * @param phoneValue - Ref con el valor actual del campo de teléfono
 * @param update    - Función para actualizar el valor del teléfono (como emit v-model)
 */
export function usePhoneField(
    isoCode2: Ref<string | null | undefined> | ComputedRef<string | null | undefined>,
    phoneValue: Ref<string | undefined>,
    update: (value: string) => void
): UsePhoneFieldReturn {

    const activeConfig = computed<PhoneConfig>(() => {
        const iso = isoCode2.value?.toUpperCase()
        return iso && PHONE_CONFIG[iso] ? PHONE_CONFIG[iso] : FALLBACK_CONFIG
    })

    const phoneMask = computed(() => activeConfig.value.mask || null)
    const phonePlaceholder = computed(() => activeConfig.value.placeholder)
    const phoneHint = computed(() => activeConfig.value.hint)
    const dialCode = computed(() =>
        activeConfig.value.dialCode ? `+${activeConfig.value.dialCode}` : ''
    )

    /**
     * Valida el valor actual contra el patrón del país.
     * Retorna null si es válido o si está vacío (sin tocar).
     */
    const phoneError = computed<string | null>(() => {
        const val = phoneValue.value?.trim() ?? ''
        if (!val) return null  // campo vacío: error lo maneja el campo required externo

        const config = activeConfig.value
        if (!config.pattern.test(val)) {
            return `Formato inválido. Ej: ${config.placeholder}`
        }
        return null
    })

    const isPhoneValid = computed(() => {
        const val = phoneValue.value?.trim() ?? ''
        if (!val) return false
        return activeConfig.value.pattern.test(val)
    })

    /**
     * Al hacer focus en el campo, si está vacío y hay dialCode configurado,
     * prellena con el prefijo para guiar al usuario.
     */
    function onPhoneFocus(currentValue: string, updateFn: (v: string) => void) {
        const prefix = dialCode.value
        if (!currentValue?.trim() && prefix) {
            updateFn(prefix + ' ')
        }
    }

    /**
     * Cuando cambia el país, limpiar el valor del teléfono
     * para evitar formatos inválidos mezclados.
     */
    watch(isoCode2, (newIso, oldIso) => {
        if (oldIso !== undefined && newIso !== oldIso && phoneValue.value) {
            update('')
        }
    })

    return {
        phoneMask,
        phonePlaceholder,
        phoneHint,
        dialCode,
        phoneError,
        isPhoneValid,
        onPhoneFocus,
        activeConfig
    }
}

/** Exporta las configuraciones para usarlas directamente si se necesita */
export { PHONE_CONFIG, FALLBACK_CONFIG }
export type { PhoneConfig }
