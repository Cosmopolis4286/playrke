import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'
import speakeasy from 'speakeasy'
import QRCode from 'qrcode'
import { PrismaClient } from '@prisma/client'
import { config } from '@/config/env.js'
import { logger } from '@/utils/logger.js'
import { EmailService } from '@/services/emailService.js'
import { generateRandomCode, hashPassword, verifyPassword } from '@/utils/crypto.js'
import type { User, UserStatus } from '@prisma/client'

const prisma = new PrismaClient()
const emailService = new EmailService()

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
  code?: string // 2FA code
}

export interface RegisterData {
  username: string
  firstName: string
  lastName: string
  email: string
  password: string
  countryId: string
  phoneNumber?: string
  referralCode?: string
}

export interface AuthResult {
  user: Omit<User, 'password' | 'twoFactorSecret'>
  token?: string
  refreshToken?: string
  twoFactorChallenge?: boolean
  requiresEmailVerification?: boolean
}

export interface TwoFactorSetup {
  secret: string
  qrCode: string
  backupCodes: string[]
}

export class AuthService {
  /**
   * Authenticate user with email and password
   */
  async login(credentials: LoginCredentials, ipAddress: string, userAgent?: string): Promise<AuthResult> {
    const { email, password, remember = false, code } = credentials

    // Find user by email
    const user = await prisma.user.findUnique({
      where: { email },
      include: {
        userRoles: {
          include: {
            role: true
          }
        }
      }
    })

    if (!user) {
      throw new Error('Invalid credentials')
    }

    // Verify password
    const isValidPassword = await verifyPassword(password, user.password)
    if (!isValidPassword) {
      throw new Error('Invalid credentials')
    }

    // Check user status
    if (user.status === 'SUSPENDED') {
      throw new Error('Account suspended')
    }

    if (user.status === 'INACTIVE') {
      throw new Error('Account inactive')
    }

    // Handle 2FA challenge
    if (user.isTwoFactorEnabled && user.twoFactorSecret) {
      if (!code) {
        return {
          user: this.sanitizeUser(user),
          twoFactorChallenge: true
        }
      }

      // Verify 2FA code
      const isValid2FA = speakeasy.totp.verify({
        secret: user.twoFactorSecret,
        encoding: 'base32',
        token: code,
        window: config.TWO_FACTOR_WINDOW
      })

      if (!isValid2FA) {
        throw new Error('Invalid 2FA code')
      }
    }

    // Check email verification
    if (user.status === 'PENDING_VERIFICATION') {
      return {
        user: this.sanitizeUser(user),
        requiresEmailVerification: true
      }
    }

    // Generate tokens
    const tokens = await this.generateTokens(user.id, remember)

    // Update last login
    await prisma.user.update({
      where: { id: user.id },
      data: {
        lastLoginAt: new Date(),
        lastLoginIp: ipAddress
      }
    })

    // Create session record
    await this.createSession(user.id, tokens.token, tokens.refreshToken, ipAddress, userAgent)

    logger.info(`User login successful: ${user.email}`)

    return {
      user: this.sanitizeUser(user),
      token: tokens.token,
      refreshToken: tokens.refreshToken
    }
  }

  /**
   * Register a new user
   */
  async register(data: RegisterData): Promise<AuthResult> {
    const { username, firstName, lastName, email, password, countryId, phoneNumber, referralCode } = data

    // Check if user already exists
    const existingUser = await prisma.user.findFirst({
      where: {
        OR: [
          { email },
          { username }
        ]
      }
    })

    if (existingUser) {
      throw new Error(existingUser.email === email ? 'Email already registered' : 'Username already taken')
    }

    // Validate referral code if provided
    let referredByUserId: string | undefined
    if (referralCode) {
      const referrer = await prisma.user.findUnique({
        where: { referralCode }
      })
      
      if (!referrer) {
        throw new Error('Invalid referral code')
      }
      
      referredByUserId = referrer.id
    }

    // Hash password
    const hashedPassword = await hashPassword(password)
    
    // Generate unique referral code
    const userReferralCode = await this.generateUniqueReferralCode()

    // Create user
    const user = await prisma.user.create({
      data: {
        username,
        firstName,
        lastName,
        email,
        password: hashedPassword,
        countryId,
        phoneNumber,
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
    })

    // Send verification email
    await this.sendEmailVerification(user)

    logger.info(`User registration successful: ${user.email}`)

    return {
      user: this.sanitizeUser(user),
      requiresEmailVerification: true
    }
  }

  /**
   * Setup two-factor authentication
   */
  async setup2FA(userId: string): Promise<TwoFactorSetup> {
    const user = await prisma.user.findUnique({
      where: { id: userId }
    })

    if (!user) {
      throw new Error('User not found')
    }

    // Generate secret
    const secret = speakeasy.generateSecret({
      name: `${config.TWO_FACTOR_ISSUER} (${user.email})`,
      issuer: config.TWO_FACTOR_ISSUER,
      length: 32
    })

    // Generate QR code
    const qrCode = await QRCode.toDataURL(secret.otpauth_url!)

    // Generate backup codes
    const backupCodes = Array.from({ length: 8 }, () => generateRandomCode(8))

    // Store secret temporarily (not confirmed yet)
    await prisma.user.update({
      where: { id: userId },
      data: {
        twoFactorSecret: secret.base32,
        twoFactorRecoveryCodes: backupCodes
      }
    })

    return {
      secret: secret.base32!,
      qrCode,
      backupCodes
    }
  }

  /**
   * Confirm two-factor authentication setup
   */
  async confirm2FA(userId: string, code: string): Promise<boolean> {
    const user = await prisma.user.findUnique({
      where: { id: userId }
    })

    if (!user || !user.twoFactorSecret) {
      throw new Error('2FA setup not found')
    }

    // Verify the code
    const isValid = speakeasy.totp.verify({
      secret: user.twoFactorSecret,
      encoding: 'base32',
      token: code,
      window: config.TWO_FACTOR_WINDOW
    })

    if (!isValid) {
      throw new Error('Invalid 2FA code')
    }

    // Enable 2FA
    await prisma.user.update({
      where: { id: userId },
      data: {
        isTwoFactorEnabled: true
      }
    })

    return true
  }

  /**
   * Disable two-factor authentication
   */
  async disable2FA(userId: string): Promise<boolean> {
    await prisma.user.update({
      where: { id: userId },
      data: {
        isTwoFactorEnabled: false,
        twoFactorSecret: null,
        twoFactorRecoveryCodes: null
      }
    })

    return true
  }

  /**
   * Send password reset email
   */
  async forgotPassword(email: string): Promise<boolean> {
    const user = await prisma.user.findUnique({
      where: { email }
    })

    if (!user) {
      // Return true even if user not found (security)
      return true
    }

    const token = generateRandomCode(64)
    const expiresAt = new Date(Date.now() + 3600000) // 1 hour

    await prisma.passwordReset.create({
      data: {
        userId: user.id,
        email,
        token,
        expiresAt
      }
    })

    await emailService.sendPasswordResetEmail(user.email, token)

    return true
  }

  /**
   * Reset password with token
   */
  async resetPassword(token: string, email: string, newPassword: string): Promise<boolean> {
    const resetRecord = await prisma.passwordReset.findFirst({
      where: {
        token,
        email,
        isUsed: false,
        expiresAt: {
          gt: new Date()
        }
      },
      include: {
        user: true
      }
    })

    if (!resetRecord) {
      throw new Error('Invalid or expired reset token')
    }

    const hashedPassword = await hashPassword(newPassword)

    await prisma.$transaction([
      prisma.user.update({
        where: { id: resetRecord.userId },
        data: {
          password: hashedPassword
        }
      }),
      prisma.passwordReset.update({
        where: { id: resetRecord.id },
        data: {
          isUsed: true
        }
      })
    ])

    return true
  }

  /**
   * Send email verification
   */
  async sendEmailVerification(user: User): Promise<boolean> {
    const token = generateRandomCode(64)
    const expiresAt = new Date(Date.now() + 86400000) // 24 hours

    await prisma.emailVerification.create({
      data: {
        userId: user.id,
        email: user.email,
        token,
        expiresAt
      }
    })

    await emailService.sendEmailVerification(user.email, token)

    return true
  }

  /**
   * Verify email with token
   */
  async verifyEmail(token: string): Promise<boolean> {
    const verification = await prisma.emailVerification.findFirst({
      where: {
        token,
        isUsed: false,
        expiresAt: {
          gt: new Date()
        }
      },
      include: {
        user: true
      }
    })

    if (!verification) {
      throw new Error('Invalid or expired verification token')
    }

    await prisma.$transaction([
      prisma.user.update({
        where: { id: verification.userId },
        data: {
          emailVerifiedAt: new Date(),
          status: 'ACTIVE'
        }
      }),
      prisma.emailVerification.update({
        where: { id: verification.id },
        data: {
          isUsed: true
        }
      })
    ])

    return true
  }

  /**
   * Refresh authentication token
   */
  async refreshToken(refreshToken: string): Promise<{ token: string; refreshToken: string } | null> {
    const session = await prisma.userSession.findUnique({
      where: { 
        refreshToken,
        isActive: true,
        expiresAt: {
          gt: new Date()
        }
      },
      include: {
        user: true
      }
    })

    if (!session) {
      return null
    }

    // Generate new tokens
    const tokens = await this.generateTokens(session.userId, true)

    // Update session
    await prisma.userSession.update({
      where: { id: session.id },
      data: {
        token: tokens.token,
        refreshToken: tokens.refreshToken,
        updatedAt: new Date()
      }
    })

    return tokens
  }

  /**
   * Logout user (invalidate session)
   */
  async logout(token: string): Promise<boolean> {
    await prisma.userSession.updateMany({
      where: { token },
      data: { isActive: false }
    })

    return true
  }

  /**
   * Logout from all devices
   */
  async logoutAllDevices(userId: string): Promise<boolean> {
    await prisma.userSession.updateMany({
      where: { userId },
      data: { isActive: false }
    })

    return true
  }

  /**
   * Private helper methods
   */
  private async generateTokens(userId: string, remember: boolean) {
    const expiresIn = remember ? config.JWT_REFRESH_EXPIRES_IN : config.JWT_EXPIRES_IN
    
    const token = jwt.sign({ userId }, config.JWT_SECRET, { expiresIn })
    const refreshToken = jwt.sign({ userId, type: 'refresh' }, config.JWT_REFRESH_SECRET, { 
      expiresIn: config.JWT_REFRESH_EXPIRES_IN 
    })

    return { token, refreshToken }
  }

  private async createSession(userId: string, token: string, refreshToken: string | undefined, ipAddress: string, userAgent?: string) {
    const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) // 30 days

    await prisma.userSession.create({
      data: {
        userId,
        token,
        refreshToken,
        ipAddress,
        userAgent: userAgent?.slice(0, 500), // Limit user agent length
        expiresAt
      }
    })
  }

  private async generateUniqueReferralCode(): Promise<string> {
    let code: string
    let isUnique = false

    while (!isUnique) {
      code = generateRandomCode(8).toUpperCase()
      
      const existing = await prisma.user.findUnique({
        where: { referralCode: code }
      })
      
      if (!existing) {
        isUnique = true
        return code
      }
    }

    throw new Error('Unable to generate unique referral code')
  }

  private sanitizeUser(user: User & { userRoles?: any[] }): Omit<User, 'password' | 'twoFactorSecret'> {
    const { password, twoFactorSecret, ...sanitizedUser } = user
    return sanitizedUser
  }
}

export const authService = new AuthService()