/**
 * Composable para API de Billeteras
 *
 * Proporciona funciones para gestión de billeteras, transacciones y balances.
 */

import type { Wallet, Transaction, DepositData, WithdrawalData } from '~/types/wallet'

export const useWalletApi = () => {
  const api = useApi()

  /**
   * Obtiene las billeteras del usuario
   */
  const getWallets = async () => {
    return api.get<{ success: boolean; wallets: Wallet[] }>('/wallet')
  }

  /**
   * Obtiene una billetera específica
   */
  const getWallet = async (walletId: string) => {
    return api.get<{ data: Wallet }>(`/wallet/${walletId}`)
  }

  /**
   * Obtiene el balance de una billetera
   */
  const getBalance = async (walletId?: string) => {
    return api.get<{ data: { balance: number; currency: string } }>('/wallet/balance')
  }

  /**
   * Obtiene las transacciones de una billetera
   */
  const getTransactions = async (walletId?: string, params?: {
    type?: string
    status?: string
    dateFrom?: string
    dateTo?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.type) query.append('type', params.type)
    if (params?.status) query.append('status', params.status)
    if (params?.dateFrom) query.append('from', params.dateFrom)
    if (params?.dateTo) query.append('to', params.dateTo)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('per_page', params.perPage.toString())

    const queryString = query.toString()
    return api.get<{ success: boolean; transactions: Transaction[]; pagination: any }>(`/wallet/transactions${queryString ? '?' + queryString : ''}`)
  }

  /**
   * Solicita un depósito
   */
  const requestDeposit = async (depositData: DepositData) => {
    const formData = new FormData()
    formData.append('wallet_id', depositData.wallet_id)
    formData.append('amount', depositData.amount.toString())
    formData.append('payment_method', depositData.payment_method)
    if (depositData.payment_proof) {
      formData.append('payment_proof', depositData.payment_proof)
    }

    return api.post<{ data: Transaction }>('/wallet/deposit', formData)
  }

  /**
   * Solicita un retiro
   */
  const requestWithdrawal = async (withdrawalData: WithdrawalData) => {
    return api.post<{ data: Transaction }>('/wallet/withdraw', withdrawalData)
  }

  /**
   * Transfiere fondos entre billeteras
   */
  const transferFunds = async (fromWalletId: string, toWalletId: string, amount: number) => {
    return api.post<{ data: Transaction }>('/wallet/transfer', {
      from_wallet_id: fromWalletId,
      to_wallet_id: toWalletId,
      amount
    })
  }

  /**
   * Obtiene el historial de transacciones del usuario
   */
  const getTransactionHistory = async (params?: {
    currency?: string
    type?: string
    dateFrom?: string
    dateTo?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.currency) query.append('currency', params.currency)
    if (params?.type) query.append('type', params.type)
    if (params?.dateFrom) query.append('dateFrom', params.dateFrom)
    if (params?.dateTo) query.append('dateTo', params.dateTo)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get<{ data: Transaction[]; meta: any }>(`/transactions?${query.toString()}`)
  }

  /**
   * Cancela una transacción pendiente
   */
  const cancelTransaction = async (transactionId: string) => {
    return api.post(`/transactions/${transactionId}/cancel`)
  }

  /**
   * Obtiene los métodos de pago disponibles
   */
  const getPaymentMethods = async (currency?: string) => {
    const query = currency ? `?currency=${currency}` : ''
    return api.get<{ data: any[] }>(`/payment-methods${query}`)
  }

  return {
    getWallets,
    getWallet,
    getBalance,
    getTransactions,
    requestDeposit,
    requestWithdrawal,
    transferFunds,
    getTransactionHistory,
    cancelTransaction,
    getPaymentMethods
  }
}
