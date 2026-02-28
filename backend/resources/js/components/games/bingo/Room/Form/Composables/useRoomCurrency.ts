// useRoomCurrency.ts
import { ref, computed, watch } from 'vue'

export interface CurrencyOption {
    code: string       // USD, BRL, COP
    symbol: string     // $, R$, etc
    decimals: number   // 0, 2, 3
    roundingStep?: number // 1, 5, 0.5, etc
}

export function useRoomCurrency(initialCurrencies: CurrencyOption[] = []) {
    const allowedCurrencies = ref<CurrencyOption[]>(initialCurrencies)
    const mainCurrency = ref<CurrencyOption | null>(
        initialCurrencies[0] ?? null
    )

    const errors = ref<{
        allowedCurrencies?: string
        mainCurrency?: string
    }>({})

    /**
     * Validar que haya al menos una moneda
     */
    function validateAllowedCurrencies() {
        if (!allowedCurrencies.value.length) {
            errors.value.allowedCurrencies =
                'Debe seleccionar al menos una moneda'
        } else {
            errors.value.allowedCurrencies = undefined
        }
    }

    /**
     * Validar que moneda principal esté incluida
     */
    function validateMainCurrency() {
        if (!mainCurrency.value) {
            errors.value.mainCurrency = 'Debe definir moneda principal'
            return
        }

        const exists = allowedCurrencies.value.find(
            (c) => c.code === mainCurrency.value?.code
        )

        if (!exists) {
            errors.value.mainCurrency =
                'La moneda principal debe estar en permitidas'
        } else {
            errors.value.mainCurrency = undefined
        }
    }

    /**
     * Redondeo inteligente según configuración
     */
    function normalizeAmount(
        amount: number,
        currency: CurrencyOption
    ): number {
        const step = currency.roundingStep ?? 1
        const rounded = Math.round(amount / step) * step
        return Number(rounded.toFixed(currency.decimals))
    }

    /**
     * Formateo simple
     */
    function format(
        amount: number,
        currency: CurrencyOption
    ): string {
        return `${currency.symbol}${amount.toFixed(currency.decimals)}`
    }

    /**
     * Conversión básica usando tasa externa
     */
    function convert(
        amount: number,
        from: CurrencyOption,
        to: CurrencyOption,
        rate: number
    ): number {
        const converted = amount * rate
        return normalizeAmount(converted, to)
    }

    /**
     * Helpers
     */
    const isValid = computed(() => {
        return !errors.value.allowedCurrencies &&
            !errors.value.mainCurrency
    })

    watch(allowedCurrencies, validateAllowedCurrencies, {
        immediate: true,
        deep: true
    })

    watch(mainCurrency, validateMainCurrency, {
        immediate: true
    })

    return {
        allowedCurrencies,
        mainCurrency,
        errors,
        isValid,
        validateAllowedCurrencies,
        validateMainCurrency,
        normalizeAmount,
        format,
        convert
    }
}
