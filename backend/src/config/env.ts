import { z } from 'zod'
import dotenv from 'dotenv'

// Load environment variables
dotenv.config()

// Environment schema validation
const envSchema = z.object({
  // Server
  NODE_ENV: z.enum(['development', 'production', 'test']).default('development'),
  PORT: z.coerce.number().default(3001),
  HOST: z.string().default('0.0.0.0'),
  
  // Database
  DATABASE_URL: z.string().url('Database URL is required'),
  
  // Redis
  REDIS_URL: z.string().url().optional(),
  REDIS_PASSWORD: z.string().optional(),
  
  // JWT
  JWT_SECRET: z.string().min(32, 'JWT secret must be at least 32 characters'),
  JWT_EXPIRES_IN: z.string().default('7d'),
  JWT_REFRESH_SECRET: z.string().min(32, 'JWT refresh secret must be at least 32 characters'),
  JWT_REFRESH_EXPIRES_IN: z.string().default('30d'),
  
  // Encryption
  ENCRYPTION_KEY: z.string().min(32, 'Encryption key must be at least 32 characters'),
  
  // Email
  MAIL_HOST: z.string().optional(),
  MAIL_PORT: z.coerce.number().default(587),
  MAIL_USERNAME: z.string().optional(),
  MAIL_PASSWORD: z.string().optional(),
  MAIL_FROM: z.string().email().optional(),
  MAIL_FROM_NAME: z.string().default('PlayRKE'),
  
  // 2FA
  TWO_FACTOR_ISSUER: z.string().default('PlayRKE'),
  TWO_FACTOR_WINDOW: z.coerce.number().default(2),
  
  // File Upload
  MAX_FILE_SIZE: z.coerce.number().default(10485760), // 10MB
  UPLOAD_PATH: z.string().default('./uploads'),
  
  // CORS
  CORS_ORIGIN: z.string().url().default('http://localhost:5173'),
  
  // Rate Limiting
  RATE_LIMIT_MAX: z.coerce.number().default(1000),
  RATE_LIMIT_WINDOW: z.coerce.number().default(15), // minutes
  
  // Session
  SESSION_SECRET: z.string().min(32, 'Session secret must be at least 32 characters'),
  
  // Laravel Compatibility
  LARAVEL_BASE_URL: z.string().url().optional(),
  LARAVEL_API_KEY: z.string().optional(),
  
  // External APIs
  BINANCE_API_KEY: z.string().optional(),
  BINANCE_SECRET_KEY: z.string().optional(),
  
  // Logging
  LOG_LEVEL: z.enum(['fatal', 'error', 'warn', 'info', 'debug', 'trace']).default('info'),
  LOG_FILE: z.string().default('./logs/app.log'),
})

type EnvConfig = z.infer<typeof envSchema>

// Validate and export configuration
const validateEnv = (): EnvConfig => {
  try {
    return envSchema.parse(process.env)
  } catch (error) {
    if (error instanceof z.ZodError) {
      const missingVars = error.errors.map(err => `${err.path.join('.')}: ${err.message}`).join('\n')
      throw new Error(`❌ Invalid environment configuration:\n${missingVars}`)
    }
    throw error
  }
}

export const config = validateEnv()

// Type-safe environment access
export type Config = typeof config

// Helper functions
export const isDevelopment = () => config.NODE_ENV === 'development'
export const isProduction = () => config.NODE_ENV === 'production'
export const isTest = () => config.NODE_ENV === 'test'

// Email configuration helper
export const isEmailEnabled = () => !!(
  config.MAIL_HOST && 
  config.MAIL_USERNAME && 
  config.MAIL_PASSWORD
)

// Redis configuration helper
export const isRedisEnabled = () => !!config.REDIS_URL

// Laravel integration helper
export const isLaravelIntegrationEnabled = () => !!(
  config.LARAVEL_BASE_URL && 
  config.LARAVEL_API_KEY
)