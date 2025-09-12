import { FastifyReply, FastifyRequest } from 'fastify';
import { AdminService, CreateUserSchema, UpdateUserStatusSchema, UpdateUserRolesSchema } from '../../services/admin/AdminService';
import { RoleService, CreateRoleSchema, UpdateRoleSchema, AssignRoleSchema } from '../../services/admin/RoleService';
import { z } from 'zod';
import { logger } from '../../utils/logger';

// Schemas adicionales para rutas
const GetUsersQuerySchema = z.object({
  page: z.coerce.number().min(1).default(1),
  limit: z.coerce.number().min(1).max(100).default(50),
  status: z.string().optional(),
  role: z.string().optional(),
  country: z.string().optional(),
  search: z.string().optional(),
});

const SearchUsersQuerySchema = z.object({
  q: z.string().min(2),
  limit: z.coerce.number().min(1).max(50).default(10),
});

/**
 * Controlador de administración del sistema
 */
export class AdminController {

  /**
   * GET /api/admin/dashboard
   * Obtener estadísticas del dashboard administrativo
   */
  static async getDashboard(request: FastifyRequest, reply: FastifyReply) {
    try {
      const stats = await AdminService.getDashboardStats();

      return reply.code(200).send({
        success: true,
        message: 'Estadísticas del dashboard obtenidas exitosamente',
        data: stats,
      });

    } catch (error) {
      logger.error('Error in AdminController.getDashboard:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo estadísticas', code: 'DASHBOARD_ERROR' }],
      });
    }
  }

  /**
   * GET /api/admin/users
   * Listar usuarios con filtros y paginación
   */
  static async getUsers(
    request: FastifyRequest<{ Querystring: z.infer<typeof GetUsersQuerySchema> }>,
    reply: FastifyReply
  ) {
    try {
      const query = GetUsersQuerySchema.parse(request.query);
      const result = await AdminService.getUsers(query);

      return reply.code(200).send({
        success: true,
        message: 'Lista de usuarios obtenida exitosamente',
        data: result,
      });

    } catch (error) {
      logger.error('Error in AdminController.getUsers:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo usuarios', code: 'USER_LIST_ERROR' }],
      });
    }
  }

  /**
   * POST /api/admin/users
   * Crear nuevo usuario desde panel administrativo
   */
  static async createUser(
    request: FastifyRequest<{ Body: z.infer<typeof CreateUserSchema> }>,
    reply: FastifyReply
  ) {
    try {
      const data = CreateUserSchema.parse(request.body);
      const user = await AdminService.createUser(data);

      return reply.code(201).send({
        success: true,
        message: 'Usuario creado exitosamente',
        data: user,
      });

    } catch (error) {
      logger.error('Error in AdminController.createUser:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'user', message: error.message, code: 'CREATE_USER_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error creando usuario', code: 'CREATE_USER_ERROR' }],
      });
    }
  }

  /**
   * PUT /api/admin/users/:userId/status
   * Actualizar estado de un usuario
   */
  static async updateUserStatus(
    request: FastifyRequest<{ 
      Params: { userId: string };
      Body: z.infer<typeof UpdateUserStatusSchema>;
    }>,
    reply: FastifyReply
  ) {
    try {
      const { userId } = request.params;
      const data = UpdateUserStatusSchema.parse(request.body);
      const adminId = request.user!.id;

      const user = await AdminService.updateUserStatus(userId, data, adminId);

      return reply.code(200).send({
        success: true,
        message: 'Estado del usuario actualizado exitosamente',
        data: user,
      });

    } catch (error) {
      logger.error('Error in AdminController.updateUserStatus:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'status', message: error.message, code: 'UPDATE_STATUS_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error actualizando estado', code: 'UPDATE_STATUS_ERROR' }],
      });
    }
  }

  /**
   * PUT /api/admin/users/:userId/roles
   * Actualizar roles de un usuario
   */
  static async updateUserRoles(
    request: FastifyRequest<{ 
      Params: { userId: string };
      Body: z.infer<typeof UpdateUserRolesSchema>;
    }>,
    reply: FastifyReply
  ) {
    try {
      const { userId } = request.params;
      const data = UpdateUserRolesSchema.parse(request.body);
      const adminId = request.user!.id;

      await AdminService.updateUserRoles(userId, data, adminId);

      return reply.code(200).send({
        success: true,
        message: 'Roles del usuario actualizados exitosamente',
        data: null,
      });

    } catch (error) {
      logger.error('Error in AdminController.updateUserRoles:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'roles', message: error.message, code: 'UPDATE_ROLES_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error actualizando roles', code: 'UPDATE_ROLES_ERROR' }],
      });
    }
  }

  /**
   * DELETE /api/admin/users/:userId
   * Eliminar (soft delete) un usuario
   */
  static async deleteUser(
    request: FastifyRequest<{ Params: { userId: string } }>,
    reply: FastifyReply
  ) {
    try {
      const { userId } = request.params;
      const adminId = request.user!.id;

      await AdminService.deleteUser(userId, adminId);

      return reply.code(200).send({
        success: true,
        message: 'Usuario eliminado exitosamente',
        data: null,
      });

    } catch (error) {
      logger.error('Error in AdminController.deleteUser:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'user', message: error.message, code: 'DELETE_USER_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error eliminando usuario', code: 'DELETE_USER_ERROR' }],
      });
    }
  }

  /**
   * GET /api/admin/users/search
   * Buscar usuarios para autocompletado
   */
  static async searchUsers(
    request: FastifyRequest<{ Querystring: z.infer<typeof SearchUsersQuerySchema> }>,
    reply: FastifyReply
  ) {
    try {
      const { q, limit } = SearchUsersQuerySchema.parse(request.query);
      const users = await AdminService.searchUsers(q, limit);

      return reply.code(200).send({
        success: true,
        message: 'Búsqueda de usuarios completada',
        data: users,
      });

    } catch (error) {
      logger.error('Error in AdminController.searchUsers:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error en búsqueda', code: 'SEARCH_ERROR' }],
      });
    }
  }

  /**
   * GET /api/admin/form-data
   * Obtener datos auxiliares para formularios
   */
  static async getFormData(request: FastifyRequest, reply: FastifyReply) {
    try {
      const data = await AdminService.getFormData();

      return reply.code(200).send({
        success: true,
        message: 'Datos de formulario obtenidos exitosamente',
        data,
      });

    } catch (error) {
      logger.error('Error in AdminController.getFormData:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo datos', code: 'FORM_DATA_ERROR' }],
      });
    }
  }

  // ========================================
  // GESTIÓN DE ROLES
  // ========================================

  /**
   * GET /api/admin/roles
   * Listar todos los roles
   */
  static async getRoles(request: FastifyRequest, reply: FastifyReply) {
    try {
      const roles = await RoleService.getAllRoles();

      return reply.code(200).send({
        success: true,
        message: 'Lista de roles obtenida exitosamente',
        data: roles,
      });

    } catch (error) {
      logger.error('Error in AdminController.getRoles:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo roles', code: 'ROLES_ERROR' }],
      });
    }
  }

  /**
   * GET /api/admin/roles/:roleId
   * Obtener rol con usuarios asignados
   */
  static async getRole(
    request: FastifyRequest<{ Params: { roleId: string } }>,
    reply: FastifyReply
  ) {
    try {
      const roleId = parseInt(request.params.roleId);
      const role = await RoleService.getRoleWithUsers(roleId);

      if (!role) {
        return reply.code(404).send({
          success: false,
          message: 'Rol no encontrado',
          errors: [{ field: 'role', message: 'Rol no encontrado', code: 'ROLE_NOT_FOUND' }],
        });
      }

      return reply.code(200).send({
        success: true,
        message: 'Rol obtenido exitosamente',
        data: role,
      });

    } catch (error) {
      logger.error('Error in AdminController.getRole:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo rol', code: 'ROLE_ERROR' }],
      });
    }
  }

  /**
   * POST /api/admin/roles
   * Crear nuevo rol
   */
  static async createRole(
    request: FastifyRequest<{ Body: z.infer<typeof CreateRoleSchema> }>,
    reply: FastifyReply
  ) {
    try {
      const data = CreateRoleSchema.parse(request.body);
      const role = await RoleService.createRole(data);

      return reply.code(201).send({
        success: true,
        message: 'Rol creado exitosamente',
        data: role,
      });

    } catch (error) {
      logger.error('Error in AdminController.createRole:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'role', message: error.message, code: 'CREATE_ROLE_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error creando rol', code: 'CREATE_ROLE_ERROR' }],
      });
    }
  }

  /**
   * PUT /api/admin/roles/:roleId
   * Actualizar rol
   */
  static async updateRole(
    request: FastifyRequest<{ 
      Params: { roleId: string };
      Body: z.infer<typeof UpdateRoleSchema>;
    }>,
    reply: FastifyReply
  ) {
    try {
      const roleId = parseInt(request.params.roleId);
      const data = UpdateRoleSchema.parse(request.body);

      const role = await RoleService.updateRole(roleId, data);

      return reply.code(200).send({
        success: true,
        message: 'Rol actualizado exitosamente',
        data: role,
      });

    } catch (error) {
      logger.error('Error in AdminController.updateRole:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'role', message: error.message, code: 'UPDATE_ROLE_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error actualizando rol', code: 'UPDATE_ROLE_ERROR' }],
      });
    }
  }

  /**
   * DELETE /api/admin/roles/:roleId
   * Eliminar rol
   */
  static async deleteRole(
    request: FastifyRequest<{ Params: { roleId: string } }>,
    reply: FastifyReply
  ) {
    try {
      const roleId = parseInt(request.params.roleId);
      await RoleService.deleteRole(roleId);

      return reply.code(200).send({
        success: true,
        message: 'Rol eliminado exitosamente',
        data: null,
      });

    } catch (error) {
      logger.error('Error in AdminController.deleteRole:', error);
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          errors: [{ field: 'role', message: error.message, code: 'DELETE_ROLE_ERROR' }],
        });
      }

      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error eliminando rol', code: 'DELETE_ROLE_ERROR' }],
      });
    }
  }

  /**
   * GET /api/admin/roles/stats
   * Obtener estadísticas de roles
   */
  static async getRoleStats(request: FastifyRequest, reply: FastifyReply) {
    try {
      const stats = await RoleService.getRoleStats();

      return reply.code(200).send({
        success: true,
        message: 'Estadísticas de roles obtenidas exitosamente',
        data: stats,
      });

    } catch (error) {
      logger.error('Error in AdminController.getRoleStats:', error);
      return reply.code(500).send({
        success: false,
        message: 'Error interno del servidor',
        errors: [{ field: 'system', message: 'Error obteniendo estadísticas', code: 'ROLE_STATS_ERROR' }],
      });
    }
  }
}

export default AdminController;