import { computed, nextTick, reactive, ref, watch } from 'vue'
import type { Ref } from 'vue'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import { usePhoneField, PHONE_CONFIG } from '@/js/composables/form/usePhoneField'
import { validateBingoCardGenerate } from '../bingoCardGenerateSchema'

import type { Country } from '@/js/types/country'
import type {
    CardPriceResponse,
    FrequentBuyer,
    GenerateCardResponse,
    PosRoom,
    PosSaleResult,
} from '@/js/types/games/bingo/card/bingo-card-generate'

export const MAX_CARDS = 40

export type BuyerLookup = 'idle' | 'checking' | 'found' | 'new'

/** Cómo pagó el comprador. Efectivo no lleva comprobante (el comercio confirma al revisar). */
export type PaymentMethod = 'cash' | 'mobile_payment' | 'bank_transfer'

export const PAYMENT_METHODS: { value: PaymentMethod; label: string; icon: string }[] = [
    { value: 'mobile_payment', label: 'Pago móvil',    icon: 'fas fa-mobile-alt' },
    { value: 'bank_transfer',  label: 'Transferencia', icon: 'fas fa-university' },
    { value: 'cash',           label: 'Efectivo',      icon: 'fas fa-money-bill-wave' },
]

/**
 * Número en formato wa.me (solo dígitos, sin el 0 troncal local): el
 * teléfono se guarda formateado por IMask ("+58 0414-1234567").
 */
export function whatsappNumber(phone: string, iso2: string | null): string | null {
    const digits = phone.replace(/\D/g, '')
    if (!digits) return null
    const dial = iso2 ? PHONE_CONFIG[iso2.toUpperCase()]?.dialCode : undefined
    if (dial && digits.startsWith(`${dial}0`)) return dial + digits.slice(dial.length + 1)
    return digits
}

/**
 * Estado y reglas de una venta en el punto de venta del vendedor.
 *
 * Flujo: sala → país (auto si hay uno) → teléfono (búsqueda automática del
 * comprador al completarse) → nombre → cantidad → comprobante → confirmar.
 * Mismos endpoints y validación que el formulario anterior
 * (available-countries, card-price, external-players/check, generate).
 */
export function usePosSale(
    selectedRoom: Ref<PosRoom | null>,
    /** El backend rechazó por cierre de ventas (409 BINGO_CARD_SALES_CLOSED): recargar salas. */
    onSalesClosed: () => void = () => {},
) {
    /* ───────── STATE ───────── */

    const countries = ref<Country[]>([])
    const loadingCountries = ref(false)
    const selectedCountry = ref<string | null>(null)

    const cardPriceData = ref<CardPriceResponse | null>(null)
    const loadingCardPrice = ref(false)

    const cardCount = ref(1)

    const buyer = reactive({ phone: '', firstName: '', lastName: '' })
    const buyerLookup = ref<BuyerLookup>('idle')
    /** Datos del comprador si ya compró en ESTA sala (check()). */
    const existingBuyer = ref<{ cardCount: number; link: string | null } | null>(null)

    const receiptNumber = ref('')
    const receiptImage = ref<File | null>(null)
    /** Se conserva entre ventas: el vendedor suele cobrar siempre igual. */
    const paymentMethod = ref<PaymentMethod>('mobile_payment')
    const requiresReceipt = computed(() => paymentMethod.value !== 'cash')

    const submitting = ref(false)
    const lastSale = ref<PosSaleResult | null>(null)

    /* ───────── PHONE ───────── */

    const selectedIso2 = computed(() =>
        countries.value.find(c => c.id === selectedCountry.value)?.iso_code_2 ?? null
    )

    const phoneRef = computed<string | undefined>({
        get: () => buyer.phone || undefined,
        set: v => { buyer.phone = v ?? '' },
    })

    const phone = usePhoneField(selectedIso2, phoneRef, v => { buyer.phone = v })

    /* ───────── COMPUTED ───────── */

    const currencySymbol = computed(() => cardPriceData.value?.currency_symbol ?? '')
    const cardPrice = computed(() => cardPriceData.value?.card_price ?? 0)
    const totalAmount = computed(() => cardPrice.value * cardCount.value)
    const commissionRate = computed(() => cardPriceData.value?.seller_commission ?? 0)
    const sellerCommission = computed(() => totalAmount.value * (commissionRate.value / 100))
    const prizePoolLocal = computed(() =>
        Number(selectedRoom.value?.prize_pool ?? 0) * (cardPriceData.value?.exchange_rate ?? 0)
    )

    const buyerReady = computed(() =>
        phone.isPhoneValid.value &&
        (buyerLookup.value === 'found' || buyerLookup.value === 'new') &&
        !!buyer.firstName.trim() && !!buyer.lastName.trim()
    )

    const paymentReady = computed(() =>
        !requiresReceipt.value || (!!receiptNumber.value.trim() && !!receiptImage.value)
    )

    const isFormValid = computed(() =>
        validateBingoCardGenerate(
            {
                selectedCountry: selectedCountry.value,
                cardCount: cardCount.value,
                phone: buyer.phone,
                firstName: buyer.firstName,
                lastName: buyer.lastName,
                receiptNumber: receiptNumber.value,
                receiptImage: receiptImage.value,
            },
            {
                hasRoom: !!selectedRoom.value,
                isLoadingPrice: loadingCardPrice.value,
                hasPriceLoaded: !!cardPriceData.value,
                requiresReceipt: requiresReceipt.value,
            }
        ) && buyerReady.value
    )

    /** Qué le falta a la venta, en lenguaje del vendedor (para el botón deshabilitado). */
    const missingSteps = computed(() => {
        const missing: string[] = []
        if (!selectedRoom.value) missing.push('Elegir sala')
        if (!selectedCountry.value) missing.push('Elegir país')
        else if (!phone.isPhoneValid.value) missing.push('Teléfono del comprador')
        else if (!buyer.firstName.trim() || !buyer.lastName.trim()) missing.push('Nombre del comprador')
        if (requiresReceipt.value) {
            if (!receiptImage.value) missing.push('Foto del comprobante')
            if (!receiptNumber.value.trim()) missing.push('Número de comprobante')
        }
        return missing
    })

    /* ───────── HELPERS ───────── */

    function resetBuyer(keepPhone = false): void {
        if (!keepPhone) buyer.phone = ''
        buyer.firstName = ''
        buyer.lastName = ''
        buyerLookup.value = 'idle'
        existingBuyer.value = null
    }

    function resetPayment(): void {
        receiptNumber.value = ''
        receiptImage.value = null
    }

    function setCardCount(value: number): void {
        cardCount.value = Math.max(1, Math.min(MAX_CARDS, Math.round(value) || 1))
    }

    /* ───────── API ───────── */

    async function fetchCountries(roomId: string): Promise<void> {
        loadingCountries.value = true
        try {
            const { data } = await backendClient.get<{ countries: Country[] }>(`/bingo-card/${roomId}/available-countries`)
            countries.value = data.countries ?? []
            // Un solo país habilitado (lo más común): elegirlo sin preguntar.
            if (countries.value.length === 1) selectedCountry.value = countries.value[0].id
        } catch {
            countries.value = []
            showToast('No se pudieron cargar los países disponibles.', 'error')
        } finally {
            loadingCountries.value = false
        }
    }

    async function fetchCardPrice(): Promise<void> {
        if (!selectedRoom.value || !selectedCountry.value) return
        loadingCardPrice.value = true
        try {
            const { data } = await backendClient.get<CardPriceResponse>(
                `/bingo-card/${selectedRoom.value.id}/card-price`,
                { params: { country_id: selectedCountry.value } }
            )
            cardPriceData.value = data
        } catch (error: any) {
            cardPriceData.value = null
            showToast(error?.response?.data?.message ?? 'No se pudo obtener el precio del cartón.', 'error')
            if (error?.response?.data?.error_code === 'BINGO_CARD_SALES_CLOSED') onSalesClosed()
        } finally {
            loadingCardPrice.value = false
        }
    }

    let lookupToken = 0

    /** Busca al comprador en la sala actual en cuanto el teléfono es válido. */
    async function lookupBuyer(): Promise<void> {
        if (!selectedRoom.value || !selectedCountry.value || !phone.isPhoneValid.value) return
        const token = ++lookupToken
        buyerLookup.value = 'checking'
        try {
            const { data } = await backendClient.get('/external-players/check', {
                params: {
                    phone: buyer.phone,
                    country_id: selectedCountry.value,
                    game_id: 'bingo',
                    room_id: selectedRoom.value.id,
                },
            })
            if (token !== lookupToken) return

            if (data.exists && data.player) {
                buyer.firstName = data.player.first_name ?? ''
                buyer.lastName = data.player.last_name ?? ''
                existingBuyer.value = { cardCount: data.player.card_count ?? 0, link: data.player.link ?? null }
                buyerLookup.value = 'found'
            } else {
                existingBuyer.value = null
                buyerLookup.value = 'new'
            }
        } catch {
            if (token === lookupToken) buyerLookup.value = 'new'
        }
    }

    /**
     * Completa el comprador desde "clientes frecuentes". Si es de otro país
     * se cambia primero el país y se espera a que usePhoneField limpie el
     * campo (su watcher de ISO), antes de escribir el teléfono.
     */
    async function applyFrequentBuyer(fb: FrequentBuyer): Promise<void> {
        if (!countries.value.some(c => c.id === fb.country_id)) {
            showToast('Este cliente es de un país no habilitado en esta sala.', 'warning')
            return
        }
        if (selectedCountry.value !== fb.country_id) {
            selectedCountry.value = fb.country_id
            await nextTick()
        }
        buyer.phone = fb.phone
        await nextTick()
        // El check de la sala puede traer un nombre más reciente; si no existe
        // en esta sala, se usan los datos del historial.
        buyer.firstName = fb.first_name
        buyer.lastName = fb.last_name
    }

    async function submit(): Promise<boolean> {
        if (!isFormValid.value || submitting.value || !selectedRoom.value || !cardPriceData.value) return false

        submitting.value = true
        const room = selectedRoom.value

        const formData = new FormData()
        formData.append('card_count', String(cardCount.value))
        formData.append('country_id', selectedCountry.value!)
        formData.append('player[phone]', buyer.phone.trim())
        formData.append('player[first_name]', buyer.firstName.trim())
        formData.append('player[last_name]', buyer.lastName.trim())
        formData.append('payment_method', paymentMethod.value)
        // El backend rechaza la venta (409 BINGO_CARD_PRICE_CHANGED) si el precio cambió.
        formData.append('expected_card_price', String(cardPriceData.value.card_price))
        // Efectivo: sin comprobante (el backend lo ignora igual).
        if (requiresReceipt.value) {
            formData.append('receipt_number', receiptNumber.value.trim())
            if (receiptImage.value) formData.append('receipt_image', receiptImage.value)
        }

        try {
            const { data } = await backendClient.post<GenerateCardResponse>(
                `/bingo-card/${room.id}/generate`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )

            lastSale.value = {
                playerLink: data.playerLink ?? null,
                paymentStatus: data.paymentStatus ?? null,
                buyerName: `${buyer.firstName.trim()} ${buyer.lastName.trim()}`,
                buyerPhone: buyer.phone,
                countryIso2: selectedIso2.value,
                roomCode: room.uuid_code,
                cardCount: cardCount.value,
                total: totalAmount.value,
                currencySymbol: currencySymbol.value,
            }
            return true
        } catch (error: any) {
            const code = error?.response?.data?.error_code
            showToast(
                error?.response?.data?.message ?? 'Error al generar cartones.',
                code === 'BINGO_CARD_PRICE_CHANGED' ? 'warning' : 'error'
            )
            if (code === 'BINGO_CARD_PRICE_CHANGED') await fetchCardPrice()
            if (code === 'BINGO_CARD_SALES_CLOSED') onSalesClosed()
            return false
        } finally {
            submitting.value = false
        }
    }

    /** Siguiente venta: misma sala y país, comprador y pago en blanco. */
    function startNewSale(): void {
        lastSale.value = null
        cardCount.value = 1
        resetBuyer()
        resetPayment()
    }

    /* ───────── WATCHERS ───────── */

    watch(selectedRoom, async (room, prev) => {
        if (room?.id === prev?.id) return
        countries.value = []
        selectedCountry.value = null
        cardPriceData.value = null
        cardCount.value = 1
        resetBuyer()
        resetPayment()
        lastSale.value = null
        if (room) await fetchCountries(room.id)
    })

    watch(selectedCountry, () => {
        cardPriceData.value = null
        buyerLookup.value = 'idle'
        existingBuyer.value = null
        fetchCardPrice()
    })

    // Teléfono completo → buscar comprador. Al editarlo se invalida la búsqueda
    // (y el nombre, si venía de un comprador existente).
    watch(() => buyer.phone, () => {
        lookupToken++
        if (buyerLookup.value === 'found') {
            buyer.firstName = ''
            buyer.lastName = ''
        }
        existingBuyer.value = null
        buyerLookup.value = 'idle'
        if (phone.isPhoneValid.value) lookupBuyer()
    })

    return {
        // catálogos / precio
        countries, loadingCountries, selectedCountry, selectedIso2,
        cardPriceData, loadingCardPrice, currencySymbol, cardPrice,
        totalAmount, commissionRate, sellerCommission, prizePoolLocal,
        // comprador
        buyer, buyerLookup, existingBuyer, phone, applyFrequentBuyer,
        // cantidad / pago
        cardCount, setCardCount, receiptNumber, receiptImage, paymentMethod, requiresReceipt,
        // estado general
        buyerReady, paymentReady, isFormValid, missingSteps,
        submitting, submit, lastSale, startNewSale,
    }
}
