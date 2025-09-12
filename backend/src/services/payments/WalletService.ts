import { prisma } from '../../utils/database'
import { logger } from '../../utils/logger'
import { AppError, ErrorCode } from '../../types/errors'
import type { 
  CreateWalletRequest, 
  WalletResponse, 
  WalletBalance,
  WalletTransferRequest,
  WalletTransactionHistory 
} from '../../types/payments'

export class WalletService {

  /**
   * Crear nueva billetera para un usuario
   */
  async createWallet(request: CreateWalletRequest): Promise<WalletResponse> {
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

      // Verificar si ya existe una billetera para esta combinación
      const existingWallet = await prisma.wallet.findFirst({
        where: {
          userId: request.userId,
          currencyId: request.currencyId
        }
      })

      if (existingWallet) {
        throw new AppError('Ya existe una billetera para esta moneda', ErrorCode.BAD_REQUEST)
      }

      // Si es la primera billetera, marcarla como principal
      const userWalletsCount = await prisma.wallet.count({
        where: { userId: request.userId }
      })
      const isPrimary = userWalletsCount === 0 || request.isPrimary

      const wallet = await prisma.wallet.create({
        data: {
          userId: request.userId,
          currencyId: request.currencyId,
          balance: request.initialBalance || 0,
          isActive: true,
          isPrimary,
          createdAt: new Date()
        },
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      logger.info(`Billetera creada: ${wallet.id}`, {
        userId: request.userId,
        currencyId: request.currencyId,
        balance: wallet.balance
      })

      return this.formatWalletResponse(wallet)
    } catch (error) {
      logger.error('Error creando billetera:', error)
      throw error instanceof AppError ? error : new AppError('Error creando billetera')
    }
  }

  /**
   * Obtener billeteras de un usuario
   */
  async getUserWallets(userId: string, includeInactive = false): Promise<WalletResponse[]> {
    try {
      const where: any = { userId }
      if (!includeInactive) {
        where.isActive = true
      }

      const wallets = await prisma.wallet.findMany({
        where,
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        },
        orderBy: [
          { isPrimary: 'desc' },
          { createdAt: 'asc' }
        ]
      })

      return wallets.map(wallet => this.formatWalletResponse(wallet))
    } catch (error) {
      logger.error('Error obteniendo billeteras del usuario:', error)
      throw new AppError('Error obteniendo billeteras')
    }
  }

  /**
   * Obtener billetera por ID
   */
  async getWalletById(walletId: string, userId?: string): Promise<WalletResponse> {
    try {
      const where: any = { id: walletId }
      if (userId) {
        where.userId = userId
      }

      const wallet = await prisma.wallet.findUnique({
        where,
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      if (!wallet) {
        throw new AppError('Billetera no encontrada', ErrorCode.NOT_FOUND)
      }

      return this.formatWalletResponse(wallet)
    } catch (error) {
      logger.error('Error obteniendo billetera:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo billetera')
    }
  }

  /**
   * Obtener balance de una billetera
   */
  async getWalletBalance(walletId: string, userId?: string): Promise<WalletBalance> {
    try {
      const wallet = await this.getWalletById(walletId, userId)
      
      return {
        walletId: wallet.id,
        balance: wallet.balance,
        currency: wallet.currency,
        lastUpdated: wallet.updatedAt
      }
    } catch (error) {
      logger.error('Error obteniendo balance:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo balance')
    }
  }

  /**
   * Agregar fondos a una billetera
   */
  async addFunds(walletId: string, amount: number, description: string, userId?: string, metadata?: any): Promise<{ wallet: WalletResponse, transaction: any }> {
    try {
      if (amount <= 0) {
        throw new AppError('El monto debe ser mayor a cero', ErrorCode.BAD_REQUEST)
      }

      const wallet = await this.getWalletById(walletId, userId)

      if (!wallet.isActive) {
        throw new AppError('La billetera está inactiva', ErrorCode.BAD_REQUEST)
      }

      const result = await prisma.$transaction(async (tx) => {
        // Actualizar balance
        const updatedWallet = await tx.wallet.update({
          where: { id: walletId },
          data: {
            balance: { increment: amount },
            updatedAt: new Date()
          },
          include: {
            currency: true,
            user: {
              select: { id: true, username: true, email: true }
            }
          }
        })

        // Crear transacción
        const transaction = await tx.transaction.create({
          data: {
            userId: wallet.userId,
            walletId,
            type: 'CREDIT',
            amount,
            currencyId: wallet.currency.id,
            description,
            status: 'COMPLETED',
            reference: `CREDIT_${Date.now()}`,
            metadata: metadata || {},
            createdAt: new Date()
          }
        })

        return { updatedWallet, transaction }
      })

      logger.info(`Fondos agregados a billetera ${walletId}`, {
        amount,
        newBalance: result.updatedWallet.balance,
        userId: wallet.userId
      })

      return {
        wallet: this.formatWalletResponse(result.updatedWallet),
        transaction: result.transaction
      }
    } catch (error) {
      logger.error('Error agregando fondos:', error)
      throw error instanceof AppError ? error : new AppError('Error agregando fondos')
    }
  }

  /**
   * Retirar fondos de una billetera
   */
  async withdrawFunds(walletId: string, amount: number, description: string, userId?: string, metadata?: any): Promise<{ wallet: WalletResponse, transaction: any }> {
    try {
      if (amount <= 0) {
        throw new AppError('El monto debe ser mayor a cero', ErrorCode.BAD_REQUEST)
      }

      const wallet = await this.getWalletById(walletId, userId)

      if (!wallet.isActive) {
        throw new AppError('La billetera está inactiva', ErrorCode.BAD_REQUEST)
      }

      if (wallet.balance < amount) {
        throw new AppError('Saldo insuficiente', ErrorCode.BAD_REQUEST)
      }

      const result = await prisma.$transaction(async (tx) => {
        // Actualizar balance
        const updatedWallet = await tx.wallet.update({
          where: { id: walletId },
          data: {
            balance: { decrement: amount },
            updatedAt: new Date()
          },
          include: {
            currency: true,
            user: {
              select: { id: true, username: true, email: true }
            }
          }
        })

        // Crear transacción
        const transaction = await tx.transaction.create({
          data: {
            userId: wallet.userId,
            walletId,
            type: 'DEBIT',
            amount,
            currencyId: wallet.currency.id,
            description,
            status: 'COMPLETED',
            reference: `DEBIT_${Date.now()}`,
            metadata: metadata || {},
            createdAt: new Date()
          }
        })

        return { updatedWallet, transaction }
      })

      logger.info(`Fondos retirados de billetera ${walletId}`, {
        amount,
        newBalance: result.updatedWallet.balance,
        userId: wallet.userId
      })

      return {
        wallet: this.formatWalletResponse(result.updatedWallet),
        transaction: result.transaction
      }
    } catch (error) {
      logger.error('Error retirando fondos:', error)
      throw error instanceof AppError ? error : new AppError('Error retirando fondos')
    }
  }

  /**
   * Transferir entre billeteras
   */
  async transferFunds(request: WalletTransferRequest): Promise<{ fromWallet: WalletResponse, toWallet: WalletResponse, transaction: any }> {
    try {
      if (request.amount <= 0) {
        throw new AppError('El monto debe ser mayor a cero', ErrorCode.BAD_REQUEST)
      }

      if (request.fromWalletId === request.toWalletId) {
        throw new AppError('No se puede transferir a la misma billetera', ErrorCode.BAD_REQUEST)
      }

      const [fromWallet, toWallet] = await Promise.all([
        this.getWalletById(request.fromWalletId, request.userId),
        this.getWalletById(request.toWalletId)
      ])

      if (fromWallet.currency.id !== toWallet.currency.id) {
        throw new AppError('Las billeteras deben tener la misma moneda', ErrorCode.BAD_REQUEST)
      }

      if (fromWallet.balance < request.amount) {
        throw new AppError('Saldo insuficiente', ErrorCode.BAD_REQUEST)
      }

      if (!fromWallet.isActive || !toWallet.isActive) {
        throw new AppError('Una o ambas billeteras están inactivas', ErrorCode.BAD_REQUEST)
      }

      const result = await prisma.$transaction(async (tx) => {
        // Debitar de la billetera origen
        const updatedFromWallet = await tx.wallet.update({
          where: { id: request.fromWalletId },
          data: {
            balance: { decrement: request.amount },
            updatedAt: new Date()
          },
          include: {
            currency: true,
            user: {
              select: { id: true, username: true, email: true }
            }
          }
        })

        // Acreditar a la billetera destino
        const updatedToWallet = await tx.wallet.update({
          where: { id: request.toWalletId },
          data: {
            balance: { increment: request.amount },
            updatedAt: new Date()
          },
          include: {
            currency: true,
            user: {
              select: { id: true, username: true, email: true }
            }
          }
        })

        // Crear transacción de débito
        await tx.transaction.create({
          data: {
            userId: fromWallet.userId,
            walletId: request.fromWalletId,
            type: 'DEBIT',
            amount: request.amount,
            currencyId: fromWallet.currency.id,
            description: request.description || `Transferencia a ${toWallet.user.username}`,
            status: 'COMPLETED',
            reference: `TRANSFER_OUT_${Date.now()}`,
            metadata: {
              transferType: 'OUTGOING',
              targetWalletId: request.toWalletId,
              targetUserId: toWallet.userId
            }
          }
        })

        // Crear transacción de crédito
        const transaction = await tx.transaction.create({
          data: {
            userId: toWallet.userId,
            walletId: request.toWalletId,
            type: 'CREDIT',
            amount: request.amount,
            currencyId: toWallet.currency.id,
            description: request.description || `Transferencia de ${fromWallet.user.username}`,
            status: 'COMPLETED',
            reference: `TRANSFER_IN_${Date.now()}`,
            metadata: {
              transferType: 'INCOMING',
              sourceWalletId: request.fromWalletId,
              sourceUserId: fromWallet.userId
            }
          }
        })

        return { updatedFromWallet, updatedToWallet, transaction }
      })

      logger.info(`Transferencia completada`, {
        from: request.fromWalletId,
        to: request.toWalletId,
        amount: request.amount,
        currency: fromWallet.currency.code
      })

      return {
        fromWallet: this.formatWalletResponse(result.updatedFromWallet),
        toWallet: this.formatWalletResponse(result.updatedToWallet),
        transaction: result.transaction
      }
    } catch (error) {
      logger.error('Error en transferencia:', error)
      throw error instanceof AppError ? error : new AppError('Error en transferencia')
    }
  }

  /**
   * Obtener historial de transacciones
   */
  async getTransactionHistory(walletId: string, userId?: string, limit = 20, offset = 0): Promise<WalletTransactionHistory> {
    try {
      // Verificar acceso a la billetera
      await this.getWalletById(walletId, userId)

      const [transactions, total] = await Promise.all([
        prisma.transaction.findMany({
          where: { walletId },
          include: {
            currency: true,
            user: {
              select: { id: true, username: true }
            }
          },
          orderBy: { createdAt: 'desc' },
          take: limit,
          skip: offset
        }),
        prisma.transaction.count({
          where: { walletId }
        })
      ])

      return {
        walletId,
        transactions: transactions.map(tx => ({
          id: tx.id,
          type: tx.type,
          amount: tx.amount,
          currency: tx.currency,
          description: tx.description,
          status: tx.status,
          reference: tx.reference,
          metadata: tx.metadata,
          createdAt: tx.createdAt
        })),
        total,
        page: Math.floor(offset / limit) + 1,
        totalPages: Math.ceil(total / limit)
      }
    } catch (error) {
      logger.error('Error obteniendo historial:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo historial')
    }
  }

  /**
   * Activar/desactivar billetera
   */
  async toggleWalletStatus(walletId: string, isActive: boolean, userId?: string): Promise<WalletResponse> {
    try {
      const wallet = await prisma.wallet.update({
        where: {
          id: walletId,
          ...(userId && { userId })
        },
        data: {
          isActive,
          updatedAt: new Date()
        },
        include: {
          currency: true,
          user: {
            select: { id: true, username: true, email: true }
          }
        }
      })

      logger.info(`Billetera ${isActive ? 'activada' : 'desactivada'}: ${walletId}`)

      return this.formatWalletResponse(wallet)
    } catch (error) {
      logger.error('Error cambiando estado de billetera:', error)
      throw new AppError('Error cambiando estado de billetera')
    }
  }

  /**
   * Formatear respuesta de billetera
   */
  private formatWalletResponse(wallet: any): WalletResponse {
    return {
      id: wallet.id,
      userId: wallet.userId,
      currency: {
        id: wallet.currency.id,
        code: wallet.currency.code,
        name: wallet.currency.name,
        symbol: wallet.currency.symbol,
        decimals: wallet.currency.decimals
      },
      balance: wallet.balance,
      isActive: wallet.isActive,
      isPrimary: wallet.isPrimary,
      user: wallet.user,
      createdAt: wallet.createdAt,
      updatedAt: wallet.updatedAt
    }
  }
}