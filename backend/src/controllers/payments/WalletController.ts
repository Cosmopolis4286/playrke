import { FastifyRequest, FastifyReply } from 'fastify'
import { WalletService } from '../../services/payments/WalletService'
import { 
  CreateWalletSchema, 
  AddFundsSchema, 
  WithdrawFundsSchema, 
  TransferFundsSchema 
} from '../../types/payments'
import { AppError, ErrorCode } from '../../types/errors'
import { logger } from '../../utils/logger'

export class WalletController {
  private walletService: WalletService

  constructor() {
    this.walletService = new WalletService()
  }

  /**
   * POST /api/wallets
   * Crear nueva billetera
   */
  async createWallet(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = CreateWalletSchema.parse({
        ...body,
        userId
      })

      const wallet = await this.walletService.createWallet(validatedData)

      return reply.status(201).send({
        success: true,
        data: wallet,
        message: 'Billetera creada exitosamente'
      })
    } catch (error) {
      logger.error('Error en createWallet:', error)
      
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
   * GET /api/wallets
   * Obtener mis billeteras
   */
  async getMyWallets(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as { includeInactive?: boolean }

      const wallets = await this.walletService.getUserWallets(
        userId, 
        query.includeInactive || false
      )

      return reply.status(200).send({
        success: true,
        data: wallets,
        message: 'Billeteras obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyWallets:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/wallets/:id
   * Obtener billetera por ID
   */
  async getWalletById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const wallet = await this.walletService.getWalletById(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: wallet,
        message: 'Billetera obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getWalletById:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/wallets/:id/balance
   * Obtener balance de billetera
   */
  async getWalletBalance(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const balance = await this.walletService.getWalletBalance(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: balance,
        message: 'Balance obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getWalletBalance:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/wallets/:id/add-funds
   * Agregar fondos
   */
  async addFunds(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = AddFundsSchema.parse({
        ...body,
        walletId: params.id
      })

      const result = await this.walletService.addFunds(
        validatedData.walletId,
        validatedData.amount,
        validatedData.description,
        userId,
        validatedData.metadata
      )

      return reply.status(200).send({
        success: true,
        data: {
          wallet: result.wallet,
          transaction: {
            id: result.transaction.id,
            amount: result.transaction.amount,
            type: result.transaction.type,
            status: result.transaction.status,
            reference: result.transaction.reference
          }
        },
        message: 'Fondos agregados exitosamente'
      })
    } catch (error) {
      logger.error('Error en addFunds:', error)
      
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
   * POST /api/wallets/:id/withdraw-funds
   * Retirar fondos
   */
  async withdrawFunds(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = WithdrawFundsSchema.parse({
        ...body,
        walletId: params.id
      })

      const result = await this.walletService.withdrawFunds(
        validatedData.walletId,
        validatedData.amount,
        validatedData.description,
        userId,
        validatedData.metadata
      )

      return reply.status(200).send({
        success: true,
        data: {
          wallet: result.wallet,
          transaction: {
            id: result.transaction.id,
            amount: result.transaction.amount,
            type: result.transaction.type,
            status: result.transaction.status,
            reference: result.transaction.reference
          }
        },
        message: 'Fondos retirados exitosamente'
      })
    } catch (error) {
      logger.error('Error en withdrawFunds:', error)
      
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
   * POST /api/wallets/transfer
   * Transferir entre billeteras
   */
  async transferFunds(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = TransferFundsSchema.parse(body)

      const result = await this.walletService.transferFunds({
        ...validatedData,
        userId
      })

      return reply.status(200).send({
        success: true,
        data: {
          fromWallet: result.fromWallet,
          toWallet: result.toWallet,
          transaction: {
            id: result.transaction.id,
            amount: result.transaction.amount,
            type: result.transaction.type,
            status: result.transaction.status,
            reference: result.transaction.reference
          }
        },
        message: 'Transferencia completada exitosamente'
      })
    } catch (error) {
      logger.error('Error en transferFunds:', error)
      
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
   * GET /api/wallets/:id/transactions
   * Obtener historial de transacciones
   */
  async getTransactionHistory(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const query = request.query as any
      const userId = request.user!.id

      const limit = query.limit ? parseInt(query.limit) : 20
      const offset = query.offset ? parseInt(query.offset) : 0

      const history = await this.walletService.getTransactionHistory(
        params.id, 
        userId, 
        limit, 
        offset
      )

      return reply.status(200).send({
        success: true,
        data: history,
        message: 'Historial obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getTransactionHistory:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/wallets/:id/toggle-status
   * Activar/desactivar billetera
   */
  async toggleWalletStatus(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as { isActive: boolean }
      const userId = request.user!.id

      if (typeof body.isActive !== 'boolean') {
        return reply.status(400).send({
          success: false,
          message: 'El campo isActive es requerido y debe ser booleano'
        })
      }

      const wallet = await this.walletService.toggleWalletStatus(
        params.id, 
        body.isActive, 
        userId
      )

      return reply.status(200).send({
        success: true,
        data: wallet,
        message: `Billetera ${body.isActive ? 'activada' : 'desactivada'} exitosamente`
      })
    } catch (error) {
      logger.error('Error en toggleWalletStatus:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/wallets/summary
   * Resumen de billeteras del usuario
   */
  async getWalletSummary(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id

      const wallets = await this.walletService.getUserWallets(userId, false)
      
      const summary = {
        totalWallets: wallets.length,
        totalBalance: wallets.reduce((sum, wallet) => sum + wallet.balance, 0),
        activeCurrencies: [...new Set(wallets.map(w => w.currency.code))],
        primaryWallet: wallets.find(w => w.isPrimary) || null,
        recentTransactions: [] // Se implementaría con una consulta adicional
      }

      return reply.status(200).send({
        success: true,
        data: summary,
        message: 'Resumen obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getWalletSummary:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }
}