/**
 * Composable para API de Bingo
 *
 * Proporciona funciones para interactuar con el sistema de bingo:
 * salas, cartones, sorteos, premios, etc.
 */

import type { BingoRoom, BingoCard, BingoDraw } from '~/types/bingo'

export const useBingoApi = () => {
  const api = useApi()

  /**
   * Obtiene las salas de bingo disponibles
   */
  const getRooms = async (params?: {
    currency?: string
    roomType?: 'automatic' | 'manual' | 'public'
  }) => {
    const query = new URLSearchParams()
    if (params?.currency) query.append('currency', params.currency)
    if (params?.roomType) query.append('roomType', params.roomType)

    return api.get<{ data: BingoRoom[] }>(`/bingo/game-rooms?${query.toString()}`)
  }

  /**
   * Obtiene información de una sala específica
   */
  const getRoom = async (roomId: string) => {
    return api.get<{ data: BingoRoom }>(`/bingo/room/${roomId}`)
  }

  /**
   * Compra un cartón de bingo
   */
  const purchaseCard = async (roomId: string, quantity: number = 1) => {
    return api.post<{ data: BingoCard[] }>('/bingo/carton/generate/store', {
      room_id: roomId,
      quantity
    })
  }

  /**
   * Obtiene los cartones del jugador en una sala
   */
  const getPlayerCards = async (roomId: string) => {
    return api.get<{ data: BingoCard[] }>(`/bingo/room/${roomId}/my-cards`)
  }

  /**
   * Obtiene el sorteo actual de una sala
   */
  const getDraw = async (roomId: string) => {
    return api.get<{ data: BingoDraw }>(`/bingo/get-draw/${roomId}`)
  }

  /**
   * Obtiene los números cantados
   */
  const getCalledNumbers = async (roomId: string) => {
    return api.get<{ data: number[] }>(`/bingo/get/chips/${roomId}`)
  }

  /**
   * Verifica si un jugador ganó (para marcar bingo)
   */
  const checkWinner = async (roomId: string, cardId: string, patternId: string) => {
    return api.post<{ data: { isWinner: boolean; prize?: number } }>('/bingo/check-winner', {
      room_id: roomId,
      card_id: cardId,
      pattern_id: patternId
    })
  }

  /**
   * Obtiene los ganadores de una sala
   */
  const getWinners = async (roomId: string) => {
    return api.get<{ data: any[] }>(`/bingo/winners/room/${roomId}`)
  }

  /**
   * Obtiene el historial de juegos del jugador
   */
  const getPlayerHistory = async (params?: {
    currency?: string
    dateFilter?: string
  }) => {
    const query = new URLSearchParams()
    if (params?.currency) query.append('currency', params.currency)
    if (params?.dateFilter) query.append('dateFilter', params.dateFilter)

    return api.get<{ data: any[] }>(`/bingo/cartons-sold?${query.toString()}`)
  }

  /**
   * Notifica pago de premio
   */
  const notifyPrizePayment = async (cardId: string, paymentInfo: any) => {
    return api.post('/bingo/carton/notify/payment/win-ctn', {
      card_id: cardId,
      ...paymentInfo
    })
  }

  return {
    getRooms,
    getRoom,
    purchaseCard,
    getPlayerCards,
    getDraw,
    getCalledNumbers,
    checkWinner,
    getWinners,
    getPlayerHistory,
    notifyPrizePayment
  }
}
