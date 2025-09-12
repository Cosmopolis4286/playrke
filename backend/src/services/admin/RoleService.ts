import { prisma } from '../../config/database';
import { z } from 'zod';
import type { Role, User } from '@prisma/client';

// Schemas de validación
export const CreateRoleSchema = z.object({
  name: z.string().min(2).max(50),
  prefix: z.string().min(2).max(20).optional().nullable(),
  description: z.string().max(255).optional().nullable(),
  status: z.boolean().default(true),
});

export const UpdateRoleSchema = CreateRoleSchema.partial();

export const AssignRoleSchema = z.object({
  userId: z.string(),
  roleId: z.number(),
});

// Types
export type CreateRoleRequest = z.infer<typeof CreateRoleSchema>;
export type UpdateRoleRequest = z.infer<typeof UpdateRoleSchema>;
export type AssignRoleRequest = z.infer<typeof AssignRoleSchema>;

export interface RoleWithUsers extends Role {
  users: Array<{
    id: string;
    username: string;
    email: string;
    firstName: string;
    lastName: string;
  }>;
}

/**
 * Servicio para gestión de roles del sistema
 */
export class RoleService {
  
  /**
   * Obtener todos los roles
   */
  static async getAllRoles(): Promise<Role[]> {
    return await prisma.role.findMany({
      orderBy: { name: 'asc' },
    });
  }

  /**
   * Obtener roles activos
   */
  static async getActiveRoles(): Promise<Role[]> {
    return await prisma.role.findMany({
      where: { status: true },
      orderBy: { name: 'asc' },
    });
  }

  /**
   * Obtener rol con usuarios asignados
   */
  static async getRoleWithUsers(roleId: number): Promise<RoleWithUsers | null> {
    return await prisma.role.findUnique({
      where: { id: roleId },
      include: {
        users: {
          select: {
            user: {
              select: {
                id: true,
                username: true,
                email: true,
                firstName: true,
                lastName: true,
              }
            }
          }
        }
      }
    }) as RoleWithUsers | null;
  }

  /**
   * Crear nuevo rol
   */
  static async createRole(data: CreateRoleRequest): Promise<Role> {
    // Verificar que no exista un rol con el mismo nombre
    const existingRole = await prisma.role.findFirst({
      where: { 
        OR: [
          { name: data.name },
          ...(data.prefix ? [{ prefix: data.prefix }] : [])
        ]
      }
    });

    if (existingRole) {
      throw new Error('Ya existe un rol con ese nombre o prefijo');
    }

    return await prisma.role.create({
      data: {
        name: data.name,
        prefix: data.prefix,
        description: data.description,
        status: data.status,
      },
    });
  }

  /**
   * Actualizar rol
   */
  static async updateRole(roleId: number, data: UpdateRoleRequest): Promise<Role> {
    // Verificar que el rol existe
    const existingRole = await prisma.role.findUnique({
      where: { id: roleId },
    });

    if (!existingRole) {
      throw new Error('Rol no encontrado');
    }

    // Verificar unicidad si se cambia nombre o prefijo
    if (data.name || data.prefix) {
      const conflictingRole = await prisma.role.findFirst({
        where: {
          AND: [
            { id: { not: roleId } },
            {
              OR: [
                ...(data.name ? [{ name: data.name }] : []),
                ...(data.prefix ? [{ prefix: data.prefix }] : [])
              ]
            }
          ]
        }
      });

      if (conflictingRole) {
        throw new Error('Ya existe otro rol con ese nombre o prefijo');
      }
    }

    return await prisma.role.update({
      where: { id: roleId },
      data,
    });
  }

  /**
   * Eliminar rol
   */
  static async deleteRole(roleId: number): Promise<void> {
    // Verificar que el rol no tenga usuarios asignados
    const usersCount = await prisma.roleUser.count({
      where: { roleId },
    });

    if (usersCount > 0) {
      throw new Error('No se puede eliminar un rol que tiene usuarios asignados');
    }

    await prisma.role.delete({
      where: { id: roleId },
    });
  }

  /**
   * Asignar rol a usuario
   */
  static async assignRoleToUser(data: AssignRoleRequest): Promise<void> {
    // Verificar que el usuario existe
    const user = await prisma.user.findUnique({
      where: { id: data.userId },
    });

    if (!user) {
      throw new Error('Usuario no encontrado');
    }

    // Verificar que el rol existe
    const role = await prisma.role.findUnique({
      where: { id: data.roleId },
    });

    if (!role) {
      throw new Error('Rol no encontrado');
    }

    // Verificar que no tenga ya ese rol asignado
    const existingAssignment = await prisma.roleUser.findUnique({
      where: {
        userId_roleId: {
          userId: data.userId,
          roleId: data.roleId,
        },
      },
    });

    if (existingAssignment) {
      throw new Error('El usuario ya tiene ese rol asignado');
    }

    await prisma.roleUser.create({
      data: {
        userId: data.userId,
        roleId: data.roleId,
      },
    });
  }

  /**
   * Remover rol de usuario
   */
  static async removeRoleFromUser(userId: string, roleId: number): Promise<void> {
    const assignment = await prisma.roleUser.findUnique({
      where: {
        userId_roleId: {
          userId,
          roleId,
        },
      },
    });

    if (!assignment) {
      throw new Error('El usuario no tiene ese rol asignado');
    }

    await prisma.roleUser.delete({
      where: {
        userId_roleId: {
          userId,
          roleId,
        },
      },
    });
  }

  /**
   * Obtener roles de un usuario
   */
  static async getUserRoles(userId: string): Promise<Role[]> {
    const userRoles = await prisma.roleUser.findMany({
      where: { userId },
      include: { role: true },
    });

    return userRoles.map(ur => ur.role);
  }

  /**
   * Verificar si usuario tiene rol específico
   */
  static async userHasRole(userId: string, roleName: string): Promise<boolean> {
    const assignment = await prisma.roleUser.findFirst({
      where: {
        userId,
        role: { name: roleName },
      },
    });

    return !!assignment;
  }

  /**
   * Verificar si usuario tiene cualquier rol de una lista
   */
  static async userHasAnyRole(userId: string, roleNames: string[]): Promise<boolean> {
    const assignment = await prisma.roleUser.findFirst({
      where: {
        userId,
        role: { name: { in: roleNames } },
      },
    });

    return !!assignment;
  }

  /**
   * Obtener usuarios por rol
   */
  static async getUsersByRole(roleName: string): Promise<User[]> {
    const userRoles = await prisma.roleUser.findMany({
      where: {
        role: { name: roleName },
      },
      include: {
        user: true,
      },
    });

    return userRoles.map(ur => ur.user);
  }

  /**
   * Estadísticas de roles
   */
  static async getRoleStats(): Promise<Array<{
    roleId: number;
    roleName: string;
    userCount: number;
    activeUserCount: number;
  }>> {
    const roles = await prisma.role.findMany({
      include: {
        users: {
          include: {
            user: {
              select: {
                status: true,
              }
            }
          }
        },
        _count: {
          select: {
            users: true,
          }
        }
      },
    });

    return roles.map(role => ({
      roleId: role.id,
      roleName: role.name,
      userCount: role._count.users,
      activeUserCount: role.users.filter(u => u.user.status === 'active').length,
    }));
  }
}

export default RoleService;