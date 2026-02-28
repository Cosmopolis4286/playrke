import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { BingoRoomAutomatic, BingoRoomAutomaticFormData } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'

/**
 * Contrato público del composable useBingoRoomAutomatic
 */
interface UseBingoRoomAutomaticReturn {
    /** Datos de la sala (para crear o editar) */
    roomData: Ref<Partial<BingoRoomAutomatic>>

    /** Datos del formulario de creación */
    roomFormData: Ref<BingoRoomAutomaticFormData>

    /** Lista reactiva de salas */
    rooms: Ref<BingoRoomAutomatic[]>

    /** Inicializa la lista local */
    setInitialRooms: (initialBingoRoomAutomatics: BingoRoomAutomatic[]) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Copiar sala como borrador */
    copyRoom: (id: string | number) => Promise<boolean>

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
const emptyBingoRoomAutomatic = (): Partial<BingoRoomAutomatic> =>
    Object.freeze({})

/**
 * Factory para formulario vacío
 */
const emptyFormData = (): BingoRoomAutomaticFormData => ({
    date: null,
    days: [],
    time: null,
    roomType: null,
    gameMode: null,
    prizePoolType: null,
    allowedCurrencies: [],
    amountAddedPrizePool: null,
    rkeCommission: null,
    roomCommn: 20,
    jackpotPct: null,
    timeChip: 5,
    rounds: 1,
    roundsPct: null,
    useSerials: false,
    serialsPct: null,
    numSerials: null,
    useBaseUsd: false,
    cardPriceUsd: 1,
    usdExchangeRoundStep: null,
    cardPriceByCurrency: {},
    roundsConfig: [],
    isDraft: true
})

/**
 * Composable para manejar salas de bingo automaticas
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 */
export function useBingoRoomAutomatic(tokenId: string = ''): UseBingoRoomAutomaticReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario (para tabla CRUD) */
    const roomData = ref<Partial<BingoRoomAutomatic>>(emptyBingoRoomAutomatic())

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoRoomAutomaticFormData>(emptyFormData())

    /** Lista reactiva local */
    const rooms = ref<BingoRoomAutomatic[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialRooms(initialBingoRoomAutomatics: BingoRoomAutomatic[]) {
        rooms.value = [...initialBingoRoomAutomatics]
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchRooms(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: BingoRoomAutomatic[] }>(
                '/bingo-automatic-rooms/fetch-list',
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
                    `/bingo-automatic-rooms/${roomData.value.id}/update`,
                    payload
                )
                showToast('Sala actualizada exitosamente', 'success')
            } else {
                await backendClient.post('/bingo-automatic-rooms', payload)
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
        roomData.value = { ...emptyBingoRoomAutomatic() }
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
                `/bingo-automatic-rooms/${id}/show`
            )

            const room = data.data
            const config = room.config ?? {}

            roomData.value = { id: room.id }

            roomFormData.value = {
                date: config.date ?? null,
                days: room.days ?? [],
                time: config.time ?? null,
                roomType: config.room_type_id ?? null,
                gameMode: config.game_mode_id ?? null,
                prizePoolType: config.prize_pool_type_id ?? null,
                allowedCurrencies: room.allowed_currencies ?? [],
                amountAddedPrizePool: config.amount_added_prize_pool ?? null,
                rkeCommission: config.rke_commission ?? null,
                roomCommn: config.room_commission ?? 20,
                jackpotPct: config.jackpot_pct ?? null,
                timeChip: config.time_chip ?? 5,
                rounds: config.rounds ?? 1,
                roundsPct: config.rounds_pct ?? null,
                useSerials: config.use_serials === true,
                serialsPct: config.serials_pct ?? null,
                numSerials: config.num_serials ?? null,
                useBaseUsd: config.use_base_usd === true,
                cardPriceUsd: config.card_price_usd != null ? Number(config.card_price_usd) : 1,
                usdExchangeRoundStep: config.usd_exchange_round_step != null ? Number(config.usd_exchange_round_step) : null,
                cardPriceByCurrency: room.card_price_by_currency ?? {},
                roundsConfig: (room.rounds_config ?? []).map((r: any) => ({
                    prizeType: r.prizeType ?? 'percentage',
                    bingoPattern: r.bingoPattern ?? null,
                    prizeValue: r.prizeValue !== null && r.prizeValue !== undefined ? Number(r.prizeValue) : null,
                    ...(r.prizeValueByCurrency ? { prizeValueByCurrency: r.prizeValueByCurrency } : {}),
                    ...(r.prizeType === 'prize' ? { prizeId: r.prizeId ?? null, prizeName: r.prizeName ?? null } : {}),
                })),
                isDraft: room.status === 'INACTIVE',
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
        roomData.value = { ...emptyBingoRoomAutomatic() }
        roomFormData.value = emptyFormData()
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de la sala.
     */
    async function toggleStatus(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const name = room?.id ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de esta sala <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.patch(`/bingo-automatic-rooms/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Copiar sala de bingo como nuevo borrador (status DRAFT)
     */
    async function copyRoom(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const name = room?.id ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Quieres copiar esta sala <b>${name}</b>? Se creará como borrador.`,
            icon: 'warning',
            confirmButtonText: 'Sí, copiar'
        })

        if (!confirm) return false

        try {
            await backendClient.post(`/bingo-automatic-rooms/${id}/copy`)
            showToast('Sala copiada como borrador exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast('Error al copiar la sala', 'error')
            return false
        }
    }

    /**
     * Eliminación (soft delete)
     */
    async function remove(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const name = room?.id ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar esta sala <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.delete(`/bingo-automatic-rooms/${id}/delete`)
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
                await backendClient.patch(`/bingo-automatic-rooms/${id}/status`)
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
                await backendClient.delete(`/bingo-automatic-rooms/${id}/delete`)
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
        copyRoom,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        fetchRooms
    }
}
