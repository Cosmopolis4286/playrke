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

    return api.get<{ data: BingoRoom[] }>(`/bingo/rooms?${query.toString()}`)
  }

  /**
   * Obtiene información de una sala específica
   */
  const getRoom = async (roomId: string) => {
    return api.get<{ data: BingoRoom }>(`/bingo/rooms/${roomId}`)
  }

  /**
   * Compra un cartón de bingo
   */
  const purchaseCard = async (roomId: string, quantity: number = 1) => {
    return api.post<{
      message: string
      data: {
        cards: BingoCard[]
        prize_pool: number
        currency: string
        exchange_rate: number
      }
      user_cards_count: number
    }>(`/bingo/rooms/${roomId}/purchase`, {
      quantity
    })
  }

  /**
   * Obtiene los cartones del jugador en una sala
   */
  const getPlayerCards = async (roomId: string) => {
    return api.get<{ data: BingoCard[] }>(`/bingo/rooms/${roomId}/cards`)
  }

  /**
   * Obtiene el sorteo actual de una sala
   */
  const getDraw = async (roomId: string) => {
    return api.get<{ data: BingoDraw }>(`/bingo/rooms/${roomId}/draw`)
  }

  /**
   * Obtiene los números cantados
   */
  const getCalledNumbers = async (drawId: string) => {
    return api.get<{ data: number[] }>(`/bingo/draw/${drawId}/numbers`)
  }

  /**
   * Verifica si un jugador ganó (para marcar bingo)
   */
  const checkWinner = async (cardId: string, patternId?: string) => {
    return api.post<{ data: { isWinner: boolean; prize?: number } }>(`/bingo/cards/${cardId}/check-winner`, {
      pattern_id: patternId
    })
  }

  /**
   * Obtiene los ganadores de una sala
   */
  const getWinners = async (roomId: string) => {
    return api.get<{ data: any[] }>(`/bingo/rooms/${roomId}/winners`)
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

    return api.get<{ data: any[] }>(`/bingo/history?${query.toString()}`)
  }

  /**
   * Notifica pago de premio
   */
  const notifyPrizePayment = async (historyId: string, paymentInfo: any) => {
    return api.post(`/bingo/history/${historyId}/notify-payment`, {
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
