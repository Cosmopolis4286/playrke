import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { useBingoRoomCrudFactory } from '../shared/useBingoRoomCrudFactory'

import type { BingoRoomAutomatic, BingoRoomAutomaticFormData } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'

/** Extrae el mensaje de error más específico posible: detalle de validación (422) si existe, si no el mensaje general. */
function extractErrorMessage(err: any, fallback: string): string {
    const errors = err?.response?.data?.errors
    if (errors) return Object.values(errors).flat().join(' ')
    return err?.response?.data?.message ?? fallback
}

/**
 * Contrato público del composable useBingoRoomAutomatic
 */
interface UseBingoRoomAutomaticReturn {
    /** Datos de la sala (para crear o editar) */
    roomData: Ref<Partial<BingoRoomAutomatic>>

    /** Etiqueta (username) del anfitrión asignado a la sala en edición, para precargar el AsyncSelect. */
    hostUserLabel: Ref<string | null>

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

    /** Ejecutar el job de auto-generación manualmente */
    executeJob: () => Promise<boolean>
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
    connectionType: null,
    gameMode: null,
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
    minCardsToStart: null,
    salesStartOffsetDays: null,
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

const ENDPOINT = 'bingo-automatic-rooms'

/**
 * Composable para manejar salas de bingo automaticas
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * El CRUD de lista (fetch/copy/toggle/remove) se delega a
 * useBingoRoomCrudFactory, compartido con useBingoRoom.
 */
export function useBingoRoomAutomatic(tokenId: string = ''): UseBingoRoomAutomaticReturn {
    const crud = useBingoRoomCrudFactory<BingoRoomAutomatic>(tokenId, { endpoint: ENDPOINT, entityLabel: 'sala' })
    const { loading, rooms, fetchRooms } = crud

    /** Estado único del formulario (para tabla CRUD) */
    const roomData = ref<Partial<BingoRoomAutomatic>>(emptyBingoRoomAutomatic())

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoRoomAutomaticFormData>(emptyFormData())

    /** Etiqueta del anfitrión asignado a la sala en edición */
    const hostUserLabel = ref<string | null>(null)

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
                    `/${ENDPOINT}/${roomData.value.id}/update`,
                    payload
                )
                showToast('Sala actualizada exitosamente', 'success')
            } else {
                await backendClient.post(`/${ENDPOINT}`, payload)
                showToast('Sala creada exitosamente', 'success')
            }

            await fetchRooms()
            return true
        } catch (error: any) {
            showToast(
                extractErrorMessage(error, 'Error al guardar la sala de bingo'),
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
        hostUserLabel.value = null
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
            hostUserLabel.value = config.host_user_label ?? null

            roomFormData.value = {
                date: config.date ?? null,
                days: room.days ?? [],
                time: config.time ?? null,
                roomType: config.room_type_id ?? null,
                connectionType: config.connection_type_id ?? null,
                gameMode: config.game_mode_id ?? null,
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
                minCardsToStart: room.min_cards_to_start != null ? Number(room.min_cards_to_start) : null,
                salesStartOffsetDays: room.sales_start_offset_days != null ? Number(room.sales_start_offset_days) : null,
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
     * Reset del formulario
     */
    function reset(): void {
        roomData.value = { ...emptyBingoRoomAutomatic() }
        roomFormData.value = emptyFormData()
        hostUserLabel.value = null
    }

    /**
     * Ejecuta el job de auto-generación de salas de bingo manualmente.
     * Llama al mismo generador que el scheduler, filtrado por el creador autenticado.
     */
    async function executeJob(): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: '¿Ejecutar el generador de salas ahora?<br><small>Solo se crearán salas cuyo horario esté dentro de la ventana actual.</small>',
            icon: 'question',
            confirmButtonText: 'Sí, ejecutar'
        })

        if (!confirm) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ created: boolean; message: string }>(
                `/${ENDPOINT}/execute-job`
            )

            showToast(data.message, data.created ? 'success' : 'info')

            if (data.created) {
                await fetchRooms()
            }

            return data.created
        } catch (error: any) {
            showToast(
                error?.response?.data?.message ?? 'Error al ejecutar el job',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    return {
        roomData,
        hostUserLabel,
        roomFormData,
        rooms,
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
        executeJob,
    }
}
