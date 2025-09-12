import type { FastifyRequest, FastifyReply } from 'fastify';
import { AuthService } from '@/services/auth.js';
import { db } from '@/services/database.js';
import { logger } from '@/utils/logger.js';
import { validateData } from '@/utils/validation.js';
import { loginSchema, registerSchema, changePasswordSchema } from '@/types/api.js';

/**
 * Controlador de autenticación
 * Migrado desde Laravel Auth controllers
 */
export class AuthController {
  private authService: AuthService;

  constructor() {
    this.authService = new AuthService();
  }

  /**
   * Iniciar sesión
   * POST /auth/login
   */
  async login(request: FastifyRequest, reply: FastifyReply) {
    try {
      const credentials = validateData(loginSchema, request.body);

      // Actualizar IP en los datos para logging
      const clientIp = request.ip;
      
      const authResponse = await this.authService.authenticate(credentials);

      // Actualizar IP de la sesión
      await this.updateSessionIp(authResponse.token, clientIp, request.headers['user-agent']);

      // Establecer cookie segura
      reply.setCookie('token', authResponse.token, {
        maxAge: authResponse.expires_in * 1000,
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax'
      });

      return reply.send({
        success: true,
        message: 'Inicio de sesión exitoso',
        data: authResponse
      });

    } catch (error: any) {
      logger.error('Error in AuthController.login', error);
      
      return reply.status(401).send({
        success: false,
        message: error.message || 'Credenciales incorrectas',
        errors: [{ 
          field: 'login', 
          message: error.message || 'Credenciales incorrectas', 
          code: 'INVALID_CREDENTIALS' 
        }]
      });
    }
  }

  /**
   * Registrar nuevo usuario
   * POST /auth/register
   */
  async register(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Verificar si el registro está habilitado
      const registerSetting = await db.settings.findUnique({
        where: { key: 'REGISTER' }
      });

      const registerEnabled = registerSetting?.value === 'true' || registerSetting?.value === '1';
      
      if (!registerEnabled) {
        return reply.status(403).send({
          success: false,
          message: 'El registro está temporalmente deshabilitado',
          errors: [{ field: 'register', message: 'Registro deshabilitado', code: 'REGISTRATION_DISABLED' }]
        });
      }

      const userData = validateData(registerSchema, request.body);

      // Verificar que el país existe
      const country = await db.country.findUnique({
        where: { id: userData.country_id }
      });

      if (!country) {
        return reply.status(400).send({
          success: false,
          message: 'País no válido',
          errors: [{ field: 'country_id', message: 'País no válido', code: 'INVALID_COUNTRY' }]
        });
      }

      const authResponse = await this.authService.register(userData);

      // Actualizar IP de la sesión
      const clientIp = request.ip;
      await this.updateSessionIp(authResponse.token, clientIp, request.headers['user-agent']);

      // Establecer cookie
      reply.setCookie('token', authResponse.token, {
        maxAge: authResponse.expires_in * 1000,
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax'
      });

      return reply.status(201).send({
        success: true,
        message: 'Registro exitoso',
        data: authResponse
      });

    } catch (error: any) {
      logger.error('Error in AuthController.register', error);
      
      return reply.status(400).send({
        success: false,
        message: error.message || 'Error en el registro',
        errors: [{ 
          field: 'register', 
          message: error.message || 'Error en el registro', 
          code: 'REGISTRATION_ERROR' 
        }]
      });
    }
  }

  /**
   * Renovar token
   * POST /auth/refresh
   */
  async refresh(request: FastifyRequest<{ Body: { refresh_token: string } }>, reply: FastifyReply) {
    try {
      const { refresh_token } = request.body;

      if (!refresh_token) {
        return reply.status(400).send({
          success: false,
          message: 'Refresh token requerido',
          errors: [{ field: 'refresh_token', message: 'Refresh token requerido', code: 'MISSING_REFRESH_TOKEN' }]
        });
      }

      const authResponse = await this.authService.refreshToken(refresh_token);

      // Actualizar IP de la nueva sesión
      const clientIp = request.ip;
      await this.updateSessionIp(authResponse.token, clientIp, request.headers['user-agent']);

      // Actualizar cookie
      reply.setCookie('token', authResponse.token, {
        maxAge: authResponse.expires_in * 1000,
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax'
      });

      return reply.send({
        success: true,
        message: 'Token renovado exitosamente',
        data: authResponse
      });

    } catch (error: any) {
      logger.error('Error in AuthController.refresh', error);
      
      return reply.status(401).send({
        success: false,
        message: error.message || 'Token de actualización inválido',
        errors: [{ 
          field: 'refresh_token', 
          message: error.message || 'Token de actualización inválido', 
          code: 'INVALID_REFRESH_TOKEN' 
        }]
      });
    }
  }

  /**
   * Cerrar sesión
   * POST /auth/logout
   */
  async logout(request: FastifyRequest, reply: FastifyReply) {
    try {
      if (!request.user) {
        return reply.status(401).send({
          success: false,
          message: 'No autenticado',
          errors: [{ field: 'auth', message: 'No autenticado', code: 'UNAUTHENTICATED' }]
        });
      }

      // Obtener token actual
      let token = request.headers.authorization?.substring(7) || request.cookies.token;
      
      if (token) {
        await this.authService.logout(request.user.id, token);
      }

      // Limpiar cookie
      reply.clearCookie('token');

      return reply.send({
        success: true,
        message: 'Sesión cerrada exitosamente',
        data: null
      });

    } catch (error: any) {
      logger.error('Error in AuthController.logout', error);
      
      return reply.status(500).send({
        success: false,
        message: 'Error al cerrar sesión',
        errors: [{ field: 'logout', message: 'Error interno', code: 'LOGOUT_ERROR' }]
      });
    }
  }

  /**
   * Cerrar todas las sesiones
   * POST /auth/logout-all
   */
  async logoutAll(request: FastifyRequest, reply: FastifyReply) {
    try {
      if (!request.user) {
        return reply.status(401).send({
          success: false,
          message: 'No autenticado',
          errors: [{ field: 'auth', message: 'No autenticado', code: 'UNAUTHENTICATED' }]
        });
      }

      await this.authService.logoutAll(request.user.id);

      // Limpiar cookie
      reply.clearCookie('token');

      return reply.send({
        success: true,
        message: 'Todas las sesiones cerradas exitosamente',
        data: null
      });

    } catch (error: any) {
      logger.error('Error in AuthController.logoutAll', error);
      
      return reply.status(500).send({
        success: false,
        message: 'Error al cerrar sesiones',
        errors: [{ field: 'logout_all', message: 'Error interno', code: 'LOGOUT_ALL_ERROR' }]
      });
    }
  }

  /**
   * Obtener perfil del usuario autenticado
   * GET /auth/me
   */
  async me(request: FastifyRequest, reply: FastifyReply) {
    try {
      if (!request.user) {
        return reply.status(401).send({
          success: false,
          message: 'No autenticado',
          errors: [{ field: 'auth', message: 'No autenticado', code: 'UNAUTHENTICATED' }]
        });
      }

      // Obtener información completa del usuario
      const user = await db.user.findUnique({
        where: { id: request.user.id },
        include: {
          userRoles: {
            include: {
              role: true
            }
          },
          wallets: {
            include: {
              currency: true
            }
          }
        }
      });

      if (!user) {
        return reply.status(404).send({
          success: false,
          message: 'Usuario no encontrado',
          errors: [{ field: 'user', message: 'Usuario no encontrado', code: 'USER_NOT_FOUND' }]
        });
      }

      // Sanitizar datos sensibles
      const { password, rememberToken, twoFactorSecret, twoFactorRecoveryCodes, ...sanitizedUser } = user;

      return reply.send({
        success: true,
        data: {
          user: sanitizedUser,
          roles: user.userRoles.map(ur => ur.role.name),
          permissions: [] // TODO: Implementar sistema de permisos si es necesario
        }
      });

    } catch (error: any) {
      logger.error('Error in AuthController.me', error);
      
      return reply.status(500).send({
        success: false,
        message: 'Error al obtener perfil',
        errors: [{ field: 'profile', message: 'Error interno', code: 'PROFILE_ERROR' }]
      });
    }
  }

  /**
   * Cambiar contraseña
   * PUT /auth/password
   */
  async changePassword(request: FastifyRequest, reply: FastifyReply) {
    try {
      if (!request.user) {
        return reply.status(401).send({
          success: false,
          message: 'No autenticado',
          errors: [{ field: 'auth', message: 'No autenticado', code: 'UNAUTHENTICATED' }]
        });
      }

      const passwordData = validateData(changePasswordSchema, request.body);
      const bcrypt = await import('bcryptjs');

      // Verificar contraseña actual
      const user = await db.user.findUnique({
        where: { id: request.user.id }
      });

      if (!user || !await bcrypt.compare(passwordData.current_password, user.password)) {
        return reply.status(400).send({
          success: false,
          message: 'Contraseña actual incorrecta',
          errors: [{ field: 'current_password', message: 'Contraseña actual incorrecta', code: 'INVALID_CURRENT_PASSWORD' }]
        });
      }

      // Hash nueva contraseña
      const hashedPassword = await bcrypt.hash(passwordData.new_password, 12);

      // Actualizar contraseña
      await db.user.update({
        where: { id: request.user.id },
        data: { password: hashedPassword }
      });

      // Cerrar todas las demás sesiones por seguridad
      await this.authService.logoutAll(request.user.id);

      return reply.send({
        success: true,
        message: 'Contraseña actualizada exitosamente',
        data: null
      });

    } catch (error: any) {
      logger.error('Error in AuthController.changePassword', error);
      
      return reply.status(500).send({
        success: false,
        message: 'Error al cambiar contraseña',
        errors: [{ field: 'password', message: 'Error interno', code: 'PASSWORD_CHANGE_ERROR' }]
      });
    }
  }

  // Métodos privados helpers

  private async updateSessionIp(token: string, ipAddress: string, userAgent?: string): Promise<void> {
    try {
      await db.userSession.updateMany({
        where: { token },
        data: {
          ipAddress,
          userAgent: userAgent || null
        }
      });
    } catch (error) {
      logger.error('Error updating session IP', error);
    }
  }
}