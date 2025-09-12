import { prisma } from '../../utils/database'
import { logger } from '../../utils/logger'
import { AppError, ErrorCode } from '../../types/errors'
import { WalletService } from './WalletService'
import type { 
  CreatePaymentRequest, 
  ProcessPaymentRequest,
  PaymentResponse, 
  PaymentListResponse,
  PaymentMethod,
  TransactionStatus,
  PaymentStats,
  PaymentMethodConfig 
} from '../../types/payments'

export class PaymentService {
  private walletService: WalletService

  constructor() {
    this.walletService = new WalletService()
  }

  /**
   * Crear nuevo pago
   */
  async createPayment(request: CreatePaymentRequest): Promise<PaymentResponse> {
    try {
      // Verificar que el usuario existe
      const user = await prisma.user.findUnique({
        where: { id: request.userId }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      // Verificar que la moneda existe
      const currency = await prisma.currency.findUnique({
        where: { id: request.currencyId }
      })

      if (!currency) {
        throw new AppError('Moneda no encontrada', ErrorCode.NOT_FOUND)
      }

      // Validar método de pago
      const paymentConfig = await this.getPaymentMethodConfig(request.method)
      if (!paymentConfig.isEnabled) {
        throw new AppError('Método de pago no disponible', ErrorCode.BAD_REQUEST)
      }

      // Validar límites de monto
      if (request.amount < paymentConfig.minAmount) {
        throw new AppError(`Monto mínimo es ${paymentConfig.minAmount}`, ErrorCode.BAD_REQUEST)
      }

      if (request.amount > paymentConfig.maxAmount) {
        throw new AppError(`Monto máximo es ${paymentConfig.maxAmount}`, ErrorCode.BAD_REQUEST)
      }

      // Calcular comisión
      const fee = this.calculatePaymentFee(request.amount, paymentConfig.fee)
      const totalAmount = request.amount + fee

      const payment = await prisma.payment.create({
        data: {
          userId: request.userId,
          amount: request.amount,
          fee,
          totalAmount,
          currencyId: request.currencyId,
          method: request.method,
          status: 'PENDING' as TransactionStatus,
          description: request.description,
          reference: this.generatePaymentReference(),
          metadata: request.metadata || {},
          expiresAt: new Date(Date.now() + 30 * 60 * 1000), // 30 minutos
          createdAt: new Date()
        },
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      logger.info(`Pago creado: ${payment.id}`, {
        userId: request.userId,
        amount: request.amount,
        method: request.method
      })

      return this.formatPaymentResponse(payment)
    } catch (error) {
      logger.error('Error creando pago:', error)
      throw error instanceof AppError ? error : new AppError('Error creando pago')
    }
  }

  /**
   * Procesar pago
   */
  async processPayment(request: ProcessPaymentRequest): Promise<PaymentResponse> {
    try {
      const payment = await prisma.payment.findUnique({
        where: { id: request.paymentId },
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      if (!payment) {
        throw new AppError('Pago no encontrado', ErrorCode.NOT_FOUND)
      }

      if (payment.status !== 'PENDING') {
        throw new AppError('El pago ya fue procesado', ErrorCode.BAD_REQUEST)
      }

      if (payment.expiresAt && new Date() > payment.expiresAt) {
        await this.updatePaymentStatus(request.paymentId, 'CANCELLED', 'Pago expirado')
        throw new AppError('El pago ha expirado', ErrorCode.BAD_REQUEST)
      }

      let updatedPayment: any

      try {
        // Procesar según el método de pago
        switch (payment.method) {
          case 'WALLET':
            updatedPayment = await this.processWalletPayment(payment, request.paymentData)
            break
          case 'CREDIT_CARD':
          case 'DEBIT_CARD':
            updatedPayment = await this.processCardPayment(payment, request.paymentData)
            break
          case 'BANK_TRANSFER':
            updatedPayment = await this.processBankTransfer(payment, request.paymentData)
            break
          case 'CRYPTO':
            updatedPayment = await this.processCryptoPayment(payment, request.paymentData)
            break
          default:
            throw new AppError('Método de pago no soportado', ErrorCode.BAD_REQUEST)
        }

        logger.info(`Pago procesado exitosamente: ${payment.id}`)
        return this.formatPaymentResponse(updatedPayment)

      } catch (processingError) {
        // Marcar pago como fallido
        await this.updatePaymentStatus(request.paymentId, 'FAILED', 
          processingError instanceof Error ? processingError.message : 'Error procesando pago')
        throw processingError
      }
    } catch (error) {
      logger.error('Error procesando pago:', error)
      throw error instanceof AppError ? error : new AppError('Error procesando pago')
    }
  }

  /**
   * Obtener pago por ID
   */
  async getPaymentById(paymentId: string, userId?: string): Promise<PaymentResponse> {
    try {
      const where: any = { id: paymentId }
      if (userId) {
        where.userId = userId
      }

      const payment = await prisma.payment.findUnique({
        where,
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      if (!payment) {
        throw new AppError('Pago no encontrado', ErrorCode.NOT_FOUND)
      }

      return this.formatPaymentResponse(payment)
    } catch (error) {
      logger.error('Error obteniendo pago:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo pago')
    }
  }

  /**
   * Listar pagos de un usuario
   */
  async getUserPayments(userId: string, filters?: any, limit = 20, offset = 0): Promise<PaymentListResponse> {
    try {
      const where: any = { userId }

      // Aplicar filtros
      if (filters?.status) {
        where.status = filters.status
      }

      if (filters?.method) {
        where.method = filters.method
      }

      if (filters?.currencyId) {
        where.currencyId = filters.currencyId
      }

      if (filters?.dateFrom) {
        where.createdAt = { ...where.createdAt, gte: new Date(filters.dateFrom) }
      }

      if (filters?.dateTo) {
        where.createdAt = { ...where.createdAt, lte: new Date(filters.dateTo) }
      }

      const [payments, total] = await Promise.all([
        prisma.payment.findMany({
          where,
          include: {
            currency: true,
            user: {
              select: { id: true, username: true, email: true }
            }
          },
          orderBy: { createdAt: 'desc' },
          take: limit,
          skip: offset
        }),
        prisma.payment.count({ where })
      ])

      return {
        payments: payments.map(payment => this.formatPaymentResponse(payment)),
        total,
        page: Math.floor(offset / limit) + 1,
        totalPages: Math.ceil(total / limit)
      }
    } catch (error) {
      logger.error('Error listando pagos:', error)
      throw new AppError('Error obteniendo pagos')
    }
  }

  /**
   * Cancelar pago
   */
  async cancelPayment(paymentId: string, userId?: string): Promise<PaymentResponse> {
    try {
      const payment = await this.getPaymentById(paymentId, userId)

      if (payment.status !== 'PENDING') {
        throw new AppError('Solo se pueden cancelar pagos pendientes', ErrorCode.BAD_REQUEST)
      }

      const updatedPayment = await this.updatePaymentStatus(paymentId, 'CANCELLED', 'Cancelado por el usuario')

      logger.info(`Pago cancelado: ${paymentId}`)
      return this.formatPaymentResponse(updatedPayment)
    } catch (error) {
      logger.error('Error cancelando pago:', error)
      throw error instanceof AppError ? error : new AppError('Error cancelando pago')
    }
  }

  /**
   * Obtener estadísticas de pagos
   */
  async getPaymentStats(userId?: string, period = '30d'): Promise<PaymentStats> {
    try {
      const where: any = {}
      if (userId) {
        where.userId = userId
      }

      // Calcular fecha de inicio según el período
      const now = new Date()
      const startDate = new Date()
      switch (period) {
        case '7d':
          startDate.setDate(now.getDate() - 7)
          break
        case '30d':
          startDate.setDate(now.getDate() - 30)
          break
        case '90d':
          startDate.setDate(now.getDate() - 90)
          break
        default:
          startDate.setDate(now.getDate() - 30)
      }

      where.createdAt = { gte: startDate }

      const payments = await prisma.payment.findMany({
        where,
        include: { currency: true }
      })

      const totalPayments = payments.length
      const totalAmount = payments.reduce((sum, p) => sum + p.amount, 0)
      const successfulPayments = payments.filter(p => p.status === 'COMPLETED').length
      const failedPayments = payments.filter(p => p.status === 'FAILED').length
      const pendingPayments = payments.filter(p => p.status === 'PENDING').length

      // Agrupar por método de pago
      const paymentsByMethod = Object.values(PaymentMethod).map(method => {
        const methodPayments = payments.filter(p => p.method === method)
        return {
          method,
          count: methodPayments.length,
          totalAmount: methodPayments.reduce((sum, p) => sum + p.amount, 0)
        }
      }).filter(m => m.count > 0)

      const averagePaymentAmount = totalPayments > 0 ? totalAmount / totalPayments : 0

      // Tendencias diarias (simplificado)
      const paymentTrends: Array<{ date: Date, count: number, amount: number }> = []
      // Implementar lógica de tendencias según necesidades

      return {
        totalPayments,
        totalAmount,
        successfulPayments,
        failedPayments,
        pendingPayments,
        paymentsByMethod,
        averagePaymentAmount,
        paymentTrends
      }
    } catch (error) {
      logger.error('Error obteniendo estadísticas de pagos:', error)
      throw new AppError('Error obteniendo estadísticas')
    }
  }

  /**
   * Procesar pago con billetera
   */
  private async processWalletPayment(payment: any, paymentData: any): Promise<any> {
    const { walletId } = paymentData

    if (!walletId) {
      throw new AppError('ID de billetera requerido', ErrorCode.BAD_REQUEST)
    }

    // Retirar fondos de la billetera
    await this.walletService.withdrawFunds(
      walletId, 
      payment.totalAmount, 
      payment.description,
      payment.userId,
      { paymentId: payment.id }
    )

    return await this.updatePaymentStatus(payment.id, 'COMPLETED', 'Pago completado con billetera')
  }

  /**
   * Procesar pago con tarjeta
   */
  private async processCardPayment(payment: any, paymentData: any): Promise<any> {
    // Aquí integrarías con un gateway de pagos como Stripe, PayPal, etc.
    // Por ahora simulamos el procesamiento
    
    const { cardToken, cvv } = paymentData

    if (!cardToken) {
      throw new AppError('Token de tarjeta requerido', ErrorCode.BAD_REQUEST)
    }

    // Simular procesamiento
    await new Promise(resolve => setTimeout(resolve, 2000))

    // Simular éxito/fallo (90% éxito)
    const success = Math.random() > 0.1

    if (!success) {
      throw new AppError('Pago rechazado por el banco', ErrorCode.BAD_REQUEST)
    }

    return await this.updatePaymentStatus(payment.id, 'COMPLETED', 'Pago completado con tarjeta')
  }

  /**
   * Procesar transferencia bancaria
   */
  private async processBankTransfer(payment: any, paymentData: any): Promise<any> {
    // Marcar como pendiente de confirmación bancaria
    return await this.updatePaymentStatus(payment.id, 'PENDING', 'Pendiente de confirmación bancaria')
  }

  /**
   * Procesar pago con criptomonedas
   */
  private async processCryptoPayment(payment: any, paymentData: any): Promise<any> {
    const { cryptoAddress, transactionHash } = paymentData

    if (!cryptoAddress || !transactionHash) {
      throw new AppError('Dirección y hash de transacción requeridos', ErrorCode.BAD_REQUEST)
    }

    // Verificar transacción en blockchain (simulado)
    await new Promise(resolve => setTimeout(resolve, 3000))

    return await this.updatePaymentStatus(payment.id, 'COMPLETED', 'Pago completado con criptomonedas')
  }

  /**
   * Actualizar estado del pago
   */
  private async updatePaymentStatus(paymentId: string, status: TransactionStatus, notes?: string): Promise<any> {
    return await prisma.payment.update({
      where: { id: paymentId },
      data: {
        status,
        notes,
        processedAt: status === 'COMPLETED' ? new Date() : undefined,
        updatedAt: new Date()
      },
      include: {
        currency: true,
        user: {
          select: { id: true, username: true, email: true }
        }
      }
    })
  }

  /**
   * Obtener configuración de método de pago
   */
  private async getPaymentMethodConfig(method: PaymentMethod): Promise<PaymentMethodConfig> {
    // En una implementación real, esto vendría de la base de datos
    const defaultConfigs: Record<PaymentMethod, PaymentMethodConfig> = {
      WALLET: {
        method: PaymentMethod.WALLET,
        isEnabled: true,
        minAmount: 1,
        maxAmount: 100000,
        fee: { type: 'FIXED', value: 0 },
        supportedCurrencies: ['USD', 'EUR', 'COP'],
        processingTime: 'Inmediato',
        description: 'Pago con billetera interna'
      },
      CREDIT_CARD: {
        method: PaymentMethod.CREDIT_CARD,
        isEnabled: true,
        minAmount: 5,
        maxAmount: 5000,
        fee: { type: 'PERCENTAGE', value: 2.9 },
        supportedCurrencies: ['USD', 'EUR'],
        processingTime: '1-3 minutos',
        description: 'Pago con tarjeta de crédito'
      },
      DEBIT_CARD: {
        method: PaymentMethod.DEBIT_CARD,
        isEnabled: true,
        minAmount: 5,
        maxAmount: 2000,
        fee: { type: 'PERCENTAGE', value: 1.9 },
        supportedCurrencies: ['USD', 'EUR'],
        processingTime: '1-3 minutos',
        description: 'Pago con tarjeta débito'
      },
      BANK_TRANSFER: {
        method: PaymentMethod.BANK_TRANSFER,
        isEnabled: true,
        minAmount: 10,
        maxAmount: 50000,
        fee: { type: 'FIXED', value: 2 },
        supportedCurrencies: ['USD', 'EUR', 'COP'],
        processingTime: '1-3 días hábiles',
        description: 'Transferencia bancaria'
      },
      CRYPTO: {
        method: PaymentMethod.CRYPTO,
        isEnabled: false,
        minAmount: 10,
        maxAmount: 10000,
        fee: { type: 'PERCENTAGE', value: 1 },
        supportedCurrencies: ['USD'],
        processingTime: '10-60 minutos',
        description: 'Pago con criptomonedas'
      },
      PAYPAL: {
        method: PaymentMethod.PAYPAL,
        isEnabled: false,
        minAmount: 5,
        maxAmount: 5000,
        fee: { type: 'PERCENTAGE', value: 3.4 },
        supportedCurrencies: ['USD', 'EUR'],
        processingTime: '1-5 minutos',
        description: 'Pago con PayPal'
      },
      OTHER: {
        method: PaymentMethod.OTHER,
        isEnabled: false,
        minAmount: 1,
        maxAmount: 1000,
        fee: { type: 'FIXED', value: 0 },
        supportedCurrencies: ['USD'],
        processingTime: 'Variable',
        description: 'Otros métodos de pago'
      }
    }

    return defaultConfigs[method]
  }

  /**
   * Calcular comisión del pago
   */
  private calculatePaymentFee(amount: number, feeConfig: { type: 'FIXED' | 'PERCENTAGE', value: number }): number {
    if (feeConfig.type === 'FIXED') {
      return feeConfig.value
    } else {
      return amount * (feeConfig.value / 100)
    }
  }

  /**
   * Generar referencia única para el pago
   */
  private generatePaymentReference(): string {
    return `PAY_${Date.now()}_${Math.random().toString(36).substring(2, 8).toUpperCase()}`
  }

  /**
   * Formatear respuesta de pago
   */
  private formatPaymentResponse(payment: any): PaymentResponse {
    return {
      id: payment.id,
      userId: payment.userId,
      amount: payment.amount,
      currency: {
        id: payment.currency.id,
        code: payment.currency.code,
        name: payment.currency.name,
        symbol: payment.currency.symbol,
        decimals: payment.currency.decimals
      },
      method: payment.method,
      status: payment.status,
      description: payment.description,
      reference: payment.reference,
      paymentUrl: payment.paymentUrl,
      metadata: payment.metadata,
      createdAt: payment.createdAt,
      updatedAt: payment.updatedAt,
      expiresAt: payment.expiresAt
    }
  }
}