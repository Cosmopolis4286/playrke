import { FastifyInstance } from 'fastify'
import { WalletController } from '../../controllers/payments/WalletController'
import { PaymentController } from '../../controllers/payments/PaymentController'
import { authenticateUser } from '../../middleware/auth'

export async function paymentsRoutes(fastify: FastifyInstance) {
  const walletController = new WalletController()
  const paymentController = new PaymentController()

  // Aplicar middleware de autenticación a todas las rutas
  fastify.addHook('onRequest', authenticateUser)

  // === RUTAS DE BILLETERAS ===

  // Crear billetera
  fastify.post('/wallets', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Crear nueva billetera',
      body: {
        type: 'object',
        required: ['currencyId'],
        properties: {
          currencyId: { type: 'string', format: 'uuid' },
          initialBalance: { type: 'number', minimum: 0 },
          isPrimary: { type: 'boolean' }
        }
      },
      response: {
        201: {
          description: 'Billetera creada exitosamente',
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: { type: 'object' },
            message: { type: 'string' }
          }
        }
      }
    }
  }, walletController.createWallet.bind(walletController))

  // Obtener mis billeteras
  fastify.get('/wallets', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Obtener mis billeteras',
      querystring: {
        type: 'object',
        properties: {
          includeInactive: { type: 'boolean', default: false }
        }
      }
    }
  }, walletController.getMyWallets.bind(walletController))

  // Resumen de billeteras
  fastify.get('/wallets/summary', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Resumen de billeteras del usuario'
    }
  }, walletController.getWalletSummary.bind(walletController))

  // Obtener billetera por ID
  fastify.get('/wallets/:id', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Obtener billetera por ID',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, walletController.getWalletById.bind(walletController))

  // Obtener balance de billetera
  fastify.get('/wallets/:id/balance', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Obtener balance de billetera',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, walletController.getWalletBalance.bind(walletController))

  // Agregar fondos
  fastify.post('/wallets/:id/add-funds', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Agregar fondos a billetera',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['amount', 'description'],
        properties: {
          amount: { type: 'number', minimum: 0.01 },
          description: { type: 'string', minLength: 1, maxLength: 255 },
          metadata: { type: 'object' }
        }
      }
    }
  }, walletController.addFunds.bind(walletController))

  // Retirar fondos
  fastify.post('/wallets/:id/withdraw-funds', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Retirar fondos de billetera',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['amount', 'description'],
        properties: {
          amount: { type: 'number', minimum: 0.01 },
          description: { type: 'string', minLength: 1, maxLength: 255 },
          metadata: { type: 'object' }
        }
      }
    }
  }, walletController.withdrawFunds.bind(walletController))

  // Transferir fondos
  fastify.post('/wallets/transfer', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Transferir fondos entre billeteras',
      body: {
        type: 'object',
        required: ['fromWalletId', 'toWalletId', 'amount'],
        properties: {
          fromWalletId: { type: 'string', format: 'uuid' },
          toWalletId: { type: 'string', format: 'uuid' },
          amount: { type: 'number', minimum: 0.01 },
          description: { type: 'string', maxLength: 255 }
        }
      }
    }
  }, walletController.transferFunds.bind(walletController))

  // Historial de transacciones
  fastify.get('/wallets/:id/transactions', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Obtener historial de transacciones de billetera',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      querystring: {
        type: 'object',
        properties: {
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          offset: { type: 'number', minimum: 0, default: 0 }
        }
      }
    }
  }, walletController.getTransactionHistory.bind(walletController))

  // Activar/desactivar billetera
  fastify.put('/wallets/:id/toggle-status', {
    schema: {
      tags: ['Payments - Wallets'],
      summary: 'Activar/desactivar billetera',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['isActive'],
        properties: {
          isActive: { type: 'boolean' }
        }
      }
    }
  }, walletController.toggleWalletStatus.bind(walletController))

  // === RUTAS DE PAGOS ===

  // Obtener métodos de pago disponibles
  fastify.get('/payments/methods', {
    schema: {
      tags: ['Payments - Methods'],
      summary: 'Obtener métodos de pago disponibles'
    }
  }, paymentController.getPaymentMethods.bind(paymentController))

  // Obtener monedas disponibles
  fastify.get('/payments/currencies', {
    schema: {
      tags: ['Payments - Methods'],
      summary: 'Obtener monedas disponibles para pagos'
    }
  }, paymentController.getAvailableCurrencies.bind(paymentController))

  // Crear pago
  fastify.post('/payments', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Crear nuevo pago',
      body: {
        type: 'object',
        required: ['amount', 'currencyId', 'method', 'description'],
        properties: {
          amount: { type: 'number', minimum: 0.01 },
          currencyId: { type: 'string', format: 'uuid' },
          method: { 
            type: 'string', 
            enum: ['WALLET', 'CREDIT_CARD', 'DEBIT_CARD', 'BANK_TRANSFER', 'CRYPTO', 'PAYPAL', 'OTHER']
          },
          description: { type: 'string', minLength: 1, maxLength: 255 },
          metadata: { type: 'object' }
        }
      }
    }
  }, paymentController.createPayment.bind(paymentController))

  // Obtener mis pagos
  fastify.get('/payments', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Listar mis pagos',
      querystring: {
        type: 'object',
        properties: {
          status: { type: 'string', enum: ['PENDING', 'COMPLETED', 'FAILED', 'CANCELLED'] },
          method: { type: 'string' },
          currencyId: { type: 'string', format: 'uuid' },
          dateFrom: { type: 'string', format: 'date' },
          dateTo: { type: 'string', format: 'date' },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          offset: { type: 'number', minimum: 0, default: 0 }
        }
      }
    }
  }, paymentController.getMyPayments.bind(paymentController))

  // Obtener estadísticas de pagos
  fastify.get('/payments/stats', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Obtener estadísticas de pagos',
      querystring: {
        type: 'object',
        properties: {
          period: { type: 'string', enum: ['7d', '30d', '90d'], default: '30d' }
        }
      }
    }
  }, paymentController.getPaymentStats.bind(paymentController))

  // Obtener pago por ID
  fastify.get('/payments/:id', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Obtener pago por ID',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, paymentController.getPaymentById.bind(paymentController))

  // Procesar pago
  fastify.post('/payments/:id/process', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Procesar pago',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        description: 'Datos específicos del método de pago'
      }
    }
  }, paymentController.processPayment.bind(paymentController))

  // Cancelar pago
  fastify.put('/payments/:id/cancel', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Cancelar pago',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, paymentController.cancelPayment.bind(paymentController))

  // Solicitar reembolso
  fastify.post('/payments/:id/refund', {
    schema: {
      tags: ['Payments - Payments'],
      summary: 'Solicitar reembolso de pago',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['reason'],
        properties: {
          reason: { type: 'string', minLength: 10, maxLength: 500 },
          amount: { type: 'number', minimum: 0.01 }
        }
      }
    }
  }, paymentController.requestRefund.bind(paymentController))

  // Webhook para notificaciones de gateway de pago (sin autenticación)
  fastify.removeHook('onRequest', authenticateUser)
  fastify.post('/payments/webhook', {
    schema: {
      tags: ['Payments - Webhooks'],
      summary: 'Webhook para notificaciones de gateway de pago',
      body: {
        type: 'object',
        description: 'Datos del webhook del gateway de pago'
      }
    }
  }, paymentController.paymentWebhook.bind(paymentController))
}