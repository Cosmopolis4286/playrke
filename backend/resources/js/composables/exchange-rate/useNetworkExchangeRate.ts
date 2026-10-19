import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface NetworkExchangeRatePoint {
    date: string
    amount: number
}

export interface NetworkExchangeRate {
    currency_id: string
    currency_code: string
    currency_name: string
    symbol: string
    /** Tasa que se aplica hoy en las ventas de las salas de la red. */
    effective: number | null
    source: 'own' | 'official'
    official: {
        amount: number
        percentage: number
        source: 'user' | 'system'
        updated_at: string | null
        history: NetworkExchangeRatePoint[]
    } | null
    own: {
        amount: number
        percentage: number
        updated_at: string | null
    } | null
    /** % de la tasa propia sobre la oficial (+ = más cara). */
    deviation_pct: number | null
}

export interface NetworkExchangeRateData {
    owner: { type: 'commerce' | 'platform'; name: string | null }
    role: string | null
    can_manage: boolean
    rates: NetworkExchangeRate[]
}

/**
 * Tasa de cambio vigente para la red del usuario (GET /exchange-rates/network,
 * ver NetworkExchangeRateService). La consume NetworkExchangeRateWidget.vue.
 */
export function useNetworkExchangeRate() {
    const data = ref<NetworkExchangeRateData | null>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)

    async function load(): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const response = await backendClient.get<{ data: NetworkExchangeRateData }>('/exchange-rates/network')
            data.value = response.data.data
        } catch (e: any) {
            error.value = e?.response?.status === 403 ? null : 'No se pudo cargar la tasa de cambio'
            data.value = null
        } finally {
            loading.value = false
        }
    }

    return { data, loading, error, load }
}
