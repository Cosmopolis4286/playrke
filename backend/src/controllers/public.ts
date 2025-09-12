import type { FastifyRequest, FastifyReply } from 'fastify';
import { db } from '@/services/database.js';
import { logger } from '@/utils/logger.js';

/**
 * Controlador de páginas públicas
 * Migrado desde Laravel PublicController
 */
export class PublicController {
  
  /**
   * Página de inicio / información de registro
   * GET /
   */
  async root(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Obtener configuración de registro desde settings
      const registerSetting = await db.settings.findUnique({
        where: { key: 'REGISTER' }
      });

      const registerEnabled = registerSetting?.value === 'true' || 
                             registerSetting?.value === '1';

      return reply.send({
        success: true,
        data: {
          register: registerEnabled,
          passwordRequestUrl: '/auth/password-reset',
          appName: 'PlayRke',
          version: '2.0.0'
        }
      });

    } catch (error) {
      logger.error('Error in PublicController.root', error);
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Páginas de información estática
   * GET /info/:page
   */
  async info(request: FastifyRequest<{ Params: { page: string } }>, reply: FastifyReply) {
    try {
      const { page } = request.params;
      const allowedPages = ['terms', 'about', 'privacy'];

      if (!allowedPages.includes(page)) {
        return reply.status(404).send({
          success: false,
          message: 'Página no encontrada',
          errors: [{ field: 'page', message: 'Página no válida', code: 'NOT_FOUND' }]
        });
      }

      // En el futuro, esto podría obtener contenido desde la base de datos
      const content = await this.getPageContent(page);

      return reply.send({
        success: true,
        data: {
          page,
          title: this.getPageTitle(page),
          content
        }
      });

    } catch (error) {
      logger.error('Error in PublicController.info', error);
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'general', message: 'Error interno del servidor', code: 'INTERNAL_ERROR' }]
      });
    }
  }

  /**
   * Health check endpoint
   * GET /health
   */
  async health(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Verificar conexión a la base de datos
      const dbHealthy = await this.checkDatabaseHealth();

      const health = {
        status: dbHealthy ? 'ok' : 'degraded',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        version: '2.0.0',
        environment: process.env.NODE_ENV || 'development',
        services: {
          database: dbHealthy ? 'healthy' : 'unhealthy'
        }
      };

      const statusCode = dbHealthy ? 200 : 503;

      return reply.status(statusCode).send({
        success: dbHealthy,
        data: health
      });

    } catch (error) {
      logger.error('Error in health check', error);
      return reply.status(503).send({
        success: false,
        message: 'Servicio no disponible',
        data: {
          status: 'error',
          timestamp: new Date().toISOString(),
          error: 'Health check failed'
        }
      });
    }
  }

  // Métodos privados helpers

  private async checkDatabaseHealth(): Promise<boolean> {
    try {
      await db.$queryRaw`SELECT 1`;
      return true;
    } catch {
      return false;
    }
  }

  private getPageTitle(page: string): string {
    const titles = {
      terms: 'Términos y Condiciones',
      about: 'Acerca de PlayRke',
      privacy: 'Política de Privacidad'
    };
    return titles[page as keyof typeof titles] || 'Información';
  }

  private async getPageContent(page: string): Promise<string> {
    // Por ahora retornamos contenido estático
    // En el futuro, esto podría venir de la base de datos
    const content = {
      terms: 'Términos y condiciones de uso de PlayRke...',
      about: 'PlayRke es una plataforma de gaming en línea...',
      privacy: 'Su privacidad es importante para nosotros...'
    };
    
    return content[page as keyof typeof content] || 'Contenido no disponible';
  }
}