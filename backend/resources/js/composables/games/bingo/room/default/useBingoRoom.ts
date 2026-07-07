import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { useBingoRoomCrudFactory } from '../shared/useBingoRoomCrudFactory'

import type { BingoRoom, BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/**
 * Contrato público del composable useBingoRoom
 */
interface UseBingoRoomReturn {
    /** Datos de la sala (para crear o editar) */
    roomData: Ref<Partial<BingoRoom>>

    /** Datos del formulario de creación */
    roomFormData: Ref<BingoRoomFormData>

    /** Lista reactiva de salas */
    rooms: Ref<BingoRoom[]>

    /** Estado de carga global */
    loading: Ref<boolean>

    /** Inicializa la lista local */
    setInitialRooms: (initialBingoRooms: BingoRoom[]) => void

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

    /** Obtener detalles de una sala */
    fetchRoomDetails: (id: string | number) => Promise<BingoRoom | null>

    /** Ir a sala de juego */
    goToRoom: (ids: string[]) => Promise<void>

    /** Cancelar sala */
    cancelRoom: (id: string | number) => Promise<boolean>

    /** Activar manualmente el sorteo de una sala sin horario programado */
    activateRoomNow: (id: string | number) => Promise<boolean>

    /** Programar fecha/hora de inicio de una sala sin horario (la convierte en programada) */
    scheduleRoomStart: (id: string | number, date: string, time: string) => Promise<boolean>
}

/**
 * Factory para una sala vacía
 */
const emptyBingoRoom = (): Partial<BingoRoom> =>
    Object.freeze({})

/**
 * Factory para formulario vacío
 */
const emptyFormData = (): BingoRoomFormData => ({
    date: null,
    time: null,
    startMode: 'SCHEDULED',
    minCardsToStart: null,
    roomType: null,
    gameMode: null,
    gameVariant: 'BINGO_75',
    prizePoolType: null,
    allowedCurrencies: [],
    roomsPerCurrency: false,
    amountAddedPrizePool: null,
    roomCommn: 20,
    jackpotPct: null,
    timeChip: 5,
    rounds: 1,
    roundsPct: null,
    useSerials: false,
    serialsPct: null,
    numSerials: null,
    useBaseUsd: false,
    cardPriceUsd: 0.5,
    usdExchangeRoundStep: null,
    cardPriceByCurrency: {},
    roundsConfig: [],
    activateRoom: false,
    allowCustomCard: false,
    customCardPricePct: null,
    allowSpecialCard: false,
    maxSpecialCards: null,
})

const ENDPOINT = 'bingo-rooms'

/**
 * Composable para manejar salas de bingo
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * El CRUD de lista (fetch/copy/toggle/remove) se delega a
 * useBingoRoomCrudFactory, compartido con useBingoRoomAutomatic.
 */
export function useBingoRoom(tokenId: string = ''): UseBingoRoomReturn {
    const crud = useBingoRoomCrudFactory<BingoRoom>(tokenId, { endpoint: ENDPOINT, entityLabel: 'sala' })
    const { loading, rooms, fetchRooms } = crud

    /** Estado único del formulario (para tabla CRUD) */
    const roomData = ref<Partial<BingoRoom>>(emptyBingoRoom())

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoRoomFormData>(emptyFormData())

    /**
     * Reset del formulario
     */
    function reset(): void {
        roomData.value = { ...emptyBingoRoom() }
        roomFormData.value = emptyFormData()
    }

    /**
     * Modo creación
     */
    const create = (): void => {
        reset()
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
                try {
                    await backendClient.patch(
                        `/${ENDPOINT}/${roomData.value.id}/update`,
                        payload
                    )
                    showToast('Sala actualizada exitosamente', 'success')
                } catch (updateError: any) {
                    showToast(
                        updateError?.response?.data?.message ?? 'Error al actualizar la sala de bingo',
                        'error'
                    )
                    return false
                }
            } else {
                try {
                    await backendClient.post(`/${ENDPOINT}`, payload)
                    showToast('Sala creada exitosamente', 'success')
                } catch (createError: any) {
                    showToast(
                        createError?.response?.data?.message ?? 'Error al crear la sala de bingo',
                        'error'
                    )
                    return false
                }
            }

            // Forzar recarga con timestamp para evitar cache
            const timestamp = Date.now()
            const response = await backendClient.get<{ data: BingoRoom[] }>(
                `/${ENDPOINT}/fetch-list`,
                { params: { token: tokenId, _t: timestamp } }
            )

            if (Array.isArray(response.data?.data)) {
                rooms.value = response.data.data
            }

            // Forzar reactividad
            const currentRooms = [...rooms.value]
            rooms.value = []
            rooms.value = currentRooms

            reset()

            // Emitir evento para forzar refresh de la tabla
            window.dispatchEvent(new CustomEvent('bingo-room-updated'))

            return true
        } catch (error: any) {
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
     * Modo edición
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/${ENDPOINT}/${id}/show`
            )

            const room = data.data
            const config = room.config ?? {}

            roomData.value = { id: room.id }

            roomFormData.value = {
                date: config.date ?? null,
                time: config.time ?? null,
                startMode: config.start_mode ?? 'SCHEDULED',
                minCardsToStart: config.min_cards_to_start ?? null,
                roomType: config.room_type_id ?? null,
                gameMode: config.game_mode_id ?? null,
                gameVariant: (config.game_variant ?? 'BINGO_75') as any,
                prizePoolType: config.prize_pool_type_id ?? null,
                allowedCurrencies: room.allowed_currencies ?? [],
                roomsPerCurrency: room.rooms_per_currency === true,
                amountAddedPrizePool: config.amount_added_prize_pool ?? null,
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
                activateRoom: room.status === 'ACTIVE',
                allowCustomCard: config.allow_custom_card === true,
                customCardPricePct: config.custom_card_price_pct != null ? Number(config.custom_card_price_pct) : null,
                allowSpecialCard: config.allow_special_card === true,
                maxSpecialCards: config.max_special_cards != null ? Number(config.max_special_cards) : null,
            }

            return true
        } catch {
            showToast('Error al cargar la sala de bingo', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Obtener detalles completos de una sala
     */
    async function fetchRoomDetails(id: string | number): Promise<BingoRoom | null> {
        try {
            const response = await backendClient.get<{ data: BingoRoom }>(
                `/${ENDPOINT}/${id}/show`
            )
            return response.data?.data ?? null
        } catch {
            showToast('Error al cargar los detalles de la sala', 'error')
            return null
        }
    }

    /**
     * Ir a sala de juego
     */
    async function goToRoom(ids: string[]): Promise<void> {
        if (!ids || ids.length !== 1) {
            showToast('Seleccione una sala para continuar', 'error')
            return
        }

        const roomId = ids[0]
        const room = rooms.value.find(r => String(r.id) === String(roomId))

        if (!room) {
            showToast('Sala no encontrada', 'error')
            return
        }

        const confirm = await showToastConfirmAction({
            html: `¿Quieres ir a la sala de juego <b>${room.uuid_code ?? roomId}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, entrar'
        })

        if (!confirm) return

        window.location.href = `/bingo/${roomId}/game`
    }

    /**
     * Cancelar sala
     */
    async function cancelRoom(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const roomCode = room?.uuid_code ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Está seguro de que desea cancelar la sala "<b>${roomCode}</b>"? Esta acción no se puede deshacer.`,
            icon: 'warning',
            confirmButtonText: 'Sí, cancelar'
        })

        if (!confirm) return false

        try {
            await backendClient.patch(`/${ENDPOINT}/${id}/cancel`)
            showToast('Sala cancelada exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            showToast(
                error?.response?.data?.message ?? 'Error al cancelar la sala',
                'error'
            )
            return false
        }
    }

    /**
     * Activar manualmente el sorteo de una sala sin horario programado.
     * Solo disponible para el creador de la sala o un admin/superadmin (resuelto en backend).
     */
    async function activateRoomNow(id: string | number): Promise<boolean> {
        const room = rooms.value.find(c => c.id === id)
        const roomCode = room?.uuid_code ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Quieres activar ahora la sala "<b>${roomCode}</b>"? El sorteo iniciará de inmediato.`,
            icon: 'warning',
            confirmButtonText: 'Sí, activar ahora'
        })

        if (!confirm) return false

        try {
            await backendClient.patch(`/${ENDPOINT}/${id}/activate-now`)
            showToast('Sala activada exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            showToast(
                error?.response?.data?.message ?? 'Error al activar la sala',
                'error'
            )
            return false
        }
    }

    async function scheduleRoomStart(id: string | number, date: string, time: string): Promise<boolean> {
        try {
            await backendClient.patch(`/${ENDPOINT}/${id}/schedule-start`, { date, time })
            showToast('Inicio programado exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            showToast(
                error?.response?.data?.message ?? 'Error al programar el inicio de la sala',
                'error'
            )
            return false
        }
    }

    return {
        roomData,
        roomFormData,
        rooms,
        loading,
        setInitialRooms: crud.setInitialRooms,
        submit,
        create,
        edit,
        reset,
        copyRoom: crud.copyRoom,
        toggleStatus: crud.toggleStatus,
        toggleStatusBulk: crud.toggleStatusBulk,
        remove: crud.remove,
        removeBulk: crud.removeBulk,
        fetchRooms,
        fetchRoomDetails,
        goToRoom,
        cancelRoom,
        activateRoomNow,
        scheduleRoomStart
    }
}
