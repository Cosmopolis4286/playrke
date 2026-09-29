import { onUnmounted, ref } from 'vue'

import backendClient from '@/js/plugins/axios'
import type { FrequentBuyer, PosSale, PosSalesSummary } from '@/js/types/games/bingo/card/bingo-card-generate'

/** Cada cuánto se refrescan las ventas del día (el comercio aprueba/rechaza en paralelo). */
const REFRESH_MS = 60_000

/**
 * Ventas del día del vendedor (GET /bingo-card/pos/today-sales). Se refresca
 * sola para que el vendedor vea cuándo el comercio aprueba un comprobante,
 * y manualmente tras cada venta.
 */
export function usePosTodaySales() {
    const sales = ref<PosSale[]>([])
    const summary = ref<PosSalesSummary | null>(null)
    const loading = ref(false)

    async function refresh(): Promise<void> {
        loading.value = true
        try {
            const { data } = await backendClient.get<{ sales: PosSale[]; summary: PosSalesSummary }>('/bingo-card/pos/today-sales')
            sales.value = data.sales ?? []
            summary.value = data.summary ?? null
        } catch {
            // Silencioso: el panel es informativo, no bloquea la venta.
        } finally {
            loading.value = false
        }
    }

    refresh()
    const timer = window.setInterval(() => {
        if (document.visibilityState === 'visible') refresh()
    }, REFRESH_MS)
    onUnmounted(() => window.clearInterval(timer))

    return { sales, summary, loading, refresh }
}

/**
 * Búsqueda de clientes frecuentes del vendedor con debounce. Sin texto
 * devuelve los más recientes, para que un habitual se elija en un clic.
 */
export function useFrequentBuyers() {
    const query = ref('')
    const buyers = ref<FrequentBuyer[]>([])
    const loading = ref(false)

    let debounce: number | undefined
    let token = 0

    async function load(): Promise<void> {
        const current = ++token
        loading.value = true
        try {
            const { data } = await backendClient.get<{ buyers: FrequentBuyer[] }>('/bingo-card/pos/frequent-buyers', {
                params: { q: query.value.trim() || undefined },
            })
            if (current === token) buyers.value = data.buyers ?? []
        } catch {
            if (current === token) buyers.value = []
        } finally {
            if (current === token) loading.value = false
        }
    }

    function search(value: string): void {
        query.value = value
        window.clearTimeout(debounce)
        debounce = window.setTimeout(load, 250)
    }

    onUnmounted(() => window.clearTimeout(debounce))

    return { query, buyers, loading, load, search }
}
