import { FastifyInstance } from 'fastify';
import { AdminController } from '../controllers/admin/AdminController';
import { authenticateUser } from '../middleware/auth';
import { requireRoles } from '../middleware/roles';

/**
 * Rutas de administración del sistema
 * Requieren autenticación y rol de administrador
 */
export async function adminRoutes(fastify: FastifyInstance) {
  
  // Middleware de autenticación y autorización para todas las rutas admin
  fastify.addHook('preHandler', authenticateUser);
  fastify.addHook('preHandler', requireRoles(['admin']));

  // ========================================
  // DASHBOARD ADMINISTRATIVO
  // ========================================
  
  /**
   * @swagger
   * /api/admin/dashboard:
   *   get:
   *     summary: Obtener estadísticas del dashboard administrativo
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     responses:
   *       200:
   *         description: Estadísticas obtenidas exitosamente
   *         content:
   *           application/json:
   *             schema:
   *               type: object
   *               properties:
   *                 success:
   *                   type: boolean
   *                 message:
   *                   type: string
   *                 data:
   *                   $ref: '#/components/schemas/AdminDashboardStats'
   */
  fastify.get('/dashboard', AdminController.getDashboard);

  // ========================================
  // GESTIÓN DE USUARIOS
  // ========================================

  /**
   * @swagger
   * /api/admin/users:
   *   get:
   *     summary: Listar usuarios con filtros y paginación
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: query
   *         name: page
   *         schema:
   *           type: integer
   *           minimum: 1
   *           default: 1
   *       - in: query
   *         name: limit
   *         schema:
   *           type: integer
   *           minimum: 1
   *           maximum: 100
   *           default: 50
   *       - in: query
   *         name: status
   *         schema:
   *           type: string
   *           enum: [active, inactive, suspended, banned]
   *       - in: query
   *         name: role
   *         schema:
   *           type: string
   *       - in: query
   *         name: country
   *         schema:
   *           type: string
   *       - in: query
   *         name: search
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Lista de usuarios obtenida exitosamente
   */
  fastify.get('/users', AdminController.getUsers);

  /**
   * @swagger
   * /api/admin/users:
   *   post:
   *     summary: Crear nuevo usuario desde panel administrativo
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     requestBody:
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             $ref: '#/components/schemas/CreateUserRequest'
   *     responses:
   *       201:
   *         description: Usuario creado exitosamente
   */
  fastify.post('/users', AdminController.createUser);

  /**
   * @swagger
   * /api/admin/users/{userId}/status:
   *   put:
   *     summary: Actualizar estado de un usuario
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: userId
   *         required: true
   *         schema:
   *           type: string
   *     requestBody:
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             $ref: '#/components/schemas/UpdateUserStatusRequest'
   *     responses:
   *       200:
   *         description: Estado del usuario actualizado exitosamente
   */
  fastify.put('/users/:userId/status', AdminController.updateUserStatus);

  /**
   * @swagger
   * /api/admin/users/{userId}/roles:
   *   put:
   *     summary: Actualizar roles de un usuario
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: userId
   *         required: true
   *         schema:
   *           type: string
   *     requestBody:
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             $ref: '#/components/schemas/UpdateUserRolesRequest'
   *     responses:
   *       200:
   *         description: Roles del usuario actualizados exitosamente
   */
  fastify.put('/users/:userId/roles', AdminController.updateUserRoles);

  /**
   * @swagger
   * /api/admin/users/{userId}:
   *   delete:
   *     summary: Eliminar (soft delete) un usuario
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: userId
   *         required: true
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Usuario eliminado exitosamente
   */
  fastify.delete('/users/:userId', AdminController.deleteUser);

  /**
   * @swagger
   * /api/admin/users/search:
   *   get:
   *     summary: Buscar usuarios para autocompletado
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: query
   *         name: q
   *         required: true
   *         schema:
   *           type: string
   *           minLength: 2
   *       - in: query
   *         name: limit
   *         schema:
   *           type: integer
   *           minimum: 1
   *           maximum: 50
   *           default: 10
   *     responses:
   *       200:
   *         description: Búsqueda completada exitosamente
   */
  fastify.get('/users/search', AdminController.searchUsers);

  // ========================================
  // DATOS AUXILIARES
  // ========================================

  /**
   * @swagger
   * /api/admin/form-data:
   *   get:
   *     summary: Obtener datos auxiliares para formularios
   *     tags: [Admin]
   *     security:
   *       - BearerAuth: []
   *     responses:
   *       200:
   *         description: Datos obtenidos exitosamente
   */
  fastify.get('/form-data', AdminController.getFormData);

  // ========================================
  // GESTIÓN DE ROLES
  // ========================================

  /**
   * @swagger
   * /api/admin/roles:
   *   get:
   *     summary: Listar todos los roles
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     responses:
   *       200:
   *         description: Lista de roles obtenida exitosamente
   */
  fastify.get('/roles', AdminController.getRoles);

  /**
   * @swagger
   * /api/admin/roles/{roleId}:
   *   get:
   *     summary: Obtener rol con usuarios asignados
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: roleId
   *         required: true
   *         schema:
   *           type: integer
   *     responses:
   *       200:
   *         description: Rol obtenido exitosamente
   *       404:
   *         description: Rol no encontrado
   */
  fastify.get('/roles/:roleId', AdminController.getRole);

  /**
   * @swagger
   * /api/admin/roles:
   *   post:
   *     summary: Crear nuevo rol
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     requestBody:
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             $ref: '#/components/schemas/CreateRoleRequest'
   *     responses:
   *       201:
   *         description: Rol creado exitosamente
   */
  fastify.post('/roles', AdminController.createRole);

  /**
   * @swagger
   * /api/admin/roles/{roleId}:
   *   put:
   *     summary: Actualizar rol
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: roleId
   *         required: true
   *         schema:
   *           type: integer
   *     requestBody:
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             $ref: '#/components/schemas/UpdateRoleRequest'
   *     responses:
   *       200:
   *         description: Rol actualizado exitosamente
   */
  fastify.put('/roles/:roleId', AdminController.updateRole);

  /**
   * @swagger
   * /api/admin/roles/{roleId}:
   *   delete:
   *     summary: Eliminar rol
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     parameters:
   *       - in: path
   *         name: roleId
   *         required: true
   *         schema:
   *           type: integer
   *     responses:
   *       200:
   *         description: Rol eliminado exitosamente
   */
  fastify.delete('/roles/:roleId', AdminController.deleteRole);

  /**
   * @swagger
   * /api/admin/roles/stats:
   *   get:
   *     summary: Obtener estadísticas de roles
   *     tags: [Admin - Roles]
   *     security:
   *       - BearerAuth: []
   *     responses:
   *       200:
   *         description: Estadísticas obtenidas exitosamente
   */
  fastify.get('/roles/stats', AdminController.getRoleStats);
}