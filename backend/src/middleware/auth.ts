import type { FastifyRequest, FastifyReply } from 'fastify';
import { AuthService } from '@/services/auth.js';
import { logger } from '@/utils/logger.js';

// Extend FastifyRequest to include user
declare module 'fastify' {
  interface FastifyRequest {
    user?: any; // Usuario autenticado
  }
}

/**
 * Middleware de autenticación
 * Verifica que el usuario tenga un token válido
 */
export async function authMiddleware(request: FastifyRequest, reply: FastifyReply) {
  try {
    // Extraer token del header Authorization o cookie
    let token: string | undefined;
    
    const authHeader = request.headers.authorization;
    if (authHeader && authHeader.startsWith('Bearer ')) {
      token = authHeader.substring(7);
    } else {
      // Fallback a cookie si no hay header
      token = request.cookies.token;
    }

    if (!token) {
      return reply.status(401).send({
        success: false,
        message: 'Token de acceso requerido',
        errors: [{ field: 'token', message: 'No se proporcionó token de acceso', code: 'MISSING_TOKEN' }]
      });
    }

    // Validar token
    const authService = new AuthService();
    const user = await authService.validateToken(token);

    if (!user) {
      return reply.status(401).send({
        success: false,
        message: 'Token inválido o expirado',
        errors: [{ field: 'token', message: 'Token inválido o expirado', code: 'INVALID_TOKEN' }]
      });
    }

    // Agregar usuario a la request
    request.user = user;

    // Log del acceso
    logger.apiRequest(request.method, request.url, user.id);

  } catch (error) {
    logger.error('Error in auth middleware', error);
    return reply.status(401).send({
      success: false,
      message: 'Error de autenticación',
      errors: [{ field: 'auth', message: 'Error interno de autenticación', code: 'AUTH_ERROR' }]
    });
  }
}

/**
 * Middleware opcional de autenticación
 * No falla si no hay token, pero agrega user si está presente
 */
export async function optionalAuthMiddleware(request: FastifyRequest, reply: FastifyReply) {
  try {
    let token: string | undefined;
    
    const authHeader = request.headers.authorization;
    if (authHeader && authHeader.startsWith('Bearer ')) {
      token = authHeader.substring(7);
    } else {
      token = request.cookies.token;
    }

    if (token) {
      const authService = new AuthService();
      const user = await authService.validateToken(token);
      
      if (user) {
        request.user = user;
      }
    }

  } catch (error) {
    // En middleware opcional, no fallar - solo log
    logger.debug('Optional auth middleware error', error);
  }
}

/**
 * Middleware para verificar roles específicos
 */
export function roleMiddleware(allowedRoles: string[]) {
  return async (request: FastifyRequest, reply: FastifyReply) => {
    if (!request.user) {
      return reply.status(401).send({
        success: false,
        message: 'Autenticación requerida',
        errors: [{ field: 'auth', message: 'Usuario no autenticado', code: 'UNAUTHENTICATED' }]
      });
    }

    // Verificar roles del usuario
    const userRoles = request.user.userRoles?.map((ur: any) => ur.role.name) || [];
    const hasRequiredRole = allowedRoles.some(role => userRoles.includes(role));

    if (!hasRequiredRole) {
      logger.authEvent('access_denied', request.user.id, { 
        requiredRoles: allowedRoles, 
        userRoles,
        endpoint: request.url 
      });

      return reply.status(403).send({
        success: false,
        message: 'Permisos insuficientes',
        errors: [{ 
          field: 'permissions', 
          message: `Se requiere uno de los siguientes roles: ${allowedRoles.join(', ')}`, 
          code: 'INSUFFICIENT_PERMISSIONS' 
        }]
      });
    }
  };
}

/**
 * Middleware para verificar que el usuario esté activo
 */
export async function activeUserMiddleware(request: FastifyRequest, reply: FastifyReply) {
  if (!request.user) {
    return reply.status(401).send({
      success: false,
      message: 'Autenticación requerida',
      errors: [{ field: 'auth', message: 'Usuario no autenticado', code: 'UNAUTHENTICATED' }]
    });
  }

  if (request.user.status !== 'ACTIVE') {
    return reply.status(403).send({
      success: false,
      message: 'Cuenta inactiva',
      errors: [{ 
        field: 'status', 
        message: `Tu cuenta está ${request.user.status.toLowerCase()}. Contacta al administrador.`, 
        code: 'ACCOUNT_INACTIVE' 
      }]
    });
  }
}

/**
 * Middleware para verificar verificación de email
 */
export async function emailVerifiedMiddleware(request: FastifyRequest, reply: FastifyReply) {
  if (!request.user) {
    return reply.status(401).send({
      success: false,
      message: 'Autenticación requerida',
      errors: [{ field: 'auth', message: 'Usuario no autenticado', code: 'UNAUTHENTICATED' }]
    });
  }

  if (!request.user.emailVerifiedAt) {
    return reply.status(403).send({
      success: false,
      message: 'Email no verificado',
      errors: [{ 
        field: 'email', 
        message: 'Debes verificar tu email antes de acceder a esta función', 
        code: 'EMAIL_NOT_VERIFIED' 
      }]
    });
  }
}

// Alias para compatibilidad con las nuevas rutas
export const authenticateUser = authMiddleware
export const requireRole = roleMiddleware

/**
 * Middleware de rate limiting por usuario autenticado
 */
export function userRateLimitMiddleware(maxRequests: number = 100, windowMs: number = 60000) {
  const userLimits = new Map<string, { count: number; resetTime: number }>();

  return async (request: FastifyRequest, reply: FastifyReply) => {
    if (!request.user) {
      return; // Si no está autenticado, skip (será manejado por authMiddleware)
    }

    const userId = request.user.id;
    const now = Date.now();
    const windowStart = now - windowMs;

    // Limpiar entradas expiradas
    for (const [key, value] of userLimits.entries()) {
      if (value.resetTime < windowStart) {
        userLimits.delete(key);
      }
    }

    // Verificar límite del usuario
    const userLimit = userLimits.get(userId);
    
    if (!userLimit) {
      userLimits.set(userId, { count: 1, resetTime: now });
      return;
    }

    if (userLimit.resetTime < windowStart) {
      // Ventana expirada, resetear
      userLimits.set(userId, { count: 1, resetTime: now });
      return;
    }

    userLimit.count++;

    if (userLimit.count > maxRequests) {
      const retryAfter = Math.ceil((userLimit.resetTime + windowMs - now) / 1000);
      
      return reply
        .status(429)
        .header('Retry-After', retryAfter.toString())
        .send({
          success: false,
          message: 'Límite de solicitudes excedido',
          errors: [{ 
            field: 'rate_limit', 
            message: `Demasiadas solicitudes. Intenta en ${retryAfter} segundos.`, 
            code: 'RATE_LIMIT_EXCEEDED' 
          }]
        });
    }
  };
}