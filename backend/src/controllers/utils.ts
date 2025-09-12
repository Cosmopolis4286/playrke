import type { FastifyRequest, FastifyReply } from 'fastify';
import { logger } from '@/utils/logger.js';

/**
 * Controlador de utilidades
 * Migrado desde Laravel Utils controllers
 */
export class UtilsController {

  /**
   * Obtener valor de cookie referral
   * GET /utils/referral
   */
  async getReferral(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Obtener cookie de referral
      const referralCookie = request.cookies.referral;

      return reply.send({
        success: true,
        data: {
          referral: referralCookie || null
        }
      });

    } catch (error) {
      logger.error('Error in UtilsController.getReferral', error);
      return reply.status(500).send({
        success: false,
        message: 'Error al obtener referral',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Establecer cookie de referral
   * POST /utils/referral
   */
  async setReferral(
    request: FastifyRequest<{ Body: { referral: string } }>, 
    reply: FastifyReply
  ) {
    try {
      const { referral } = request.body;

      if (!referral || typeof referral !== 'string') {
        return reply.status(400).send({
          success: false,
          message: 'Código de referral no válido',
          errors: [{ field: 'referral', message: 'Código de referral requerido', code: 'VALIDATION_ERROR' }]
        });
      }

      // Validar formato de referral (20 caracteres alfanuméricos)
      if (!/^[a-zA-Z0-9]{20}$/.test(referral)) {
        return reply.status(400).send({
          success: false,
          message: 'Formato de referral no válido',
          errors: [{ field: 'referral', message: 'Formato de referral no válido', code: 'VALIDATION_ERROR' }]
        });
      }

      // Establecer cookie por 30 días
      reply.setCookie('referral', referral, {
        maxAge: 30 * 24 * 60 * 60 * 1000, // 30 días en ms
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax'
      });

      return reply.send({
        success: true,
        message: 'Referral establecido correctamente',
        data: {
          referral
        }
      });

    } catch (error) {
      logger.error('Error in UtilsController.setReferral', error);
      return reply.status(500).send({
        success: false,
        message: 'Error al establecer referral',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Obtener información del servidor
   * GET /utils/server-info
   */
  async getServerInfo(request: FastifyRequest, reply: FastifyReply) {
    try {
      const serverInfo = {
        timestamp: new Date().toISOString(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        version: {
          node: process.version,
          playrke: '2.0.0'
        },
        environment: process.env.NODE_ENV || 'development'
      };

      return reply.send({
        success: true,
        data: serverInfo
      });

    } catch (error) {
      logger.error('Error in UtilsController.getServerInfo', error);
      return reply.status(500).send({
        success: false,
        message: 'Error al obtener información del servidor',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Generar UUID v4
   * GET /utils/uuid
   */
  async generateUUID(request: FastifyRequest, reply: FastifyReply) {
    try {
      const { randomUUID } = await import('crypto');
      const uuid = randomUUID();

      return reply.send({
        success: true,
        data: {
          uuid
        }
      });

    } catch (error) {
      logger.error('Error in UtilsController.generateUUID', error);
      return reply.status(500).send({
        success: false,
        message: 'Error al generar UUID',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Obtener configuraciones públicas
   * GET /utils/config
   */
  async getPublicConfig(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Configuraciones que son seguras de exponer al frontend
      const publicConfig = {
        app: {
          name: 'PlayRke',
          version: '2.0.0',
          environment: process.env.NODE_ENV || 'development'
        },
        features: {
          registration: true,
          twoFactor: true,
          referrals: true
        },
        limits: {
          maxFileSize: 10 * 1024 * 1024, // 10MB
          maxUsernameLength: 30,
          maxEmailLength: 320
        },
        formats: {
          dateFormat: 'YYYY-MM-DD',
          timeFormat: 'HH:mm:ss',
          datetimeFormat: 'YYYY-MM-DD HH:mm:ss'
        }
      };

      return reply.send({
        success: true,
        data: publicConfig
      });

    } catch (error) {
      logger.error('Error in UtilsController.getPublicConfig', error);
      return reply.status(500).send({
        success: false,
        message: 'Error al obtener configuración',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }
}