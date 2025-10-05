#!/usr/bin/env node

/**
 * Script de configuración de variables de entorno
 * PlayRKE Bingo - Frontend Nuxt 3
 *
 * Este script configura automáticamente el archivo .env
 * con valores por defecto para desarrollo local.
 *
 * Uso:
 *   node setup-env.js [--production]
 */

const fs = require('fs')
const path = require('path')
const readline = require('readline')

// Colores para terminal
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  red: '\x1b[31m'
}

const log = {
  info: (msg) => console.log(`${colors.blue}ℹ${colors.reset} ${msg}`),
  success: (msg) => console.log(`${colors.green}✓${colors.reset} ${msg}`),
  warning: (msg) => console.log(`${colors.yellow}⚠${colors.reset} ${msg}`),
  error: (msg) => console.log(`${colors.red}✗${colors.reset} ${msg}`),
  title: (msg) => console.log(`\n${colors.bright}${colors.blue}${msg}${colors.reset}\n`)
}

// Configuración por defecto
const defaultConfig = {
  development: {
    NUXT_PUBLIC_API_BASE: 'http://localhost:8000/api',
    NUXT_PUBLIC_PUSHER_KEY: 'local',
    NUXT_PUBLIC_PUSHER_HOST: 'localhost',
    NUXT_PUBLIC_PUSHER_PORT: '6001',
    NUXT_PUBLIC_PUSHER_CLUSTER: 'mt1',
    NUXT_PUBLIC_PUSHER_TLS: 'false',
    NUXT_PUBLIC_APP_NAME: 'PlayRKE Bingo',
    NUXT_PUBLIC_APP_URL: 'http://localhost:3000',
    NODE_ENV: 'development'
  },
  production: {
    NUXT_PUBLIC_API_BASE: 'https://api.playrke.com/api',
    NUXT_PUBLIC_PUSHER_KEY: 'your-pusher-key',
    NUXT_PUBLIC_PUSHER_HOST: 'playrke.com',
    NUXT_PUBLIC_PUSHER_PORT: '443',
    NUXT_PUBLIC_PUSHER_CLUSTER: 'mt1',
    NUXT_PUBLIC_PUSHER_TLS: 'true',
    NUXT_PUBLIC_APP_NAME: 'PlayRKE Bingo',
    NUXT_PUBLIC_APP_URL: 'https://playrke.com',
    NODE_ENV: 'production'
  }
}

// Interfaz de línea de comandos
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
})

const question = (query) => {
  return new Promise((resolve) => rl.question(query, resolve))
}

// Función principal
async function main() {
  log.title('🚀 PlayRKE Bingo - Configuración de Variables de Entorno')

  const isProduction = process.argv.includes('--production')
  const envPath = path.join(__dirname, '.env')
  const examplePath = path.join(__dirname, '.env.example')

  // Verificar si .env ya existe
  if (fs.existsSync(envPath)) {
    log.warning('El archivo .env ya existe.')
    const overwrite = await question('¿Deseas sobrescribirlo? (s/n): ')

    if (overwrite.toLowerCase() !== 's') {
      log.info('Operación cancelada.')
      rl.close()
      return
    }

    // Backup del archivo existente
    const backupPath = `${envPath}.backup.${Date.now()}`
    fs.copyFileSync(envPath, backupPath)
    log.success(`Backup creado en: ${backupPath}`)
  }

  // Seleccionar modo
  let mode = 'development'
  if (!isProduction) {
    log.info('Selecciona el modo de configuración:')
    console.log('  1) Desarrollo (localhost)')
    console.log('  2) Producción')

    const choice = await question('\nOpción (1-2): ')
    mode = choice === '2' ? 'production' : 'development'
  } else {
    mode = 'production'
  }

  log.info(`Configurando en modo: ${colors.bright}${mode}${colors.reset}\n`)

  // Obtener configuración base
  const config = { ...defaultConfig[mode] }

  // Preguntar por valores personalizados
  log.info('Presiona Enter para usar el valor por defecto o escribe uno nuevo:\n')

  // API Base
  const apiBase = await question(`API Base [${config.NUXT_PUBLIC_API_BASE}]: `)
  if (apiBase) config.NUXT_PUBLIC_API_BASE = apiBase

  // App URL
  const appUrl = await question(`App URL [${config.NUXT_PUBLIC_APP_URL}]: `)
  if (appUrl) config.NUXT_PUBLIC_APP_URL = appUrl

  // WebSocket configuración
  console.log('\n--- WebSocket Configuration (Pusher/Reverb) ---')

  const pusherKey = await question(`Pusher Key [${config.NUXT_PUBLIC_PUSHER_KEY}]: `)
  if (pusherKey) config.NUXT_PUBLIC_PUSHER_KEY = pusherKey

  const pusherHost = await question(`Pusher Host [${config.NUXT_PUBLIC_PUSHER_HOST}]: `)
  if (pusherHost) config.NUXT_PUBLIC_PUSHER_HOST = pusherHost

  const pusherPort = await question(`Pusher Port [${config.NUXT_PUBLIC_PUSHER_PORT}]: `)
  if (pusherPort) config.NUXT_PUBLIC_PUSHER_PORT = pusherPort

  const pusherTls = await question(`Pusher TLS [${config.NUXT_PUBLIC_PUSHER_TLS}]: `)
  if (pusherTls) config.NUXT_PUBLIC_PUSHER_TLS = pusherTls

  // App Name
  const appName = await question(`\nApp Name [${config.NUXT_PUBLIC_APP_NAME}]: `)
  if (appName) config.NUXT_PUBLIC_APP_NAME = appName

  // Generar contenido del archivo .env
  const envContent = generateEnvContent(config, mode)

  // Escribir archivo
  fs.writeFileSync(envPath, envContent)
  log.success(`\nArchivo .env creado exitosamente en: ${envPath}`)

  // Mostrar siguiente pasos
  console.log('\n' + '='.repeat(60))
  log.title('✅ Configuración completada')
  console.log('Siguientes pasos:')
  console.log('  1. npm install          - Instalar dependencias')
  console.log('  2. npm run dev          - Iniciar servidor de desarrollo')
  console.log('\nRecursos:')
  console.log('  - Documentación: docs/installation.md')
  console.log('  - API Endpoints: docs/api-endpoints-required.md')
  console.log('='.repeat(60) + '\n')

  rl.close()
}

// Generar contenido del archivo .env
function generateEnvContent(config, mode) {
  const timestamp = new Date().toISOString()

  let content = `# PlayRKE Bingo - Frontend Environment Variables
# Modo: ${mode}
# Generado: ${timestamp}

# ========================================
# API Configuration
# ========================================
NUXT_PUBLIC_API_BASE=${config.NUXT_PUBLIC_API_BASE}

# ========================================
# WebSocket Configuration (Laravel Reverb)
# ========================================
NUXT_PUBLIC_PUSHER_KEY=${config.NUXT_PUBLIC_PUSHER_KEY}
NUXT_PUBLIC_PUSHER_HOST=${config.NUXT_PUBLIC_PUSHER_HOST}
NUXT_PUBLIC_PUSHER_PORT=${config.NUXT_PUBLIC_PUSHER_PORT}
NUXT_PUBLIC_PUSHER_CLUSTER=${config.NUXT_PUBLIC_PUSHER_CLUSTER}
NUXT_PUBLIC_PUSHER_TLS=${config.NUXT_PUBLIC_PUSHER_TLS}

# ========================================
# App Configuration
# ========================================
NUXT_PUBLIC_APP_NAME=${config.NUXT_PUBLIC_APP_NAME}
NUXT_PUBLIC_APP_URL=${config.NUXT_PUBLIC_APP_URL}
NODE_ENV=${config.NODE_ENV}

# ========================================
# Optional Services (descomentar si es necesario)
# ========================================

# Google Analytics
# NUXT_PUBLIC_GA_ID=G-XXXXXXXXXX

# Sentry (error tracking)
# NUXT_PUBLIC_SENTRY_DSN=https://xxx@sentry.io/xxx
# NUXT_PUBLIC_SENTRY_ENVIRONMENT=${mode}

# Google reCAPTCHA
# NUXT_PUBLIC_RECAPTCHA_SITE_KEY=your-site-key
`

  return content
}

// Ejecutar
main().catch((error) => {
  log.error('Error durante la configuración:')
  console.error(error)
  rl.close()
  process.exit(1)
})
