import { prisma } from '../../config/database';
import { z } from 'zod';
import { RoleService } from './RoleService';
import { UserService } from '../users/UserService';
import type { User, Country, Currency, TimeZone } from '@prisma/client';
import { logger } from '../../utils/logger';

// Types para estadísticas del dashboard
export interface AdminDashboardStats {
  users: {
    total: number;
    active: number;
    inactive: number;
    newThisMonth: number;
  };
  roles: {
    total: number;
    assignments: number;
  };
  games: {
    totalRooms: number;
    activeRooms: number;
    totalPlayers: number;
  };
  financial: {
    totalTransactions: number;
    totalVolume: number;
    pendingWithdrawals: number;
  };
  countries: Array<{
    countryId: string;
    countryName: string;
    userCount: number;
  }>;
}

export interface UserManagement {
  id: string;
  username: string;
  email: string;
  firstName: string;
  lastName: string;
  fullName: string;
  status: string;
  emailVerifiedAt: Date | null;
  country: {
    id: string;
    name: string;
    code: string;
  } | null;
  roles: Array<{
    id: number;
    name: string;
    prefix: string | null;
  }>;
  createdAt: Date;
  lastLoginAt: Date | null;
}

// Schemas de validación
export const CreateUserSchema = z.object({
  username: z.string().min(3).max(30),
  email: z.string().email(),
  firstName: z.string().min(2).max(50),
  lastName: z.string().min(2).max(50),
  password: z.string().min(8),
  countryId: z.string(),
  timeZoneId: z.string().optional(),
  phoneNumber: z.string().optional(),
  roleIds: z.array(z.number()).min(1),
});

export const UpdateUserStatusSchema = z.object({
  status: z.enum(['active', 'inactive', 'suspended', 'banned']),
  reason: z.string().optional(),
});

export const UpdateUserRolesSchema = z.object({
  roleIds: z.array(z.number()),
});

export type CreateUserRequest = z.infer<typeof CreateUserSchema>;
export type UpdateUserStatusRequest = z.infer<typeof UpdateUserStatusSchema>;
export type UpdateUserRolesRequest = z.infer<typeof UpdateUserRolesSchema>;

/**
 * Servicio de administración del sistema
 */
export class AdminService {

  /**
   * Obtener estadísticas del dashboard administrativo
   */
  static async getDashboardStats(): Promise<AdminDashboardStats> {
    try {
      // Obtener estadísticas de usuarios
      const [
        totalUsers,
        activeUsers,
        inactiveUsers,
        newUsersThisMonth,
        totalRoles,
        totalRoleAssignments,
        totalBingoRooms,
        activeBingoRooms,
        totalTransactions,
        usersByCountry,
      ] = await Promise.all([
        prisma.user.count(),
        prisma.user.count({ where: { status: 'active' } }),
        prisma.user.count({ where: { status: { not: 'active' } } }),
        prisma.user.count({
          where: {
            createdAt: {
              gte: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            },
          },
        }),
        prisma.role.count(),
        prisma.roleUser.count(),
        prisma.bingoRoom.count(),
        prisma.bingoRoom.count({ where: { status: { in: [1, 2] } } }),
        prisma.transaction.count(),
        prisma.user.groupBy({
          by: ['countryId'],
          _count: { id: true },
          where: { countryId: { not: null } },
        }),
      ]);

      // Obtener información de países
      const countries = await prisma.country.findMany({
        where: {
          id: {
            in: usersByCountry.map(u => u.countryId).filter(Boolean) as string[],
          },
        },
        select: { id: true, name: true },
      });

      const countriesWithUsers = usersByCountry.map(country => ({
        countryId: country.countryId!,
        countryName: countries.find(c => c.id === country.countryId)?.name || 'Desconocido',
        userCount: country._count.id,
      }));

      // Calcular volumen financiero
      const financialStats = await prisma.transaction.aggregate({
        _sum: { amount: true },
        where: { status: 'COMPLETED' },
      });

      const pendingWithdrawals = await prisma.transaction.count({
        where: {
          type: 'DEBIT',
          status: 'PENDING',
        },
      });

      return {
        users: {
          total: totalUsers,
          active: activeUsers,
          inactive: inactiveUsers,
          newThisMonth: newUsersThisMonth,
        },
        roles: {
          total: totalRoles,
          assignments: totalRoleAssignments,
        },
        games: {
          totalRooms: totalBingoRooms,
          activeRooms: activeBingoRooms,
          totalPlayers: activeUsers, // Aproximación
        },
        financial: {
          totalTransactions,
          totalVolume: Number(financialStats._sum.amount || 0),
          pendingWithdrawals,
        },
        countries: countriesWithUsers,
      };
    } catch (error) {
      logger.error('Error getting dashboard stats:', error);
      throw new Error('Error obteniendo estadísticas del dashboard');
    }
  }

  /**
   * Obtener lista de usuarios para administración
   */
  static async getUsers(params: {
    page?: number;
    limit?: number;
    status?: string;
    role?: string;
    country?: string;
    search?: string;
  } = {}): Promise<{
    users: UserManagement[];
    total: number;
    page: number;
    totalPages: number;
  }> {
    const {
      page = 1,
      limit = 50,
      status,
      role,
      country,
      search,
    } = params;

    const offset = (page - 1) * limit;

    // Construir filtros
    const where: any = {};

    if (status) {
      where.status = status;
    }

    if (country) {
      where.countryId = country;
    }

    if (search) {
      where.OR = [
        { username: { contains: search, mode: 'insensitive' } },
        { email: { contains: search, mode: 'insensitive' } },
        { firstName: { contains: search, mode: 'insensitive' } },
        { lastName: { contains: search, mode: 'insensitive' } },
      ];
    }

    // Si se filtra por rol, necesitamos hacer una consulta más compleja
    if (role) {
      where.roles = {
        some: {
          role: { name: role },
        },
      };
    }

    const [users, total] = await Promise.all([
      prisma.user.findMany({
        where,
        include: {
          country: {
            select: { id: true, name: true, code: true },
          },
          roles: {
            include: {
              role: {
                select: { id: true, name: true, prefix: true },
              },
            },
          },
        },
        orderBy: { createdAt: 'desc' },
        skip: offset,
        take: limit,
      }),
      prisma.user.count({ where }),
    ]);

    const formattedUsers: UserManagement[] = users.map(user => ({
      id: user.id,
      username: user.username,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName,
      fullName: `${user.firstName} ${user.lastName}`.trim(),
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
      country: user.country,
      roles: user.roles.map(ur => ur.role),
      createdAt: user.createdAt,
      lastLoginAt: null, // Por implementar en futuras versiones
    }));

    return {
      users: formattedUsers,
      total,
      page,
      totalPages: Math.ceil(total / limit),
    };
  }

  /**
   * Crear usuario desde panel administrativo
   */
  static async createUser(data: CreateUserRequest): Promise<User> {
    try {
      // Verificar que no exista el usuario
      const existingUser = await prisma.user.findFirst({
        where: {
          OR: [
            { username: data.username },
            { email: data.email },
          ],
        },
      });

      if (existingUser) {
        throw new Error('Ya existe un usuario con ese username o email');
      }

      // Verificar que todos los roles existen
      const roles = await prisma.role.findMany({
        where: { id: { in: data.roleIds } },
      });

      if (roles.length !== data.roleIds.length) {
        throw new Error('Algunos roles especificados no existen');
      }

      // Crear usuario con roles
      const user = await prisma.$transaction(async (tx) => {
        // Crear usuario
        const newUser = await UserService.createUser({
          username: data.username,
          email: data.email,
          password: data.password,
          firstName: data.firstName,
          lastName: data.lastName,
          countryId: data.countryId,
          timeZoneId: data.timeZoneId,
          phoneNumber: data.phoneNumber,
        });

        // Asignar roles
        await tx.roleUser.createMany({
          data: data.roleIds.map(roleId => ({
            userId: newUser.id,
            roleId,
          })),
        });

        return newUser;
      });

      logger.info(`Admin created user: ${user.username} (${user.id})`);
      return user;

    } catch (error) {
      logger.error('Error creating user from admin:', error);
      throw error;
    }
  }

  /**
   * Actualizar estado de usuario
   */
  static async updateUserStatus(
    userId: string,
    data: UpdateUserStatusRequest,
    adminId: string
  ): Promise<User> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
      });

      if (!user) {
        throw new Error('Usuario no encontrado');
      }

      const updatedUser = await prisma.user.update({
        where: { id: userId },
        data: {
          status: data.status,
          updatedAt: new Date(),
        },
      });

      // Log de la acción administrativa
      logger.info(
        `Admin ${adminId} changed user ${userId} status from ${user.status} to ${data.status}`,
        { reason: data.reason }
      );

      return updatedUser;

    } catch (error) {
      logger.error('Error updating user status:', error);
      throw error;
    }
  }

  /**
   * Actualizar roles de usuario
   */
  static async updateUserRoles(
    userId: string,
    data: UpdateUserRolesRequest,
    adminId: string
  ): Promise<void> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        include: { roles: true },
      });

      if (!user) {
        throw new Error('Usuario no encontrado');
      }

      // Verificar que todos los roles existen
      const roles = await prisma.role.findMany({
        where: { id: { in: data.roleIds } },
      });

      if (roles.length !== data.roleIds.length) {
        throw new Error('Algunos roles especificados no existen');
      }

      await prisma.$transaction(async (tx) => {
        // Remover roles actuales
        await tx.roleUser.deleteMany({
          where: { userId },
        });

        // Asignar nuevos roles
        if (data.roleIds.length > 0) {
          await tx.roleUser.createMany({
            data: data.roleIds.map(roleId => ({
              userId,
              roleId,
            })),
          });
        }
      });

      logger.info(
        `Admin ${adminId} updated roles for user ${userId}: [${data.roleIds.join(', ')}]`
      );

    } catch (error) {
      logger.error('Error updating user roles:', error);
      throw error;
    }
  }

  /**
   * Eliminar usuario (soft delete)
   */
  static async deleteUser(userId: string, adminId: string): Promise<void> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
      });

      if (!user) {
        throw new Error('Usuario no encontrado');
      }

      // Soft delete: cambiar status a 'deleted'
      await prisma.user.update({
        where: { id: userId },
        data: {
          status: 'deleted',
          updatedAt: new Date(),
        },
      });

      logger.info(`Admin ${adminId} deleted user ${userId} (${user.username})`);

    } catch (error) {
      logger.error('Error deleting user:', error);
      throw error;
    }
  }

  /**
   * Obtener datos auxiliares para formularios
   */
  static async getFormData(): Promise<{
    countries: Country[];
    currencies: Currency[];
    timeZones: TimeZone[];
    roles: Array<{ id: number; name: string; description: string | null }>;
  }> {
    const [countries, currencies, timeZones, roles] = await Promise.all([
      prisma.country.findMany({
        where: { status: true },
        orderBy: { name: 'asc' },
      }),
      prisma.currency.findMany({
        where: { status: true },
        orderBy: { name: 'asc' },
      }),
      prisma.timeZone.findMany({
        where: { status: true },
        orderBy: { name: 'asc' },
      }),
      prisma.role.findMany({
        where: { status: true },
        select: { id: true, name: true, description: true },
        orderBy: { name: 'asc' },
      }),
    ]);

    return {
      countries,
      currencies,
      timeZones,
      roles,
    };
  }

  /**
   * Buscar usuarios para autocompletado
   */
  static async searchUsers(query: string, limit: number = 10): Promise<Array<{
    id: string;
    username: string;
    email: string;
    fullName: string;
  }>> {
    const users = await prisma.user.findMany({
      where: {
        OR: [
          { username: { contains: query, mode: 'insensitive' } },
          { email: { contains: query, mode: 'insensitive' } },
          { firstName: { contains: query, mode: 'insensitive' } },
          { lastName: { contains: query, mode: 'insensitive' } },
        ],
        status: { not: 'deleted' },
      },
      select: {
        id: true,
        username: true,
        email: true,
        firstName: true,
        lastName: true,
      },
      take: limit,
      orderBy: { username: 'asc' },
    });

    return users.map(user => ({
      id: user.id,
      username: user.username,
      email: user.email,
      fullName: `${user.firstName} ${user.lastName}`.trim(),
    }));
  }
}

export default AdminService;