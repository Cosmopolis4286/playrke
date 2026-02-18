import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { BingoConfig, BingoConfigFormData } from '@/js/types/games/bingo/room/bingo-room'

/**
 * Contrato público del composable useBingoConfig
 */
interface UseBingoConfigReturn {
    /** Datos de la sala (para crear o editar) */
    roomData: Ref<Partial<BingoConfig>>

    /** Datos del formulario de creación */
    roomFormData: Ref<BingoConfigFormData>

    /** Lista reactiva de salas */
    rooms: Ref<BingoConfig[]>

    /** Inicializa la lista local */
    setInitialRooms: (initialBingoConfigs: BingoConfig[]) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Cambio de estado (individual) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Cambio de estado masivo */
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Eliminación (individual) */
    remove: (id: string | number) => Promise<boolean>

    /** Eliminación masiva */
    removeBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Fetch desde backend */
    fetchRooms: () => Promise<boolean>
}

/**
 * Factory para una sala vacía
 */
const emptyBingoConfig = (): Partial<BingoConfig> =>
    Object.freeze({})

/**
 * Factory para formulario vacío
 */
const emptyFormData = (): BingoConfigFormData => ({
    date: null,
    time: null,
    roomType: null,
    gameMode: null,
    prizePoolType: null,
    allowedCurrencies: [],
    amountAddedPrizePool: null,
    roomCommn: 20,
    jackpotPct: null,
    timeChip: 5,
    rounds: 1,
    roundsPct: null,
    serialsPct: 5,
    numSerials: 5,
    cardPrice: 1,
    cardPriceByCurrency: {},
    roundsConfig: []
})

/**
 * Composable para manejar salas de bingo
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 */
export function useBingoConfig(tokenId: string = ''): UseBingoConfigReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario (para tabla CRUD) */
    const roomData = ref<Partial<BingoConfig>>(emptyBingoConfig())

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoConfigFormData>(emptyFormData())

    /** Lista reactiva local */
    const rooms = ref<BingoConfig[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialRooms(initialBingoConfigs: BingoConfig[]) {
        rooms.value = [...initialBingoConfigs]
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchRooms(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: BingoConfig[] }>(
                '/bingo-rooms/fetch-list',
                { params: { token: tokenId } }
            )

            if (Array.isArray(response.data?.data)) {
                rooms.value = response.data.data
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar las salas de bingo', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Envío del formulario (create / update)
     */
    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const payload = { ...roomFormData.value }

            if (roomData.value.id) {
                await backendClient.patch(
                    `/bingo-rooms/${roomData.value.id}/update`,
                    payload
                )
                showToast('Sala actualizada exitosamente', 'success')
            } else {
                await backendClient.post('/bingo-rooms', payload)
                showToast('Sala creada exitosamente', 'success')
            }

            await fetchRooms()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar la sala de bingo',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Modo creación
     */
    function create(): void {
        roomData.value = { ...emptyBingoConfig() }
        roomFormData.value = emptyFormData()
    }

    /**
     * Modo edición
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/bingo-rooms/${id}/show`
            )

            const room = data.data
            const config = room.config ?? {}

            roomData.value = { id: room.id }

            roomFormData.value = {
                date: config.date ?? null,
                time: config.time ?? null,
                roomType: config.room_type_id ?? null,
                gameMode: config.game_mode_id ?? null,
                prizePoolType: config.prize_pool_type_id ?? null,
                allowedCurrencies: room.allowed_currencies ?? [],
                amountAddedPrizePool: config.amount_added_prize_pool ?? null,
                roomCommn: config.room_commission ?? 20,
                jackpotPct: config.jackpot_pct ?? null,
                timeChip: config.time_chip ?? 5,
                rounds: config.rounds ?? 1,
                roundsPct: config.rounds_pct ?? null,
                serialsPct: config.serials_pct ?? 5,
                numSerials: config.num_serials ?? 5,
                cardPrice: config.card_price ?? 1,
                cardPriceByCurrency: room.card_price_by_currency ?? {},
                roundsConfig: []
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar la sala de bingo', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Reset del formulario
     */
    function reset(): void {
        roomData.value = { ...emptyBingoConfig() }
        roomFormData.value = emptyFormData()
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de la sala.
     */
    async function toggleStatus(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const name = room?.id ?? 'esta sala'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.patch(`/bingo-rooms/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Eliminación (soft delete)
     */
    async function remove(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const name = room?.id ?? 'esta sala'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.delete(`/bingo-rooms/${id}/delete`)
            showToast('Sala eliminada con éxito', 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast('Error al eliminar la sala', 'error')
            return false
        }
    }

    /**
     * Genera HTML con lista de códigos para mostrar en el modal de confirmación
     */
    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => rooms.value.find(c => c.id === id))
            .filter(Boolean)
            .map(c => c!.id)

        return `
            <div style="max-height: 150px; overflow-y: auto; text-align: left; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 20px;">
                    ${items.map(code => `<li style="padding: 4px 0;">${code}</li>`).join('')}
                </ul>
            </div>
        `
    }

    /**
     * Alternar estado (masivo)
     */
    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        if (ids.length === 1) {
            return toggleStatus(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estas ${ids.length} salas?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.patch(`/bingo-rooms/${id}/status`)
            }
            showToast(`Estado cambiado con éxito para ${ids.length} salas`, 'success')
            await fetchRooms()
            return true
        } catch (error) {
            console.error('Error al cambiar el estado de las salas:', error)
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Eliminación masiva (soft delete)
     */
    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        if (ids.length === 1) {
            return remove(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} salas?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.delete(`/bingo-rooms/${id}/delete`)
            }
            showToast(`${ids.length} salas eliminadas con éxito`, 'success')
            await fetchRooms()
            return true
        } catch (error) {
            console.error('Error al eliminar las salas:', error)

            showToast('Error al eliminar las salas', 'error')
            return false
        }
    }

    return {
        roomData,
        roomFormData,
        rooms,
        setInitialRooms,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        fetchRooms
    }
}
