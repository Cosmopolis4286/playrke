import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { useBingoRoomCrudFactory } from '../shared/useBingoRoomCrudFactory'

import type { BingoRoom, BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/** Extrae el mensaje de error más específico posible: detalle de validación (422) si existe, si no el mensaje general. */
function extractErrorMessage(err: any, fallback: string): string {
    const errors = err?.response?.data?.errors
    if (errors) return Object.values(errors).flat().join(' ')
    return err?.response?.data?.message ?? fallback
}

/**
 * Contrato público del composable useBingoRoom
 */
interface UseBingoRoomReturn {
    /** Datos de la sala (para crear o editar) */
    roomData: Ref<Partial<BingoRoom>>

    /** IDs de monedas con al menos un cartón vendido en la sala en edición (no se pueden quitar de "monedas permitidas"). */
    soldCurrencyIds: Ref<string[]>

    /** Etiqueta (username) del anfitrión asignado a la sala en edición, para precargar el AsyncSelect. */
    hostUserLabel: Ref<string | null>

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
    maxPlayers: null,
    roomType: null,
    connectionType: null,
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
    customCardMode: null,
    customCardPricePct: null,
    customCardEveryX: null,
    allowSpecialCard: false,
    maxSpecialCards: null,
    accessCode: null,
    roomPassword: null,
    hostUserId: null,
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

    /** Monedas con cartones vendidos en la sala en edición (ver soldCurrencyIds) */
    const soldCurrencyIds = ref<string[]>([])

    /** Etiqueta del anfitrión asignado a la sala en edición */
    const hostUserLabel = ref<string | null>(null)

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoRoomFormData>(emptyFormData())

    /**
     * Reset del formulario
     */
    function reset(): void {
        roomData.value = { ...emptyBingoRoom() }
        roomFormData.value = emptyFormData()
        soldCurrencyIds.value = []
        hostUserLabel.value = null
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
                    const message = extractErrorMessage(updateError, 'Error al actualizar la sala de bingo')
                    showToast(`${message}. Tu borrador se mantuvo guardado, puedes reintentar.`, 'error')
                    return false
                }
            } else {
                try {
                    await backendClient.post(`/${ENDPOINT}`, payload)
                    showToast('Sala creada exitosamente', 'success')
                } catch (createError: any) {
                    const message = extractErrorMessage(createError, 'Error al crear la sala de bingo')
                    showToast(`${message}. Tu borrador se mantuvo guardado, puedes reintentar.`, 'error')
                    return false
                }
            }

            await fetchRooms()
            reset()

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

            roomData.value = {
                id: room.id,
                created_at: room.created_at,
                updated_at: room.updated_at,
                last_updated_by: room.last_updated_by,
            }
            soldCurrencyIds.value = (room.sold_currencies ?? []).map((id: string | number) => String(id))
            hostUserLabel.value = config.host_user_label ?? null

            roomFormData.value = {
                date: config.date ?? null,
                time: config.time ?? null,
                startMode: config.start_mode ?? 'SCHEDULED',
                minCardsToStart: config.min_cards_to_start != null ? Number(config.min_cards_to_start) : null,
                maxPlayers: config.max_players != null ? Number(config.max_players) : null,
                roomType: config.room_type_id ?? null,
                connectionType: config.connection_type_id ?? null,
                gameMode: config.game_mode_id ?? null,
                gameVariant: (config.game_variant ?? 'BINGO_75') as any,
                prizePoolType: config.prize_pool_type_id ?? null,
                allowedCurrencies: room.allowed_currencies ?? [],
                roomsPerCurrency: room.rooms_per_currency === true,
                amountAddedPrizePool: config.amount_added_prize_pool != null ? Number(config.amount_added_prize_pool) : null,
                roomCommn: config.room_commission != null ? Number(config.room_commission) : 20,
                jackpotPct: config.jackpot_pct != null ? Number(config.jackpot_pct) : null,
                timeChip: config.time_chip != null ? Number(config.time_chip) : 5,
                rounds: config.rounds != null ? Number(config.rounds) : 1,
                roundsPct: config.rounds_pct != null ? Number(config.rounds_pct) : null,
                useSerials: config.use_serials === true,
                serialsPct: config.serials_pct != null ? Number(config.serials_pct) : null,
                numSerials: config.num_serials != null ? Number(config.num_serials) : null,
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
                customCardMode: config.custom_card_mode ?? null,
                customCardPricePct: config.custom_card_price_pct != null ? Number(config.custom_card_price_pct) : null,
                customCardEveryX: config.custom_card_every_x != null ? Number(config.custom_card_every_x) : null,
                allowSpecialCard: config.allow_special_card === true,
                maxSpecialCards: config.max_special_cards != null ? Number(config.max_special_cards) : null,
                accessCode: config.access_code ?? null,
                // La contraseña nunca se recibe del backend (ni el hash) — se deja
                // vacía en edición; si el admin no la toca, no se reenvía en el update.
                roomPassword: null,
                hostUserId: config.host_user_id ?? null,
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
        soldCurrencyIds,
        hostUserLabel,
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
