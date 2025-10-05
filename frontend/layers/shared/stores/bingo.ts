/**
 * Store de Bingo usando Pinia
 *
 * Gestiona el estado del juego de bingo en tiempo real:
 * - Sala actual
 * - Números cantados
 * - Cartones del jugador
 * - Ganadores
 */

import { defineStore } from 'pinia'

export interface BingoRoom {
  id: string
  name: string
  status: 'pending' | 'active' | 'finished' | 'cancelled'
  prize_pool: number
  currency: string
  current_players: number
  max_players: number
  start_date: string
}

export interface BingoCard {
  id: string
  room_id: string
  numbers: number[][]
  status: 'pending' | 'active' | 'winner' | 'loser'
  price: number
}

export interface Winner {
  id: string
  player_name: string
  card_id: string
  pattern: string
  prize: number
  timestamp: string
}

export interface BingoState {
  currentRoom: BingoRoom | null
  myCards: BingoCard[]
  calledNumbers: number[]
  currentNumber: number | null
  winners: Winner[]
  isGameActive: boolean
}

export const useBingoStore = defineStore('bingo', {
  state: (): BingoState => ({
    currentRoom: null,
    myCards: [],
    calledNumbers: [],
    currentNumber: null,
    winners: [],
    isGameActive: false
  }),

  getters: {
    /**
     * Obtiene el total de números cantados
     */
    totalNumbersCalled: (state) => state.calledNumbers.length,

    /**
     * Verifica si un número ha sido cantado
     */
    isNumberCalled: (state) => (number: number) => {
      return state.calledNumbers.includes(number)
    },

    /**
     * Obtiene los últimos 5 números cantados
     */
    lastFiveNumbers: (state) => {
      return state.calledNumbers.slice(-5).reverse()
    },

    /**
     * Verifica si el jugador tiene cartones activos
     */
    hasActiveCards: (state) => {
      return state.myCards.some(card => card.status === 'active')
    },

    /**
     * Obtiene solo los cartones activos
     */
    activeCards: (state) => {
      return state.myCards.filter(card => card.status === 'active')
    },

    /**
     * Obtiene los cartones ganadores
     */
    winningCards: (state) => {
      return state.myCards.filter(card => card.status === 'winner')
    },

    /**
     * Verifica si la sala está activa
     */
    isRoomActive: (state) => {
      return state.currentRoom?.status === 'active'
    }
  },

  actions: {
    /**
     * Establece la sala actual
     */
    setCurrentRoom(room: BingoRoom) {
      this.currentRoom = room
      this.isGameActive = room.status === 'active'
    },

    /**
     * Establece los cartones del jugador
     */
    setMyCards(cards: BingoCard[]) {
      this.myCards = cards
    },

    /**
     * Agrega un nuevo cartón
     */
    addCard(card: BingoCard) {
      this.myCards.push(card)
    },

    /**
     * Actualiza el estado de un cartón
     */
    updateCardStatus(cardId: string, status: BingoCard['status']) {
      const card = this.myCards.find(c => c.id === cardId)
      if (card) {
        card.status = status
      }
    },

    /**
     * Canta un nuevo número
     */
    callNumber(number: number) {
      if (!this.calledNumbers.includes(number)) {
        this.calledNumbers.push(number)
        this.currentNumber = number
      }
    },

    /**
     * Establece todos los números cantados (inicial)
     */
    setCalledNumbers(numbers: number[]) {
      this.calledNumbers = numbers
      this.currentNumber = numbers[numbers.length - 1] || null
    },

    /**
     * Agrega un ganador
     */
    addWinner(winner: Winner) {
      this.winners.push(winner)
    },

    /**
     * Establece todos los ganadores (inicial)
     */
    setWinners(winners: Winner[]) {
      this.winners = winners
    },

    /**
     * Inicia el juego
     */
    startGame() {
      this.isGameActive = true
      if (this.currentRoom) {
        this.currentRoom.status = 'active'
      }
    },

    /**
     * Finaliza el juego
     */
    endGame() {
      this.isGameActive = false
      if (this.currentRoom) {
        this.currentRoom.status = 'finished'
      }

      // Marcar cartones no ganadores como perdedores
      this.myCards.forEach(card => {
        if (card.status === 'active') {
          card.status = 'loser'
        }
      })
    },

    /**
     * Limpia el estado del juego
     */
    clearGame() {
      this.currentRoom = null
      this.myCards = []
      this.calledNumbers = []
      this.currentNumber = null
      this.winners = []
      this.isGameActive = false
    },

    /**
     * Resetea solo los números del juego (para nuevo juego)
     */
    resetDraw() {
      this.calledNumbers = []
      this.currentNumber = null
      this.winners = []
    }
  }
})

export type BingoStore = ReturnType<typeof useBingoStore>
