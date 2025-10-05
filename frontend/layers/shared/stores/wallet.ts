/**
 * Store de Wallet usando Pinia
 *
 * Gestiona el estado de las billeteras y transacciones:
 * - Balances en tiempo real
 * - Transacciones pendientes
 * - Notificaciones de cambios
 */

import { defineStore } from 'pinia'

export interface Wallet {
  id: string
  user_id: string
  currency: string
  balance: number
  status: 'active' | 'frozen' | 'suspended'
  created_at: string
  updated_at: string
}

export interface Transaction {
  id: string
  wallet_id: string
  type: 'deposit' | 'withdrawal' | 'purchase' | 'prize' | 'refund'
  amount: number
  currency: string
  status: 'pending' | 'completed' | 'failed' | 'cancelled'
  description: string
  created_at: string
}

export interface WalletState {
  wallets: Wallet[]
  selectedWallet: Wallet | null
  recentTransactions: Transaction[]
  pendingTransactions: Transaction[]
  totalBalance: Record<string, number>
}

export const useWalletStore = defineStore('wallet', {
  state: (): WalletState => ({
    wallets: [],
    selectedWallet: null,
    recentTransactions: [],
    pendingTransactions: [],
    totalBalance: {}
  }),

  getters: {
    /**
     * Obtiene el balance de una moneda específica
     */
    getBalanceByCurrency: (state) => (currency: string) => {
      const wallet = state.wallets.find(w => w.currency === currency)
      return wallet?.balance || 0
    },

    /**
     * Obtiene todas las billeteras activas
     */
    activeWallets: (state) => {
      return state.wallets.filter(w => w.status === 'active')
    },

    /**
     * Verifica si tiene fondos suficientes en una moneda
     */
    hasSufficientFunds: (state) => (currency: string, amount: number) => {
      const wallet = state.wallets.find(w => w.currency === currency)
      return wallet ? wallet.balance >= amount : false
    },

    /**
     * Obtiene el total de transacciones pendientes
     */
    pendingTransactionsCount: (state) => {
      return state.pendingTransactions.length
    },

    /**
     * Obtiene las transacciones pendientes de aprobación
     */
    pendingDeposits: (state) => {
      return state.pendingTransactions.filter(t => t.type === 'deposit')
    },

    /**
     * Obtiene las transacciones pendientes de retiro
     */
    pendingWithdrawals: (state) => {
      return state.pendingTransactions.filter(t => t.type === 'withdrawal')
    },

    /**
     * Calcula el balance total en una moneda base (USD)
     */
    totalBalanceUSD: (state) => {
      // TODO: Implementar conversión de monedas
      return state.wallets.reduce((total, wallet) => {
        return total + (wallet.currency === 'USD' ? wallet.balance : 0)
      }, 0)
    }
  },

  actions: {
    /**
     * Establece todas las billeteras
     */
    setWallets(wallets: Wallet[]) {
      this.wallets = wallets

      // Calcular balance total por moneda
      this.totalBalance = wallets.reduce((acc, wallet) => {
        acc[wallet.currency] = wallet.balance
        return acc
      }, {} as Record<string, number>)

      // Establecer billetera seleccionada si no hay una
      if (!this.selectedWallet && wallets.length > 0) {
        this.selectedWallet = wallets[0]
      }
    },

    /**
     * Actualiza una billetera específica
     */
    updateWallet(walletId: string, updates: Partial<Wallet>) {
      const wallet = this.wallets.find(w => w.id === walletId)
      if (wallet) {
        Object.assign(wallet, updates)

        // Actualizar balance total
        if (updates.balance !== undefined) {
          this.totalBalance[wallet.currency] = updates.balance
        }

        // Actualizar billetera seleccionada si es la misma
        if (this.selectedWallet?.id === walletId) {
          this.selectedWallet = { ...wallet }
        }
      }
    },

    /**
     * Actualiza el balance de una billetera
     */
    updateBalance(currency: string, newBalance: number) {
      const wallet = this.wallets.find(w => w.currency === currency)
      if (wallet) {
        wallet.balance = newBalance
        this.totalBalance[currency] = newBalance

        if (this.selectedWallet?.currency === currency) {
          this.selectedWallet.balance = newBalance
        }
      }
    },

    /**
     * Incrementa el balance de una billetera
     */
    incrementBalance(currency: string, amount: number) {
      const wallet = this.wallets.find(w => w.currency === currency)
      if (wallet) {
        wallet.balance += amount
        this.totalBalance[currency] = wallet.balance

        if (this.selectedWallet?.currency === currency) {
          this.selectedWallet.balance = wallet.balance
        }
      }
    },

    /**
     * Decrementa el balance de una billetera
     */
    decrementBalance(currency: string, amount: number) {
      const wallet = this.wallets.find(w => w.currency === currency)
      if (wallet && wallet.balance >= amount) {
        wallet.balance -= amount
        this.totalBalance[currency] = wallet.balance

        if (this.selectedWallet?.currency === currency) {
          this.selectedWallet.balance = wallet.balance
        }
      }
    },

    /**
     * Establece la billetera seleccionada
     */
    selectWallet(walletId: string) {
      const wallet = this.wallets.find(w => w.id === walletId)
      if (wallet) {
        this.selectedWallet = wallet
      }
    },

    /**
     * Agrega una transacción reciente
     */
    addTransaction(transaction: Transaction) {
      this.recentTransactions.unshift(transaction)

      // Mantener solo las últimas 50
      if (this.recentTransactions.length > 50) {
        this.recentTransactions = this.recentTransactions.slice(0, 50)
      }

      // Si es pendiente, agregar a pendientes
      if (transaction.status === 'pending') {
        this.pendingTransactions.push(transaction)
      }
    },

    /**
     * Establece transacciones recientes
     */
    setRecentTransactions(transactions: Transaction[]) {
      this.recentTransactions = transactions
    },

    /**
     * Establece transacciones pendientes
     */
    setPendingTransactions(transactions: Transaction[]) {
      this.pendingTransactions = transactions
    },

    /**
     * Actualiza el estado de una transacción
     */
    updateTransactionStatus(transactionId: string, status: Transaction['status']) {
      // Actualizar en recientes
      const recentTx = this.recentTransactions.find(t => t.id === transactionId)
      if (recentTx) {
        recentTx.status = status
      }

      // Remover de pendientes si ya no está pendiente
      if (status !== 'pending') {
        this.pendingTransactions = this.pendingTransactions.filter(
          t => t.id !== transactionId
        )
      }

      // Si se completó, actualizar balance
      if (status === 'completed' && recentTx) {
        const wallet = this.wallets.find(w => w.id === recentTx.wallet_id)
        if (wallet) {
          if (recentTx.type === 'deposit' || recentTx.type === 'prize' || recentTx.type === 'refund') {
            this.incrementBalance(wallet.currency, recentTx.amount)
          } else if (recentTx.type === 'withdrawal' || recentTx.type === 'purchase') {
            this.decrementBalance(wallet.currency, recentTx.amount)
          }
        }
      }
    },

    /**
     * Limpia el estado de la billetera
     */
    clearWallet() {
      this.wallets = []
      this.selectedWallet = null
      this.recentTransactions = []
      this.pendingTransactions = []
      this.totalBalance = {}
    }
  },

  persist: {
    storage: persistedState.localStorage,
    paths: ['wallets', 'totalBalance']
  }
})

export type WalletStore = ReturnType<typeof useWalletStore>
