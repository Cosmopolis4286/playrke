import { FastifyReply, FastifyRequest } from 'fastify';
import { RoleService } from '../services/admin/RoleService';
import { logger } from '../utils/logger';

/**
 * Middleware para verificar roles de usuario
 */
export function requireRoles(requiredRoles: string[]) {
  return async (request: FastifyRequest, reply: FastifyReply) => {
    try {
      // El usuario debe estar autenticado (verificado por authMiddleware)
      if (!request.user) {
        return reply.code(401).send({
          success: false,
          message: 'Usuario no autenticado',
          errors: [{ field: 'auth', message: 'Token requerido', code: 'UNAUTHORIZED' }],
        });
      }

      const userId = request.user.id;

      // Verificar si el usuario tiene alguno de los roles requeridos
      const hasRequiredRole = await RoleService.userHasAnyRole(userId, requiredRoles);

      if (!hasRequiredRole) {
        logger.warn(`User ${userId} attempted to access protected resource without required roles: [${requiredRoles.join(', ')}]`);
        
        return reply.code(403).send({
          success: false,
          message: 'Acceso denegado - Permisos insuficientes',
          errors: [{
            field: 'authorization',
            message: `Se requiere uno de los siguientes roles: ${requiredRoles.join(', ')}`,
            code: 'INSUFFICIENT_PERMISSIONS'
          }],
        });
      }

      // El usuario tiene los permisos necesarios, continuar
    } catch (error) {
      logger.error('Error in role middleware:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error verificando permisos', code: 'ROLE_CHECK_ERROR' }],
      });
    }
  };
}

/**
 * Middleware para verificar un rol específico
 */
export function requireRole(roleName: string) {
  return requireRoles([roleName]);
}

/**
 * Middleware para verificar que el usuario es administrador
 */
export const requireAdmin = requireRole('admin');

/**
 * Middleware para verificar que el usuario es comercio
 */
export const requireCommerce = requireRole('commerce');

/**
 * Middleware para verificar que el usuario es vendedor
 */
export const requireSeller = requireRole('seller');

/**
 * Middleware para verificar que el usuario es tesorero
 */
export const requireTreasurer = requireRole('treasurer');

/**
 * Middleware para verificar que el usuario es cliente
 */
export const requireCustomer = requireRole('customer');

/**
 * Middleware para verificar múltiples roles (admin o commerce)
 */
export const requireAdminOrCommerce = requireRoles(['admin', 'commerce']);

/**
 * Middleware para verificar roles de staff (admin, commerce, seller)
 */
export const requireStaff = requireRoles(['admin', 'commerce', 'seller']);

/**
 * Obtener los roles de un usuario (helper para uso en controladores)
 */
export async function getUserRoles(userId: string): Promise<string[]> {
  try {
    const roles = await RoleService.getUserRoles(userId);
    return roles.map(role => role.name);
  } catch (error) {
    logger.error('Error getting user roles:', error);
    return [];
  }
}

/**
 * Verificar si un usuario puede acceder a recursos de otro usuario
 * Los admins pueden acceder a todo, los usuarios solo a sus propios recursos
 */
export async function canAccessUserResource(
  requestingUserId: string,
  targetUserId: string
): Promise<boolean> {
  try {
    // Los usuarios siempre pueden acceder a sus propios recursos
    if (requestingUserId === targetUserId) {
      return true;
    }

    // Verificar si el usuario que solicita es admin
    const isAdmin = await RoleService.userHasRole(requestingUserId, 'admin');
    if (isAdmin) {
      return true;
    }

    // Por defecto, no permitir acceso
    return false;
  } catch (error) {
    logger.error('Error checking user resource access:', error);
    return false;
  }
}

/**
 * Middleware para verificar acceso a recursos de usuario
 */
export function requireUserResourceAccess() {
  return async (request: FastifyRequest, reply: FastifyReply) => {
    try {
      if (!request.user) {
        return reply.code(401).send({
          success: false,
          message: 'Usuario no autenticado',
          errors: [{ field: 'auth', message: 'Token requerido', code: 'UNAUTHORIZED' }],
        });
      }

      const requestingUserId = request.user.id;
      // Intentar obtener el userId de los parámetros de la ruta
      const targetUserId = (request.params as any)?.userId;

      if (!targetUserId) {
        return reply.code(400).send({
          success: false,
          message: 'ID de usuario requerido en la URL',
          errors: [{ field: 'params', message: 'userId requerido', code: 'MISSING_USER_ID' }],
        });
      }

      const canAccess = await canAccessUserResource(requestingUserId, targetUserId);

      if (!canAccess) {
        return reply.code(403).send({
          success: false,
          message: 'Acceso denegado - No puedes acceder a este recurso',
          errors: [{
            field: 'authorization',
            message: 'Solo puedes acceder a tus propios recursos o ser administrador',
            code: 'RESOURCE_ACCESS_DENIED'
          }],
        });
      }

    } catch (error) {
      logger.error('Error in user resource access middleware:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error verificando acceso', code: 'ACCESS_CHECK_ERROR' }],
      });
    }
  };
}