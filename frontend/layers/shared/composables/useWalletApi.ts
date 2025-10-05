/**
 * Composable para API de Billeteras
 *
 * Proporciona funciones para gestión de billeteras, transacciones y balances.
 */

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
  metadata?: any
  created_at: string
  updated_at: string
}

export interface DepositData {
  wallet_id: string
  amount: number
  payment_method: string
  payment_proof?: File
}

export interface WithdrawalData {
  wallet_id: string
  amount: number
  destination_account: string
}

export const useWalletApi = () => {
  const api = useApi()

  /**
   * Obtiene las billeteras del usuario
   */
  const getWallets = async () => {
    return api.get<{ data: Wallet[] }>('/wallets')
  }

  /**
   * Obtiene una billetera específica
   */
  const getWallet = async (walletId: string) => {
    return api.get<{ data: Wallet }>(`/wallets/${walletId}`)
  }

  /**
   * Obtiene el balance de una billetera
   */
  const getBalance = async (walletId: string) => {
    return api.get<{ data: { balance: number; currency: string } }>(`/wallets/${walletId}/balance`)
  }

  /**
   * Obtiene las transacciones de una billetera
   */
  const getTransactions = async (walletId: string, params?: {
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
    if (params?.dateFrom) query.append('dateFrom', params.dateFrom)
    if (params?.dateTo) query.append('dateTo', params.dateTo)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get<{ data: Transaction[]; meta: any }>(`/wallets/${walletId}/transactions?${query.toString()}`)
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

    return api.post<{ data: Transaction }>('/wallets/deposit', formData)
  }

  /**
   * Solicita un retiro
   */
  const requestWithdrawal = async (withdrawalData: WithdrawalData) => {
    return api.post<{ data: Transaction }>('/wallets/withdrawal', withdrawalData)
  }

  /**
   * Transfiere fondos entre billeteras
   */
  const transferFunds = async (fromWalletId: string, toWalletId: string, amount: number) => {
    return api.post<{ data: Transaction }>('/wallets/transfer', {
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
