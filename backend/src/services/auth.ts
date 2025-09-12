import bcrypt from 'bcryptjs';
import { randomUUID } from 'crypto';
import { db } from '@/services/database.js';
import { logger } from '@/utils/logger.js';
import { generateSecureRandom } from '@/utils/crypto.js';
import type { User, UserStatus } from '@/types/database.js';
import type { LoginRequest, RegisterRequest, AuthResponse } from '@/types/api.js';

/**
 * Servicio de autenticación
 * Migrado desde Laravel Auth + Fortify
 */
export class AuthService {
  
  /**
   * Autenticar usuario con email/username y password
   */
  async authenticate(credentials: LoginRequest): Promise<AuthResponse> {
    const { login, password, remember_me = false } = credentials;
    
    // Determinar si es email o username
    const isEmail = this.isValidEmail(login);
    const field = isEmail ? 'email' : 'username';
    
    // Buscar usuario
    const user = await db.user.findFirst({
      where: {
        [field]: login,
        deletedAt: null
      },
      include: {
        userRoles: {
          include: {
            role: true
          }
        }
      }
    });

    if (!user) {
      logger.authEvent('login_failed', login, { reason: 'user_not_found', field });
      throw new Error('Credenciales incorrectas');
    }

    // Verificar contraseña
    const isValidPassword = await bcrypt.compare(password, user.password);
    if (!isValidPassword) {
      logger.authEvent('login_failed', user.id, { reason: 'invalid_password' });
      throw new Error('Credenciales incorrectas');
    }

    // Verificar estado del usuario
    if (user.status !== 'ACTIVE') {
      logger.authEvent('login_failed', user.id, { reason: 'inactive_user', status: user.status });
      throw new Error('Usuario inactivo. Contacte al administrador.');
    }

    // Actualizar último login
    await db.user.update({
      where: { id: user.id },
      data: {
        lastLoginAt: new Date(),
        lastLoginIp: '' // Se actualizará desde el controlador con el IP real
      }
    });

    // Generar tokens
    const tokenData = await this.generateTokens(user, remember_me);
    
    logger.authEvent('login_success', user.id, { remember_me });

    return {
      user: this.sanitizeUser(user),
      ...tokenData
    };
  }

  /**
   * Registrar nuevo usuario
   */
  async register(userData: RegisterRequest): Promise<AuthResponse> {
    const { 
      username, 
      email, 
      password, 
      first_name, 
      last_name, 
      country_id, 
      phone_number,
      referral_code 
    } = userData;

    // Verificar si email ya existe
    const existingEmail = await db.user.findUnique({
      where: { email }
    });
    if (existingEmail) {
      throw new Error('El email ya está registrado');
    }

    // Verificar si username ya existe
    const existingUsername = await db.user.findUnique({
      where: { username }
    });
    if (existingUsername) {
      throw new Error('El nombre de usuario ya está en uso');
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 12);

    // Generar código de referido único
    const userReferralCode = await this.generateUniqueReferralCode();

    // Buscar usuario referidor si se proporciona código
    let referredByUserId: string | undefined;
    if (referral_code) {
      const referrer = await db.user.findUnique({
        where: { referralCode: referral_code }
      });
      if (referrer) {
        referredByUserId = referrer.id;
      }
    }

    // Crear usuario
    const newUser = await db.user.create({
      data: {
        username,
        email,
        password: hashedPassword,
        firstName: first_name,
        lastName: last_name,
        countryId: country_id,
        phoneNumber: phone_number,
        referralCode: userReferralCode,
        referredByUserId,
        status: 'PENDING_VERIFICATION'
      },
      include: {
        userRoles: {
          include: {
            role: true
          }
        }
      }
    });

    // Asignar rol de customer por defecto
    await this.assignDefaultRole(newUser.id);

    // Crear billeteras por defecto
    await this.createDefaultWallets(newUser.id);

    // Generar tokens
    const tokenData = await this.generateTokens(newUser, false);

    logger.authEvent('register_success', newUser.id, { 
      email, 
      username,
      referredBy: referredByUserId 
    });

    return {
      user: this.sanitizeUser(newUser),
      ...tokenData
    };
  }

  /**
   * Renovar token de acceso usando refresh token
   */
  async refreshToken(refreshToken: string): Promise<AuthResponse> {
    const session = await db.userSession.findUnique({
      where: { 
        refreshToken,
        isActive: true
      },
      include: {
        user: {
          include: {
            userRoles: {
              include: {
                role: true
              }
            }
          }
        }
      }
    });

    if (!session || session.expiresAt < new Date()) {
      throw new Error('Token de actualización inválido o expirado');
    }

    // Verificar estado del usuario
    if (session.user.status !== 'ACTIVE') {
      throw new Error('Usuario inactivo');
    }

    // Generar nuevos tokens
    const tokenData = await this.generateTokens(session.user, true);

    // Invalidar sesión anterior
    await db.userSession.update({
      where: { id: session.id },
      data: { isActive: false }
    });

    return {
      user: this.sanitizeUser(session.user),
      ...tokenData
    };
  }

  /**
   * Cerrar sesión
   */
  async logout(userId: string, token: string): Promise<void> {
    await db.userSession.updateMany({
      where: {
        userId,
        token,
        isActive: true
      },
      data: {
        isActive: false
      }
    });

    logger.authEvent('logout', userId);
  }

  /**
   * Cerrar todas las sesiones del usuario
   */
  async logoutAll(userId: string): Promise<void> {
    await db.userSession.updateMany({
      where: {
        userId,
        isActive: true
      },
      data: {
        isActive: false
      }
    });

    logger.authEvent('logout_all', userId);
  }

  /**
   * Verificar si un token es válido
   */
  async validateToken(token: string): Promise<User | null> {
    const session = await db.userSession.findUnique({
      where: { 
        token,
        isActive: true
      },
      include: {
        user: {
          include: {
            userRoles: {
              include: {
                role: true
              }
            }
          }
        }
      }
    });

    if (!session || session.expiresAt < new Date()) {
      return null;
    }

    if (session.user.status !== 'ACTIVE') {
      return null;
    }

    return session.user;
  }

  // Métodos privados

  private async generateTokens(user: any, rememberMe: boolean = false) {
    const accessToken = generateSecureRandom(64);
    const refreshToken = generateSecureRandom(64);
    
    // Duración del token
    const expiresIn = rememberMe ? 30 * 24 * 60 * 60 * 1000 : 7 * 24 * 60 * 60 * 1000; // 30 días o 7 días
    const expiresAt = new Date(Date.now() + expiresIn);

    // Crear sesión en BD
    await db.userSession.create({
      data: {
        userId: user.id,
        token: accessToken,
        refreshToken,
        ipAddress: '', // Se actualizará desde el controlador
        userAgent: '', // Se actualizará desde el controlador
        expiresAt,
        isActive: true
      }
    });

    return {
      token: accessToken,
      refresh_token: refreshToken,
      expires_in: Math.floor(expiresIn / 1000), // en segundos
      token_type: 'Bearer' as const
    };
  }

  private async generateUniqueReferralCode(): Promise<string> {
    let attempts = 0;
    const maxAttempts = 10;

    while (attempts < maxAttempts) {
      const code = generateSecureRandom(20);
      
      const existing = await db.user.findUnique({
        where: { referralCode: code }
      });

      if (!existing) {
        return code;
      }
      
      attempts++;
    }

    throw new Error('No se pudo generar código de referido único');
  }

  private async assignDefaultRole(userId: string): Promise<void> {
    // Buscar rol de customer
    const customerRole = await db.role.findUnique({
      where: { name: 'customer' }
    });

    if (customerRole) {
      await db.userRole.create({
        data: {
          userId,
          roleId: customerRole.id
        }
      });
    }
  }

  private async createDefaultWallets(userId: string): Promise<void> {
    // Obtener monedas activas principales
    const currencies = await db.currency.findMany({
      where: { 
        isActive: true,
        shortName: { in: ['USD', 'EUR', 'BRL', 'COP'] } // Monedas principales
      }
    });

    // Crear billetera para cada moneda
    for (const currency of currencies) {
      await db.wallet.create({
        data: {
          userId,
          currencyId: currency.id,
          balance: 0,
          lockedBalance: 0
        }
      });
    }
  }

  private sanitizeUser(user: any) {
    const { password, rememberToken, twoFactorSecret, twoFactorRecoveryCodes, ...sanitized } = user;
    return sanitized;
  }

  private isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }
}