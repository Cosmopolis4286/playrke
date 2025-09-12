import { z } from 'zod'

// Enums para usuarios
export enum UserStatus {
  ACTIVE = 'ACTIVE',
  INACTIVE = 'INACTIVE',
  SUSPENDED = 'SUSPENDED',
  BANNED = 'BANNED',
  PENDING_VERIFICATION = 'PENDING_VERIFICATION'
}

export enum UserRole {
  ADMIN = 'ADMIN',
  CUSTOMER = 'CUSTOMER',
  SELLER = 'SELLER',
  COMMERCE = 'COMMERCE',
  COMPANY = 'COMPANY',
  TREASURER = 'TREASURER',
  EXTERNAL_PLAYER = 'EXTERNAL_PLAYER'
}

export enum NotificationType {
  EMAIL = 'EMAIL',
  SMS = 'SMS',
  PUSH = 'PUSH',
  IN_APP = 'IN_APP'
}

// Schemas de validación
export const UpdateUserProfileSchema = z.object({
  firstName: z.string().min(2).max(50).optional(),
  lastName: z.string().min(2).max(50).optional(),
  phoneNumber: z.string().regex(/^\+?[1-9]\d{1,14}$/).optional(),
  countryId: z.string().uuid().optional(),
  timeZoneId: z.string().uuid().optional(),
  profilePhotoUrl: z.string().url().optional()
})

export const ChangePasswordSchema = z.object({
  currentPassword: z.string().min(1),
  newPassword: z.string().min(8).max(128),
  confirmPassword: z.string().min(1)
}).refine(data => data.newPassword === data.confirmPassword, {
  message: "Las contraseñas no coinciden",
  path: ["confirmPassword"]
})

export const UpdateUserSettingsSchema = z.object({
  language: z.string().length(2).optional(),
  currency: z.string().length(3).optional(),
  timezone: z.string().optional(),
  emailNotifications: z.boolean().optional(),
  smsNotifications: z.boolean().optional(),
  pushNotifications: z.boolean().optional(),
  marketingEmails: z.boolean().optional(),
  twoFactorEnabled: z.boolean().optional()
})

export const CreateUserSchema = z.object({
  username: z.string().min(3).max(30).regex(/^[a-zA-Z0-9_]+$/),
  email: z.string().email(),
  password: z.string().min(8).max(128),
  firstName: z.string().min(2).max(50),
  lastName: z.string().min(2).max(50),
  phoneNumber: z.string().regex(/^\+?[1-9]\d{1,14}$/).optional(),
  countryId: z.string().uuid(),
  referralCode: z.string().optional(),
  roles: z.array(z.nativeEnum(UserRole)).optional()
})

export const AssignRoleSchema = z.object({
  userId: z.string().uuid(),
  roles: z.array(z.nativeEnum(UserRole))
})

export const UpdateUserStatusSchema = z.object({
  status: z.nativeEnum(UserStatus),
  reason: z.string().min(10).max(500).optional()
})

export const CreateNotificationSchema = z.object({
  userId: z.string().uuid(),
  type: z.nativeEnum(NotificationType),
  title: z.string().min(1).max(100),
  message: z.string().min(1).max(500),
  data: z.record(z.any()).optional()
})

// Tipos derivados
export type UpdateUserProfileRequest = z.infer<typeof UpdateUserProfileSchema>
export type ChangePasswordRequest = z.infer<typeof ChangePasswordSchema>
export type UpdateUserSettingsRequest = z.infer<typeof UpdateUserSettingsSchema>
export type CreateUserRequest = z.infer<typeof CreateUserSchema>
export type AssignRoleRequest = z.infer<typeof AssignRoleSchema>
export type UpdateUserStatusRequest = z.infer<typeof UpdateUserStatusSchema>
export type CreateNotificationRequest = z.infer<typeof CreateNotificationSchema>

// Interfaces de respuesta
export interface CountryInfo {
  id: string
  name: string
  code: string
  flag?: string
}

export interface TimeZoneInfo {
  id: string
  name: string
  offset: string
  abbreviation: string
}

export interface UserResponse {
  id: string
  username: string
  email: string
  firstName: string
  lastName: string
  fullName: string
  phoneNumber?: string
  profilePhotoUrl?: string
  status: UserStatus
  emailVerifiedAt?: Date
  country?: CountryInfo
  timeZone?: TimeZoneInfo
  referralCode: string
  referredBy?: {
    id: string
    username: string
    fullName: string
  }
  roles: UserRole[]
  createdAt: Date
  updatedAt: Date
  lastLoginAt?: Date
}

export interface UserProfileResponse extends UserResponse {
  settings: UserSettings
  stats: UserStats
  notifications: NotificationSettings
}

export interface UserSettings {
  language: string
  currency: string
  timezone: string
  emailNotifications: boolean
  smsNotifications: boolean
  pushNotifications: boolean
  marketingEmails: boolean
  twoFactorEnabled: boolean
  privacyLevel: 'PUBLIC' | 'FRIENDS' | 'PRIVATE'
}

export interface UserStats {
  totalGamesPlayed: number
  totalWinnings: number
  totalDeposits: number
  totalWithdrawals: number
  accountAge: number
  lastActivity: Date
  favoriteGames: string[]
}

export interface NotificationSettings {
  email: boolean
  sms: boolean
  push: boolean
  gameUpdates: boolean
  promotions: boolean
  security: boolean
  newsletter: boolean
}

export interface UserListResponse {
  users: UserResponse[]
  total: number
  page: number
  totalPages: number
}

export interface NotificationResponse {
  id: string
  userId: string
  type: NotificationType
  title: string
  message: string
  data: Record<string, any>
  isRead: boolean
  createdAt: Date
  readAt?: Date
}

export interface UserActivityLog {
  id: string
  userId: string
  action: string
  description: string
  ipAddress: string
  userAgent: string
  metadata: Record<string, any>
  createdAt: Date
}

export interface ReferralInfo {
  code: string
  totalReferrals: number
  activeReferrals: number
  totalEarnings: number
  referrals: Array<{
    id: string
    username: string
    joinedAt: Date
    isActive: boolean
    earnings: number
  }>
}

export interface UserVerificationStatus {
  email: boolean
  phone: boolean
  identity: boolean
  address: boolean
  paymentMethod: boolean
  completionLevel: number
}

// Tipos para administración
export interface AdminUserResponse extends UserResponse {
  ipHistory: string[]
  deviceHistory: string[]
  loginHistory: Array<{
    ip: string
    userAgent: string
    location?: string
    loginAt: Date
  }>
  securityFlags: string[]
  notes: string[]
}

export interface UserSearchFilters {
  status?: UserStatus
  role?: UserRole
  country?: string
  registeredAfter?: Date
  registeredBefore?: Date
  lastLoginAfter?: Date
  lastLoginBefore?: Date
  hasVerifiedEmail?: boolean
  hasVerifiedPhone?: boolean
  search?: string
}

// Errores específicos de usuarios
export enum UserErrorCode {
  USER_NOT_FOUND = 'USER_NOT_FOUND',
  USERNAME_TAKEN = 'USERNAME_TAKEN',
  EMAIL_TAKEN = 'EMAIL_TAKEN',
  INVALID_CREDENTIALS = 'INVALID_CREDENTIALS',
  USER_SUSPENDED = 'USER_SUSPENDED',
  USER_BANNED = 'USER_BANNED',
  EMAIL_NOT_VERIFIED = 'EMAIL_NOT_VERIFIED',
  PHONE_NOT_VERIFIED = 'PHONE_NOT_VERIFIED',
  WEAK_PASSWORD = 'WEAK_PASSWORD',
  REFERRAL_CODE_INVALID = 'REFERRAL_CODE_INVALID',
  ROLE_NOT_AUTHORIZED = 'ROLE_NOT_AUTHORIZED',
  PROFILE_INCOMPLETE = 'PROFILE_INCOMPLETE'
}