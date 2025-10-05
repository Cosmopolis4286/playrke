/**
 * Composable para API de Administración
 *
 * Proporciona funciones específicas para administradores del sistema.
 */

export interface DashboardStats {
  totalUsers: number
  activeUsers: number
  totalGames: number
  activeGames: number
  totalTransactions: number
  todayRevenue: number
  monthRevenue: number
  onlinePlayers: number
}

export interface ReportData {
  sales: any[]
  revenue: any[]
  users: any[]
  games: any[]
}

export const useAdminApi = () => {
  const api = useApi()

  /**
   * Obtiene estadísticas del dashboard
   */
  const getDashboardStats = async () => {
    return api.get<{ data: DashboardStats }>('/admin/dashboard/stats')
  }

  /**
   * Obtiene clientes (customers)
   */
  const getCustomers = async (params?: {
    search?: string
    status?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.search) query.append('search', params.search)
    if (params?.status) query.append('status', params.status)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get(`/admin/customers?${query.toString()}`)
  }

  /**
   * Crea un nuevo cliente
   */
  const createCustomer = async (customerData: any) => {
    return api.post('/admin/customers', customerData)
  }

  /**
   * Actualiza un cliente
   */
  const updateCustomer = async (customerId: string, customerData: any) => {
    return api.put(`/admin/customers/${customerId}`, customerData)
  }

  /**
   * Elimina un cliente
   */
  const deleteCustomer = async (customerId: string) => {
    return api.delete(`/admin/customers/${customerId}`)
  }

  /**
   * Obtiene tesoreros (treasurers)
   */
  const getTreasurers = async (params?: {
    search?: string
    status?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.search) query.append('search', params.search)
    if (params?.status) query.append('status', params.status)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get(`/admin/treasurers?${query.toString()}`)
  }

  /**
   * Crea un nuevo tesorero
   */
  const createTreasurer = async (treasurerData: any) => {
    return api.post('/admin/treasurers', treasurerData)
  }

  /**
   * Actualiza un tesorero
   */
  const updateTreasurer = async (treasurerId: string, treasurerData: any) => {
    return api.put(`/admin/treasurers/${treasurerId}`, treasurerData)
  }

  /**
   * Elimina un tesorero
   */
  const deleteTreasurer = async (treasurerId: string) => {
    return api.delete(`/admin/treasurers/${treasurerId}`)
  }

  /**
   * Obtiene vendedores (sellers)
   */
  const getSellers = async (params?: {
    search?: string
    status?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.search) query.append('search', params.search)
    if (params?.status) query.append('status', params.status)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get(`/admin/sellers?${query.toString()}`)
  }

  /**
   * Obtiene comercios
   */
  const getCommerces = async (params?: {
    search?: string
    status?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.search) query.append('search', params.search)
    if (params?.status) query.append('status', params.status)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get(`/admin/commerces?${query.toString()}`)
  }

  /**
   * Obtiene reportes
   */
  const getReports = async (params: {
    type: 'sales' | 'revenue' | 'users' | 'games'
    dateFrom: string
    dateTo: string
    currency?: string
  }) => {
    const query = new URLSearchParams()
    query.append('type', params.type)
    query.append('dateFrom', params.dateFrom)
    query.append('dateTo', params.dateTo)
    if (params.currency) query.append('currency', params.currency)

    return api.get<{ data: ReportData }>(`/admin/reports?${query.toString()}`)
  }

  /**
   * Obtiene configuración del sistema
   */
  const getSettings = async () => {
    return api.get('/admin/settings')
  }

  /**
   * Actualiza configuración del sistema
   */
  const updateSettings = async (settings: Record<string, any>) => {
    return api.put('/admin/settings', settings)
  }

  /**
   * Obtiene logs del sistema
   */
  const getLogs = async (params?: {
    level?: string
    dateFrom?: string
    dateTo?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.level) query.append('level', params.level)
    if (params?.dateFrom) query.append('dateFrom', params.dateFrom)
    if (params?.dateTo) query.append('dateTo', params.dateTo)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get(`/admin/logs?${query.toString()}`)
  }

  /**
   * Aprueba una transacción pendiente
   */
  const approveTransaction = async (transactionId: string) => {
    return api.post(`/admin/transactions/${transactionId}/approve`)
  }

  /**
   * Rechaza una transacción pendiente
   */
  const rejectTransaction = async (transactionId: string, reason: string) => {
    return api.post(`/admin/transactions/${transactionId}/reject`, { reason })
  }

  return {
    getDashboardStats,
    getCustomers,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    getTreasurers,
    createTreasurer,
    updateTreasurer,
    deleteTreasurer,
    getSellers,
    getCommerces,
    getReports,
    getSettings,
    updateSettings,
    getLogs,
    approveTransaction,
    rejectTransaction
  }
}
