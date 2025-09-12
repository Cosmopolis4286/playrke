import { FastifyRequest, FastifyReply } from 'fastify'
import { PaymentService } from '../../services/payments/PaymentService'
import { 
  CreatePaymentSchema, 
  ProcessPaymentSchema,
  PaymentMethod 
} from '../../types/payments'
import { AppError, ErrorCode } from '../../types/errors'
import { logger } from '../../utils/logger'

export class PaymentController {
  private paymentService: PaymentService

  constructor() {
    this.paymentService = new PaymentService()
  }

  /**
   * POST /api/payments
   * Crear nuevo pago
   */
  async createPayment(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = CreatePaymentSchema.parse({
        ...body,
        userId
      })

      const payment = await this.paymentService.createPayment(validatedData)

      return reply.status(201).send({
        success: true,
        data: payment,
        message: 'Pago creado exitosamente'
      })
    } catch (error) {
      logger.error('Error en createPayment:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.BAD_REQUEST ? 400 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/payments/:id/process
   * Procesar pago
   */
  async processPayment(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = ProcessPaymentSchema.parse({
        paymentId: params.id,
        paymentData: body
      })

      const payment = await this.paymentService.processPayment(validatedData)

      return reply.status(200).send({
        success: true,
        data: payment,
        message: 'Pago procesado exitosamente'
      })
    } catch (error) {
      logger.error('Error en processPayment:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.BAD_REQUEST ? 400 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/payments/:id
   * Obtener pago por ID
   */
  async getPaymentById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const payment = await this.paymentService.getPaymentById(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: payment,
        message: 'Pago obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getPaymentById:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/payments
   * Listar mis pagos
   */
  async getMyPayments(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as any

      const filters = {
        status: query.status,
        method: query.method,
        currencyId: query.currencyId,
        dateFrom: query.dateFrom,
        dateTo: query.dateTo
      }

      const limit = query.limit ? parseInt(query.limit) : 20
      const offset = query.offset ? parseInt(query.offset) : 0

      const result = await this.paymentService.getUserPayments(userId, filters, limit, offset)

      return reply.status(200).send({
        success: true,
        data: result,
        message: 'Pagos obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyPayments:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/payments/:id/cancel
   * Cancelar pago
   */
  async cancelPayment(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const payment = await this.paymentService.cancelPayment(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: payment,
        message: 'Pago cancelado exitosamente'
      })
    } catch (error) {
      logger.error('Error en cancelPayment:', error)
      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.BAD_REQUEST ? 400 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/payments/stats
   * Obtener estadísticas de pagos
   */
  async getPaymentStats(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as { period?: string }

      const stats = await this.paymentService.getPaymentStats(userId, query.period)

      return reply.status(200).send({
        success: true,
        data: stats,
        message: 'Estadísticas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getPaymentStats:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/payments/methods
   * Obtener métodos de pago disponibles
   */
  async getPaymentMethods(request: FastifyRequest, reply: FastifyReply) {
    try {
      const paymentMethods = [
        {
          method: PaymentMethod.WALLET,
          name: 'Billetera Interna',
          description: 'Pago con saldo de billetera',
          isEnabled: true,
          minAmount: 1,
          maxAmount: 100000,
          fee: '0%',
          processingTime: 'Inmediato'
        },
        {
          method: PaymentMethod.CREDIT_CARD,
          name: 'Tarjeta de Crédito',
          description: 'Visa, Mastercard, American Express',
          isEnabled: true,
          minAmount: 5,
          maxAmount: 5000,
          fee: '2.9%',
          processingTime: '1-3 minutos'
        },
        {
          method: PaymentMethod.DEBIT_CARD,
          name: 'Tarjeta Débito',
          description: 'Tarjetas de débito bancarias',
          isEnabled: true,
          minAmount: 5,
          maxAmount: 2000,
          fee: '1.9%',
          processingTime: '1-3 minutos'
        },
        {
          method: PaymentMethod.BANK_TRANSFER,
          name: 'Transferencia Bancaria',
          description: 'Transferencia directa desde banco',
          isEnabled: true,
          minAmount: 10,
          maxAmount: 50000,
          fee: '$2 fijo',
          processingTime: '1-3 días hábiles'
        },
        {
          method: PaymentMethod.CRYPTO,
          name: 'Criptomonedas',
          description: 'Bitcoin, Ethereum, USDT',
          isEnabled: false,
          minAmount: 10,
          maxAmount: 10000,
          fee: '1%',
          processingTime: '10-60 minutos'
        },
        {
          method: PaymentMethod.PAYPAL,
          name: 'PayPal',
          description: 'Pago con cuenta PayPal',
          isEnabled: false,
          minAmount: 5,
          maxAmount: 5000,
          fee: '3.4%',
          processingTime: '1-5 minutos'
        }
      ].filter(method => method.isEnabled)

      return reply.status(200).send({
        success: true,
        data: paymentMethods,
        message: 'Métodos de pago obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getPaymentMethods:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/payments/webhook
   * Webhook para notificaciones de gateway de pago
   */
  async paymentWebhook(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const headers = request.headers as any

      // Verificar autenticidad del webhook
      // En producción deberías validar la firma del webhook

      logger.info('Webhook de pago recibido:', { body, headers })

      // Procesar webhook según el gateway
      // Esto se implementaría según el proveedor específico

      return reply.status(200).send({
        success: true,
        message: 'Webhook procesado'
      })
    } catch (error) {
      logger.error('Error procesando webhook:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error procesando webhook'
      })
    }
  }

  /**
   * GET /api/payments/currencies
   * Obtener monedas disponibles para pagos
   */
  async getAvailableCurrencies(request: FastifyRequest, reply: FastifyReply) {
    try {
      // En una implementación real, esto vendría de la base de datos
      const currencies = [
        {
          id: '1',
          code: 'USD',
          name: 'Dólar Estadounidense',
          symbol: '$',
          decimals: 2,
          isEnabled: true
        },
        {
          id: '2',
          code: 'EUR',
          name: 'Euro',
          symbol: '€',
          decimals: 2,
          isEnabled: true
        },
        {
          id: '3',
          code: 'COP',
          name: 'Peso Colombiano',
          symbol: '$',
          decimals: 0,
          isEnabled: true
        }
      ]

      return reply.status(200).send({
        success: true,
        data: currencies,
        message: 'Monedas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getAvailableCurrencies:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/payments/:id/refund
   * Solicitar reembolso de pago
   */
  async requestRefund(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as { reason: string, amount?: number }
      const userId = request.user!.id

      if (!body.reason || body.reason.trim().length < 10) {
        return reply.status(400).send({
          success: false,
          message: 'Se requiere una razón del reembolso (mínimo 10 caracteres)'
        })
      }

      // Obtener el pago
      const payment = await this.paymentService.getPaymentById(params.id, userId)

      if (payment.status !== 'COMPLETED') {
        return reply.status(400).send({
          success: false,
          message: 'Solo se pueden reembolsar pagos completados'
        })
      }

      // En una implementación real, aquí se procesaría el reembolso
      logger.info(`Solicitud de reembolso para pago ${params.id}`, {
        userId,
        reason: body.reason,
        requestedAmount: body.amount || payment.amount
      })

      return reply.status(200).send({
        success: true,
        message: 'Solicitud de reembolso enviada. Será procesada en 1-3 días hábiles.'
      })
    } catch (error) {
      logger.error('Error en requestRefund:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }
}