import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

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
    customCardPrice: null,
    maxCustomCards: null,
})

/**
 * Composable para manejar salas de bingo
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 */
export function useBingoRoom(tokenId: string = ''): UseBingoRoomReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario (para tabla CRUD) */
    const roomData = ref<Partial<BingoRoom>>(emptyBingoRoom())

    /** Datos del formulario de creación/edición */
    const roomFormData = ref<BingoRoomFormData>(emptyFormData())

    /** Lista reactiva local */
    const rooms = ref<BingoRoom[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialRooms(initialBingoRooms: BingoRoom[]) {
        rooms.value = [...initialBingoRooms]
    }

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
     * Obtiene la lista desde backend
     */
    async function fetchRooms(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: BingoRoom[] }>(
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
                try {
                    console.log('Enviando update para sala:', roomData.value.id)
                    console.log('Payload:', payload)

                    await backendClient.patch(
                        `/bingo-rooms/${roomData.value.id}/update`,
                        payload
                    )
                    console.log('Update exitoso')
                    showToast('Sala actualizada exitosamente', 'success')
                } catch (updateError: any) {
                    console.error('Error en update:', updateError)
                    showToast(
                        updateError?.response?.data?.message ?? 'Error al actualizar la sala de bingo',
                        'error'
                    )
                    return false
                }
            } else {
                try {
                    console.log('Creando nueva sala')
                    await backendClient.post('/bingo-rooms', payload)
                    console.log('Create exitoso')
                    showToast('Sala creada exitosamente', 'success')
                } catch (createError: any) {
                    console.error('Error en create:', createError)
                    showToast(
                        createError?.response?.data?.message ?? 'Error al crear la sala de bingo',
                        'error'
                    )
                    return false
                }
            }

            console.log('Haciendo fetchRooms para refrescar lista')

            // Forzar recarga con timestamp para evitar cache
            const timestamp = Date.now()
            const response = await backendClient.get<{ data: BingoRoom[] }>(
                '/bingo-rooms/fetch-list',
                { params: { token: tokenId, _t: timestamp } }
            )

            if (Array.isArray(response.data?.data)) {
                console.log('Datos recibidos de fetchRooms:', response.data.data.length)
                response.data.data.forEach((room, index) => {
                    console.log(`Room ${index + 1}:`, {
                        id: room.id,
                        unix_date: room.unix_date,
                        updated_at: room.updated_at
                    })
                })
                rooms.value = response.data.data
                console.log('Rooms actualizado, nuevo length:', rooms.value.length)
            } else {
                console.log('No se recibieron datos válidos')
            }

            console.log('fetchRooms completado, rooms.length:', rooms.value.length)

            // Forzar reactividad
            console.log('Forzando actualización reactiva de rooms')
            const currentRooms = [...rooms.value]
            rooms.value = []
            rooms.value = currentRooms
            console.log('Rooms actualizado forzosamente, rooms.length:', rooms.value.length)

            reset()
            console.log('Form reseteado')
            console.log('saveRoom completado exitosamente')

            // Emitir evento para forzar refresh de la tabla
            window.dispatchEvent(new CustomEvent('bingo-room-updated'))

            return true
        } catch (error: any) {
            console.error('Error general en submit:', error)
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
                `/bingo-rooms/${id}/show`
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
                customCardPrice: config.custom_card_price != null ? Number(config.custom_card_price) : null,
                maxCustomCards: config.max_custom_cards != null ? Number(config.max_custom_cards) : null,
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
     * Copiar sala de bingo (status INACTIVE)
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
            await backendClient.post(`/bingo-rooms/${id}/copy`)
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

    /**
     * Obtener detalles completos de una sala
     */
    async function fetchRoomDetails(id: string | number): Promise<BingoRoom | null> {
        try {
            const response = await backendClient.get<{ data: BingoRoom }>(
                `/bingo-rooms/${id}/show`
            )
            return response.data?.data ?? null
        } catch (error) {
            console.error('Error al cargar detalles de sala:', error)
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
            await backendClient.patch(`/bingo-rooms/${id}/cancel`)
            showToast('Sala cancelada exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            console.error('Error al cancelar sala:', error)
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
            await backendClient.patch(`/bingo-rooms/${id}/activate-now`)
            showToast('Sala activada exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            console.error('Error al activar sala:', error)
            showToast(
                error?.response?.data?.message ?? 'Error al activar la sala',
                'error'
            )
            return false
        }
    }

    async function scheduleRoomStart(id: string | number, date: string, time: string): Promise<boolean> {
        try {
            await backendClient.patch(`/bingo-rooms/${id}/schedule-start`, { date, time })
            showToast('Inicio programado exitosamente', 'success')
            await fetchRooms()
            return true
        } catch (error: any) {
            console.error('Error al programar inicio de sala:', error)
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
        fetchRooms,
        fetchRoomDetails,
        goToRoom,
        cancelRoom,
        activateRoomNow,
        scheduleRoomStart
    }
}
