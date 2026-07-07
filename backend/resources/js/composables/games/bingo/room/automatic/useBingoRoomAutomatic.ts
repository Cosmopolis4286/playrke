import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { useBingoRoomCrudFactory } from '../shared/useBingoRoomCrudFactory'

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
    customCardPricePct: null,
    allowSpecialCard: false,
    maxSpecialCards: null,
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
                `/${ENDPOINT}/${id}/show`
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
                minCardsToStart: room.min_cards_to_start ?? null,
                salesStartOffsetDays: room.sales_start_offset_days ?? null,
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
     * Reset del formulario
     */
    function reset(): void {
        roomData.value = { ...emptyBingoRoomAutomatic() }
        roomFormData.value = emptyFormData()
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
