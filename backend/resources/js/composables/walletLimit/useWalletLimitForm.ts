import { ref, watch, computed } from 'vue'
import type { WalletLimit } from '@/js/types/walletLimit'

const DEFAULTS: Partial<WalletLimit> = {
    currency_id: null,
    kyc_level: 0,
    min_deposit: 1,
    max_deposit: 100,
    min_per_transaction: 1,
    max_per_transaction: 50,
    max_daily: 50,
    max_weekly: 100,
    max_monthly: 200,
    is_active: true
}

/**
 * Composable para manejar el formulario de WalletLimit.
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useWalletLimitForm(
    modelValue: Partial<WalletLimit>,
    emit: (event: 'update:modelValue', value: Partial<WalletLimit>) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref<Partial<WalletLimit>>({ ...DEFAULTS })

    const errors = ref<Partial<Record<keyof WalletLimit, string>>>({})

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!modelValue?.id)

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    /* =======================
     * Methods
     * ======================= */

    function num(value: unknown): number {
        const n = Number(value)
        return Number.isFinite(n) ? n : NaN
    }

    const validate = () => {
        errors.value = {}

        if (form.value.kyc_level === undefined || form.value.kyc_level === null) {
            errors.value.kyc_level = 'El nivel KYC es requerido.'
        }

        const minDeposit = num(form.value.min_deposit)
        const maxDeposit = num(form.value.max_deposit)
        if (!(minDeposit >= 0)) {
            errors.value.min_deposit = 'Debe ser un número mayor o igual a 0.'
        }
        if (!(maxDeposit > minDeposit)) {
            errors.value.max_deposit = 'Debe ser mayor al mínimo de depósito.'
        }

        const minTx = num(form.value.min_per_transaction)
        const maxTx = num(form.value.max_per_transaction)
        if (!(minTx >= 0)) {
            errors.value.min_per_transaction = 'Debe ser un número mayor o igual a 0.'
        }
        if (!(maxTx > minTx)) {
            errors.value.max_per_transaction = 'Debe ser mayor al mínimo de retiro.'
        }

        const maxDaily = num(form.value.max_daily)
        const maxWeekly = num(form.value.max_weekly)
        const maxMonthly = num(form.value.max_monthly)

        if (!(maxDaily >= maxTx)) {
            errors.value.max_daily = 'Debe ser mayor o igual al máximo por retiro.'
        }
        if (!(maxWeekly >= maxDaily)) {
            errors.value.max_weekly = 'Debe ser mayor o igual al límite diario.'
        }
        if (!(maxMonthly >= maxWeekly)) {
            errors.value.max_monthly = 'Debe ser mayor o igual al límite semanal.'
        }
    }

    const reset = () => {
        form.value = { ...DEFAULTS }
        errors.value = {}
    }

    /* =======================
     * Watchers
     * ======================= */

    /**
     * Sync modelValue → form (edit / create)
     */
    watch(
        () => modelValue,
        value => {
            form.value = {
                id: value.id,
                currency_id: value.currency_id ?? null,
                kyc_level: value.kyc_level ?? 0,
                min_deposit: value.min_deposit ?? DEFAULTS.min_deposit,
                max_deposit: value.max_deposit ?? DEFAULTS.max_deposit,
                min_per_transaction: value.min_per_transaction ?? DEFAULTS.min_per_transaction,
                max_per_transaction: value.max_per_transaction ?? DEFAULTS.max_per_transaction,
                max_daily: value.max_daily ?? DEFAULTS.max_daily,
                max_weekly: value.max_weekly ?? DEFAULTS.max_weekly,
                max_monthly: value.max_monthly ?? DEFAULTS.max_monthly,
                is_active: value.is_active ?? true
            }
        },
        { immediate: true, deep: true }
    )

    /**
     * Sync form → modelValue
     */
    watch(
        form,
        value => {
            emit('update:modelValue', { ...value })
        },
        { deep: true }
    )

    /**
     * Validación reactiva
     */
    watch(
        () => [
            form.value.kyc_level,
            form.value.min_deposit, form.value.max_deposit,
            form.value.min_per_transaction, form.value.max_per_transaction,
            form.value.max_daily, form.value.max_weekly, form.value.max_monthly
        ],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isValid,
        validate,
        reset
    }
}
