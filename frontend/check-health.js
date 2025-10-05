#!/usr/bin/env node

/**
 * Script de verificación de salud del proyecto
 * PlayRKE Bingo - Frontend Nuxt 3
 *
 * Este script verifica que el proyecto esté correctamente configurado
 * y listo para desarrollo o producción.
 *
 * Uso:
 *   node check-health.js
 */

const fs = require('fs')
const path = require('path')
const { execSync } = require('child_process')

// Colores para terminal
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  red: '\x1b[31m',
  cyan: '\x1b[36m',
}

const log = {
  info: (msg) => console.log(`${colors.blue}ℹ${colors.reset} ${msg}`),
  success: (msg) => console.log(`${colors.green}✓${colors.reset} ${msg}`),
  warning: (msg) => console.log(`${colors.yellow}⚠${colors.reset} ${msg}`),
  error: (msg) => console.log(`${colors.red}✗${colors.reset} ${msg}`),
  title: (msg) => console.log(`\n${colors.bright}${colors.cyan}${msg}${colors.reset}\n`),
}

// Contadores
let checks = { passed: 0, failed: 0, warnings: 0 }

// Verificar que un archivo existe
const fileExists = (filePath) => {
  return fs.existsSync(path.join(__dirname, filePath))
}

// Verificar que un comando existe
const commandExists = (command) => {
  try {
    execSync(`${command} --version`, { stdio: 'ignore' })
    return true
  } catch {
    return false
  }
}

// Obtener versión de un comando
const getVersion = (command) => {
  try {
    const output = execSync(`${command} --version`, { encoding: 'utf-8' })
    return output.trim().split('\n')[0]
  } catch {
    return null
  }
}

// Verificar versión mínima de Node
const checkNodeVersion = () => {
  const version = process.version.replace('v', '')
  const major = parseInt(version.split('.')[0])
  return major >= 18
}

// Verificar .env
const checkEnvFile = () => {
  if (!fileExists('.env')) {
    return { exists: false, configured: false }
  }

  const envContent = fs.readFileSync(path.join(__dirname, '.env'), 'utf-8')
  const requiredVars = [
    'NUXT_PUBLIC_API_BASE',
    'NUXT_PUBLIC_PUSHER_KEY',
    'NUXT_PUBLIC_APP_URL',
  ]

  const configured = requiredVars.every((varName) => envContent.includes(varName))

  return { exists: true, configured }
}

// Verificar dependencias instaladas
const checkDependencies = () => {
  return fileExists('node_modules')
}

// Verificar archivos críticos
const checkCriticalFiles = () => {
  const files = [
    'package.json',
    'nuxt.config.ts',
    'tsconfig.json',
    'tailwind.config.ts',
    '.env.example',
  ]

  return files.map((file) => ({
    file,
    exists: fileExists(file),
  }))
}

// Verificar estructura de layers
const checkLayersStructure = () => {
  const layers = ['admin', 'player', 'shared']
  const dirs = ['components', 'composables', 'pages', 'stores', 'utils']

  const results = []

  layers.forEach((layer) => {
    const layerPath = `layers/${layer}`
    if (fileExists(layerPath)) {
      results.push({ layer, exists: true })
    } else {
      results.push({ layer, exists: false })
    }
  })

  return results
}

// Verificar que el backend esté corriendo
const checkBackend = () => {
  const envFile = path.join(__dirname, '.env')
  if (!fs.existsSync(envFile)) {
    return { url: null, running: false }
  }

  const envContent = fs.readFileSync(envFile, 'utf-8')
  const match = envContent.match(/NUXT_PUBLIC_API_BASE=(.+)/)

  if (!match) {
    return { url: null, running: false }
  }

  const apiUrl = match[1].trim()
  const baseUrl = apiUrl.replace('/api', '')

  // Intentar hacer ping al backend
  try {
    const http = require('http')
    const url = new URL(baseUrl)

    return new Promise((resolve) => {
      const req = http.get(
        {
          hostname: url.hostname,
          port: url.port || 80,
          path: '/',
          timeout: 2000,
        },
        (res) => {
          resolve({ url: baseUrl, running: res.statusCode < 500 })
        }
      )

      req.on('error', () => {
        resolve({ url: baseUrl, running: false })
      })

      req.on('timeout', () => {
        req.destroy()
        resolve({ url: baseUrl, running: false })
      })
    })
  } catch {
    return { url: baseUrl, running: false }
  }
}

// Función principal
async function main() {
  log.title('🏥 PlayRKE Bingo - Health Check')

  // 1. Verificar Node.js
  log.title('1. Entorno de Sistema')

  const nodeVersion = getVersion('node')
  if (nodeVersion && checkNodeVersion()) {
    log.success(`Node.js: ${nodeVersion}`)
    checks.passed++
  } else {
    log.error(`Node.js: ${nodeVersion || 'No encontrado'} (requiere v18+)`)
    checks.failed++
  }

  const npmVersion = getVersion('npm')
  if (npmVersion) {
    log.success(`npm: ${npmVersion}`)
    checks.passed++
  } else {
    log.error('npm: No encontrado')
    checks.failed++
  }

  // 2. Verificar archivos críticos
  log.title('2. Archivos de Configuración')

  const criticalFiles = checkCriticalFiles()
  criticalFiles.forEach(({ file, exists }) => {
    if (exists) {
      log.success(`${file}`)
      checks.passed++
    } else {
      log.error(`${file} - No encontrado`)
      checks.failed++
    }
  })

  // 3. Verificar .env
  log.title('3. Variables de Entorno')

  const envCheck = checkEnvFile()
  if (envCheck.exists) {
    if (envCheck.configured) {
      log.success('.env configurado correctamente')
      checks.passed++
    } else {
      log.warning('.env existe pero faltan variables requeridas')
      log.info('  Ejecuta: npm run setup')
      checks.warnings++
    }
  } else {
    log.error('.env no encontrado')
    log.info('  Ejecuta: npm run setup')
    checks.failed++
  }

  // 4. Verificar dependencias
  log.title('4. Dependencias')

  if (checkDependencies()) {
    log.success('node_modules instalado')
    checks.passed++

    // Verificar package.json vs node_modules
    try {
      const packageJson = require('./package.json')
      const installedDeps = Object.keys(packageJson.dependencies || {}).length
      const installedDevDeps = Object.keys(packageJson.devDependencies || {}).length
      log.info(`  ${installedDeps} dependencias de producción`)
      log.info(`  ${installedDevDeps} dependencias de desarrollo`)
    } catch (e) {
      log.warning('  No se pudo leer package.json')
      checks.warnings++
    }
  } else {
    log.error('node_modules no encontrado')
    log.info('  Ejecuta: npm install')
    checks.failed++
  }

  // 5. Verificar estructura de layers
  log.title('5. Estructura de Layers')

  const layersCheck = checkLayersStructure()
  layersCheck.forEach(({ layer, exists }) => {
    if (exists) {
      log.success(`layers/${layer}`)
      checks.passed++
    } else {
      log.error(`layers/${layer} - No encontrado`)
      checks.failed++
    }
  })

  // 6. Verificar backend
  log.title('6. Conectividad Backend')

  const backendCheck = await checkBackend()
  if (backendCheck.url) {
    if (backendCheck.running) {
      log.success(`Backend Laravel: ${backendCheck.url} ✓`)
      checks.passed++
    } else {
      log.warning(`Backend Laravel: ${backendCheck.url} (no responde)`)
      log.info('  Ejecuta: php artisan serve')
      checks.warnings++
    }
  } else {
    log.warning('URL de backend no configurada en .env')
    checks.warnings++
  }

  // 7. Verificar configuración de Git
  log.title('7. Control de Versiones')

  if (commandExists('git')) {
    const gitVersion = getVersion('git')
    log.success(`Git: ${gitVersion}`)
    checks.passed++

    // Verificar .gitignore
    if (fileExists('.gitignore')) {
      log.success('.gitignore configurado')
      checks.passed++
    } else {
      log.warning('.gitignore no encontrado')
      checks.warnings++
    }
  } else {
    log.warning('Git no instalado')
    checks.warnings++
  }

  // 8. Verificar herramientas de desarrollo
  log.title('8. Herramientas de Desarrollo')

  if (fileExists('.prettierrc')) {
    log.success('Prettier configurado')
    checks.passed++
  } else {
    log.warning('Prettier no configurado')
    checks.warnings++
  }

  if (fileExists('.eslintrc.cjs')) {
    log.success('ESLint configurado')
    checks.passed++
  } else {
    log.warning('ESLint no configurado')
    checks.warnings++
  }

  // 9. Resumen final
  log.title('📊 Resumen')

  const total = checks.passed + checks.failed + checks.warnings

  console.log(`Total de verificaciones: ${total}`)
  console.log(`${colors.green}✓ Pasadas: ${checks.passed}${colors.reset}`)

  if (checks.warnings > 0) {
    console.log(`${colors.yellow}⚠ Advertencias: ${checks.warnings}${colors.reset}`)
  }

  if (checks.failed > 0) {
    console.log(`${colors.red}✗ Fallidas: ${checks.failed}${colors.reset}`)
  }

  // Conclusión
  console.log('\n' + '='.repeat(60))

  if (checks.failed === 0 && checks.warnings === 0) {
    log.title('✅ ¡Proyecto en perfecto estado!')
    console.log('Puedes iniciar el desarrollo con: npm run dev\n')
    process.exit(0)
  } else if (checks.failed === 0) {
    log.title('⚠️  Proyecto funcional con advertencias')
    console.log('Revisa las advertencias arriba para mejorar la configuración.\n')
    process.exit(0)
  } else {
    log.title('❌ Proyecto con problemas críticos')
    console.log('Corrige los errores antes de continuar.\n')
    console.log('Recursos:')
    console.log('  - Instalación: docs/installation.md')
    console.log('  - Inicio rápido: docs/quick-start.md\n')
    process.exit(1)
  }
}

// Ejecutar
main().catch((error) => {
  log.error('Error durante health check:')
  console.error(error)
  process.exit(1)
})
