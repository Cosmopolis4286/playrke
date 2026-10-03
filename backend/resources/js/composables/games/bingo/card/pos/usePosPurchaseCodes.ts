import { computed, ref } from 'vue'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { FrequentBuyer } from '@/js/types/games/bingo/card/bingo-card-generate'

/** Código de compra otorgado a un comprador recurrente (GET /bingo-card/pos/purchase-codes). */
export interface PurchaseCode {
    id: string
    code: string
    phone: string
    country_id: string
    max_uses: number
    uses_count: number
    remaining_uses: number
    expires_at: string | null
    status: 'Vigente' | 'Agotado' | 'Vencido' | 'Revocado'
    usable: boolean
}

export interface SalesLink {
    token: string
    url: string
}

interface CodeOptions {
    uses: number[]
    default_uses: number
    valid_days: number[]
    default_valid_days: number
}

const onlyDigits = (s: string) => s.replace(/\D/g, '')

/**
 * Panel "Compra con código" del punto de venta: el enlace público de venta
 * del vendedor y los códigos que otorga a sus compradores recurrentes para
 * que compren por su cuenta (el código vence por usos o por tiempo).
 */
export function usePosPurchaseCodes() {
    const link = ref<SalesLink | null>(null)
    const options = ref<CodeOptions | null>(null)
    const codes = ref<PurchaseCode[]>([])
    const buyers = ref<FrequentBuyer[]>([])

    const loading = ref(false)
    const issuing = ref(false)
    const lastIssued = ref<PurchaseCode | null>(null)

    /** Nombre del comprador de un código (los códigos guardan solo dígitos del teléfono). */
    function buyerName(code: PurchaseCode): string {
        const match = buyers.value.find(b => onlyDigits(b.phone) === code.phone && b.country_id === code.country_id)
        return match ? `${match.first_name} ${match.last_name}`.trim() : code.phone
    }

    const activeCount = computed(() => codes.value.filter(c => c.usable).length)

    async function load(): Promise<void> {
        loading.value = true
        try {
            const [linkRes, codesRes, buyersRes] = await Promise.all([
                backendClient.get('/bingo-card/pos/sales-link'),
                backendClient.get('/bingo-card/pos/purchase-codes'),
                backendClient.get('/bingo-card/pos/frequent-buyers'),
            ])
            link.value = linkRes.data.link
            options.value = linkRes.data.code_options
            codes.value = codesRes.data.codes ?? []
            buyers.value = buyersRes.data.buyers ?? []
        } catch {
            showToast('No se pudo cargar tu enlace de venta.', 'error')
        } finally {
            loading.value = false
        }
    }

    async function regenerateLink(): Promise<void> {
        try {
            const { data } = await backendClient.post('/bingo-card/pos/sales-link/regenerate')
            link.value = data.link
            showToast('Enlace regenerado. El anterior ya no funciona.', 'success')
        } catch (e: any) {
            showToast(e?.response?.data?.message ?? 'No se pudo regenerar el enlace.', 'error')
        }
    }

    async function issue(buyer: FrequentBuyer, maxUses: number, validDays: number): Promise<PurchaseCode | null> {
        issuing.value = true
        try {
            const { data } = await backendClient.post('/bingo-card/pos/purchase-codes', {
                country_id: buyer.country_id,
                phone: buyer.phone,
                max_uses: maxUses,
                valid_days: validDays,
            })
            const code: PurchaseCode = data.code
            // El anterior de ese comprador quedó revocado en el backend.
            codes.value = [
                code,
                ...codes.value.map(c =>
                    c.phone === code.phone && c.country_id === code.country_id && c.usable
                        ? { ...c, usable: false, status: 'Revocado' as const }
                        : c
                ),
            ]
            lastIssued.value = code
            return code
        } catch (e: any) {
            showToast(e?.response?.data?.message ?? 'No se pudo generar el código.', 'error')
            return null
        } finally {
            issuing.value = false
        }
    }

    async function revoke(code: PurchaseCode): Promise<void> {
        try {
            await backendClient.delete(`/bingo-card/pos/purchase-codes/${code.id}`)
            codes.value = codes.value.map(c => (c.id === code.id ? { ...c, usable: false, status: 'Revocado' } : c))
            if (lastIssued.value?.id === code.id) lastIssued.value = null
            showToast('Código revocado.', 'success')
        } catch (e: any) {
            showToast(e?.response?.data?.message ?? 'No se pudo revocar el código.', 'error')
        }
    }

    /** Mensaje listo para WhatsApp con el código y el enlace. */
    function shareText(code: PurchaseCode): string {
        const until = code.expires_at
            ? new Date(code.expires_at).toLocaleDateString('es', { day: 'numeric', month: 'long' })
            : ''
        const uses = code.remaining_uses === 1 ? '1 compra' : `${code.remaining_uses} compras`
        return [
            `¡Hola ${buyerName(code).split(' ')[0]}! Ya puedes comprar tus cartones de Bingo por tu cuenta.`,
            `Tu código: *${code.code}*`,
            `Entra aquí: ${link.value?.url ?? ''}`,
            `Sirve para ${uses}${until ? ` hasta el ${until}` : ''}.`,
        ].join('\n')
    }

    function whatsappUrl(code: PurchaseCode): string {
        return `https://wa.me/?text=${encodeURIComponent(shareText(code))}`
    }

    async function copy(text: string, label: string): Promise<void> {
        try {
            await navigator.clipboard.writeText(text)
            showToast(`${label} copiado`, 'success')
        } catch {
            showToast('No se pudo copiar', 'error')
        }
    }

    return {
        link, options, codes, buyers, loading, issuing, lastIssued, activeCount,
        load, regenerateLink, issue, revoke, buyerName, shareText, whatsappUrl, copy,
    }
}
