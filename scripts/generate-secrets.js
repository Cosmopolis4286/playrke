#!/usr/bin/env node

/**
 * PlayRKE - Generador de Secretos y Variables de Entorno
 *
 * @description Script robusto para generar archivos .env con secretos seguros
 * @version 2.0.0
 * @author PlayRKE Team
 *
 * Este script genera automáticamente:
 * - Archivos .env para backend (Laravel) y frontend (Nuxt 3)
 * - Claves de encriptación criptográficamente seguras
 * - Credenciales de WebSockets (Laravel Reverb)
 * - Variables de configuración para Docker y desarrollo local
 *
 * Uso:
 *   node scripts/generate-secrets.js [--force] [--docker] [--production]
 *
 * Flags:
 *   --force       Sobrescribir archivos .env existentes sin preguntar
 *   --docker      Generar configuración para entorno Docker
 *   --production  Generar configuración para producción
 */

import { randomBytes } from 'crypto';
import { writeFileSync, existsSync, readFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const rootDir = join(__dirname, '..');

// Parsear argumentos de línea de comandos
const args = process.argv.slice(2);
const flags = {
    force: args.includes('--force'),
    docker: args.includes('--docker'),
    production: args.includes('--production'),
    help: args.includes('--help') || args.includes('-h'),
};

// Colores para la consola
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    red: '\x1b[31m',
};

const log = {
    info: (msg) => console.log(`${colors.blue}ℹ${colors.reset} ${msg}`),
    success: (msg) => console.log(`${colors.green}✔${colors.reset} ${msg}`),
    warning: (msg) => console.log(`${colors.yellow}⚠${colors.reset} ${msg}`),
    error: (msg) => console.log(`${colors.red}✖${colors.reset} ${msg}`),
    title: (msg) => console.log(`\n${colors.bright}${colors.blue}${msg}${colors.reset}\n`),
};

// ============================================
// Funciones de Utilidad
// ============================================

/**
 * Muestra ayuda del script
 */
function showHelp() {
    console.log(`
${colors.bright}PlayRKE - Generador de Secretos${colors.reset}

${colors.blue}Uso:${colors.reset}
  node scripts/generate-secrets.js [opciones]

${colors.blue}Opciones:${colors.reset}
  --force       Sobrescribir archivos .env existentes sin backup
  --docker      Generar configuración para entorno Docker
  --production  Generar configuración para producción
  -h, --help    Mostrar esta ayuda

${colors.blue}Ejemplos:${colors.reset}
  node scripts/generate-secrets.js
  node scripts/generate-secrets.js --docker
  node scripts/generate-secrets.js --production --force
`);
    process.exit(0);
}

/**
 * Asegura que un directorio existe
 */
function ensureDirectoryExists(dirPath) {
    if (!existsSync(dirPath)) {
        log.info(`Creando directorio: ${dirPath}`);
        mkdirSync(dirPath, { recursive: true });
    }
}

/**
 * Valida que los directorios del proyecto existan
 */
function validateProjectStructure() {
    const backendPath = join(rootDir, 'backend');
    const frontendPath = join(rootDir, 'frontend');

    if (!existsSync(backendPath)) {
        log.error('No se encontró el directorio backend/');
        log.error('Asegúrate de estar en la raíz del proyecto PlayRKE');
        process.exit(1);
    }

    if (!existsSync(frontendPath)) {
        log.error('No se encontró el directorio frontend/');
        log.error('Asegúrate de estar en la raíz del proyecto PlayRKE');
        process.exit(1);
    }

    return { backendPath, frontendPath };
}

// ============================================
// Funciones de Generación de Secretos
// ============================================

/**
 * Genera una clave base64 de 32 bytes (criptográficamente segura)
 */
function generateBase64Key() {
    return randomBytes(32).toString('base64');
}

/**
 * Genera una clave hexadecimal de longitud especificada
 */
function generateHexKey(length = 32) {
    return randomBytes(length).toString('hex');
}

/**
 * Genera un ID aleatorio de 6 dígitos
 */
function generateAppId() {
    return Math.floor(100000 + Math.random() * 900000).toString();
}

/**
 * Genera una clave alfanumérica aleatoria criptográficamente segura
 */
function generateAlphanumericKey(length = 20) {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const charsLength = chars.length;
    let result = '';
    const randomValues = randomBytes(length);

    for (let i = 0; i < length; i++) {
        result += chars.charAt(randomValues[i] % charsLength);
    }
    return result;
}

/**
 * Genera una Laravel APP_KEY compatible
 * Intenta usar artisan en Docker o localmente, con fallback manual
 */
function generateLaravelKey() {
    const backendPath = join(rootDir, 'backend');

    // Intento 1: Docker
    if (flags.docker) {
        try {
            log.info('Intentando generar clave con Docker...');
            const key = execSync('docker-compose exec -T backend php artisan key:generate --show', {
                cwd: rootDir,
                encoding: 'utf-8',
                timeout: 10000,
            }).trim();
            log.success('Clave generada usando Docker');
            return key;
        } catch (error) {
            log.warning('Docker no disponible, intentando localmente...');
        }
    }

    // Intento 2: PHP local
    try {
        const key = execSync('php artisan key:generate --show', {
            cwd: backendPath,
            encoding: 'utf-8',
            timeout: 10000,
        }).trim();
        log.success('Clave generada usando PHP local');
        return key;
    } catch (error) {
        log.warning('PHP no disponible, generando clave manualmente');
    }

    // Fallback: Generación manual compatible con Laravel
    return `base64:${generateBase64Key()}`;
}

// ============================================
// Generadores de .env
// ============================================

/**
 * Genera el archivo .env raíz para Docker Compose
 */
function generateRootEnv(reverbConfig) {
    const envContent = `# ============================================
# Configuración Docker - PlayRKE Monorepo
# Generated: ${new Date().toISOString()}
# ============================================
# Copiar a .env para personalizar configuración local

# ==========================================
# General
# ==========================================
COMPOSE_PROJECT_NAME=playrke

# ==========================================
# MySQL Database
# ==========================================
DB_DATABASE=playrke
DB_USERNAME=playrke
DB_PASSWORD=playrke
DB_PORT=3307
MYSQL_ROOT_PASSWORD=root

# ==========================================
# Redis
# ==========================================
REDIS_PORT=6380

# ==========================================
# Backend Laravel
# ==========================================
APP_NAME=PlayRKE
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8001
APP_PORT=8001

# Laravel Reverb (WebSockets)
REVERB_APP_ID=${reverbConfig.reverbAppId}
REVERB_APP_KEY=${reverbConfig.reverbAppKey}
REVERB_APP_SECRET=${reverbConfig.reverbAppSecret}
REVERB_PORT=8082

# ==========================================
# Frontend Nuxt
# ==========================================
FRONTEND_PORT=3002
NUXT_PUBLIC_API_BASE=http://localhost:8001/api
NUXT_PUBLIC_WS_URL=ws://localhost:8082
FRONTEND_URL=http://localhost:3002

# ==========================================
# PhpMyAdmin
# ==========================================
PHPMYADMIN_PORT=8083

# ==========================================
# Docker Build (Opcional)
# ==========================================
USER_ID=1000
GROUP_ID=1000
`;

    const rootEnvPath = join(rootDir, '.env');

    if (!flags.force && existsSync(rootEnvPath)) {
        log.warning('El archivo .env raíz ya existe, respaldando como .env.backup');
        const backupPath = join(rootDir, '.env.backup');
        const existingContent = readFileSync(rootEnvPath, 'utf-8');
        writeFileSync(backupPath, existingContent);
    }

    writeFileSync(rootEnvPath, envContent);
    log.success('Archivo .env (raíz) generado correctamente');
}

/**
 * Genera el archivo .env para el backend (Laravel)
 */
function generateBackendEnv(options = {}) {
    // Determinar valores según entorno
    const isDocker = flags.docker;
    const isProduction = flags.production;

    const {
        appName = 'PlayRKE',
        appEnv = isProduction ? 'production' : 'local',
        appUrl = isDocker ? 'http://backend:8000' : 'http://localhost:8001',
        dbHost = isDocker ? 'mysql' : '127.0.0.1',
        dbPort = isDocker ? '3306' : '3307',
        dbDatabase = 'playrke',
        dbUsername = isDocker ? 'playrke' : 'root',
        dbPassword = isDocker ? 'playrke' : '',
        redisHost = isDocker ? 'redis' : '127.0.0.1',
        redisPort = isDocker ? '6379' : '6380',
        useExisting = !flags.force,
    } = options;

    // Generar claves criptográficamente seguras
    log.info('Generando claves de encriptación...');
    const appKey = generateLaravelKey();
    const jsonEncryptionKey = generateBase64Key();
    const ctmerEncryptionKey = generateBase64Key();
    const reverbAppId = generateAppId();
    const reverbAppKey = generateAlphanumericKey(32);
    const reverbAppSecret = generateAlphanumericKey(32);

    const envContent = `# ==========================================
# PlayRKE Backend Configuration
# Generated: ${new Date().toISOString()}
# Environment: ${appEnv} ${isDocker ? '(Docker)' : '(Local)'}
# ==========================================

# Application
APP_NAME="${appName}"
APP_ENV=${appEnv}
APP_KEY=${appKey}
APP_DEBUG=${isProduction ? 'false' : 'true'}
APP_TIMEZONE=America/Caracas
APP_URL=${appUrl}
ASSET_URL=${appUrl}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=${isProduction ? '12' : '10'}

# Logging
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${isProduction ? 'error' : 'debug'}

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=${dbHost}
DB_PORT=${dbPort}
DB_DATABASE=${dbDatabase}
DB_USERNAME=${dbUsername}
DB_PASSWORD=${dbPassword}

# Session
SESSION_DRIVER=${isDocker ? 'redis' : 'database'}
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Broadcasting & Queue
BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${isDocker ? 'redis' : 'sync'}

# Cache
CACHE_STORE=${isDocker ? 'redis' : 'database'}
CACHE_PREFIX=

# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=${redisHost}
REDIS_PASSWORD=null
REDIS_PORT=${redisPort}

# Mail Configuration
MAIL_MAILER=${isProduction ? 'smtp' : 'log'}
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

# AWS S3 (Optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Vite Asset Building
VITE_APP_NAME="\${APP_NAME}"

# Laravel Reverb (WebSockets)
REVERB_APP_ID=${reverbAppId}
REVERB_APP_KEY=${reverbAppKey}
REVERB_APP_SECRET=${reverbAppSecret}
REVERB_HOST="${isDocker ? '0.0.0.0' : 'localhost'}"
REVERB_PORT=8080
REVERB_SCHEME=${isProduction ? 'https' : 'http'}

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="${isDocker ? 'localhost' : '\${REVERB_HOST}'}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"

# Custom Encryption Keys
JSON_ENCRYPTION_KEY=${jsonEncryptionKey}
CTMER_ENCRYPTION_KEY=${ctmerEncryptionKey}
`;

    const backendEnvPath = join(rootDir, 'backend', '.env');

    if (useExisting && existsSync(backendEnvPath)) {
        log.warning('El archivo backend/.env ya existe, respaldando como .env.backup');
        const backupPath = join(rootDir, 'backend', '.env.backup');
        const existingContent = readFileSync(backendEnvPath, 'utf-8');
        writeFileSync(backupPath, existingContent);
    }

    writeFileSync(backendEnvPath, envContent);
    log.success('Archivo backend/.env generado correctamente');

    return {
        reverbAppId,
        reverbAppKey,
        reverbAppSecret,
        reverbHost: isDocker ? '0.0.0.0' : 'localhost',
        reverbPort: 8080,
        reverbScheme: isProduction ? 'https' : 'http',
    };
}

/**
 * Genera el archivo .env para el frontend (Nuxt)
 */
function generateFrontendEnv(reverbConfig, options = {}) {
    const isDocker = flags.docker;
    const isProduction = flags.production;

    const {
        apiBase = isDocker ? 'http://localhost:8001/api' : 'http://localhost:8001/api',
        appUrl = isDocker ? 'http://localhost:3002' : 'http://localhost:3002',
        wsUrl = isDocker ? 'ws://localhost:8082' : 'ws://localhost:8082',
        useExisting = !flags.force,
    } = options;

    const envContent = `# ==========================================
# PlayRKE Frontend Configuration
# Generated: ${new Date().toISOString()}
# Environment: ${isProduction ? 'production' : 'development'} ${isDocker ? '(Docker)' : '(Local)'}
# ==========================================

# API Configuration
NUXT_PUBLIC_API_BASE=${apiBase}

# WebSocket Configuration (Laravel Reverb)
NUXT_PUBLIC_WS_URL=${wsUrl}
NUXT_PUBLIC_PUSHER_KEY=${reverbConfig.reverbAppKey}
NUXT_PUBLIC_PUSHER_HOST=${isDocker ? 'localhost' : reverbConfig.reverbHost}
NUXT_PUBLIC_PUSHER_PORT=${isDocker ? '8082' : reverbConfig.reverbPort}
NUXT_PUBLIC_PUSHER_CLUSTER=mt1
NUXT_PUBLIC_PUSHER_TLS=${isProduction ? 'true' : 'false'}

# App Configuration
NUXT_PUBLIC_APP_NAME=PlayRKE Bingo
NUXT_PUBLIC_APP_URL=${appUrl}
NODE_ENV=${isProduction ? 'production' : 'development'}
`;

    const frontendEnvPath = join(rootDir, 'frontend', '.env');

    if (useExisting && existsSync(frontendEnvPath)) {
        log.warning('El archivo frontend/.env ya existe, respaldando como .env.backup');
        const backupPath = join(rootDir, 'frontend', '.env.backup');
        const existingContent = readFileSync(frontendEnvPath, 'utf-8');
        writeFileSync(backupPath, existingContent);
    }

    writeFileSync(frontendEnvPath, envContent);
    log.success('Archivo frontend/.env generado correctamente');
}

/**
 * Genera archivo .env.example para el backend
 */
function generateBackendEnvExample() {
    const examplePath = join(rootDir, 'backend', '.env.example');
    if (existsSync(examplePath)) {
        log.info('backend/.env.example ya existe, omitiendo...');
        return;
    }

    const envPath = join(rootDir, 'backend', '.env');
    if (existsSync(envPath)) {
        const content = readFileSync(envPath, 'utf-8')
            .replace(/APP_KEY=.+/, 'APP_KEY=')
            .replace(/JSON_ENCRYPTION_KEY=.+/, 'JSON_ENCRYPTION_KEY=')
            .replace(/CTMER_ENCRYPTION_KEY=.+/, 'CTMER_ENCRYPTION_KEY=')
            .replace(/REVERB_APP_ID=.+/, 'REVERB_APP_ID=')
            .replace(/REVERB_APP_KEY=.+/, 'REVERB_APP_KEY=')
            .replace(/REVERB_APP_SECRET=.+/, 'REVERB_APP_SECRET=')
            .replace(/DB_PASSWORD=.+/, 'DB_PASSWORD=');

        writeFileSync(examplePath, content);
        log.success('Archivo backend/.env.example generado');
    }
}

/**
 * Genera archivo .env.example para el frontend
 */
function generateFrontendEnvExample() {
    const examplePath = join(rootDir, 'frontend', '.env.example');
    if (existsSync(examplePath)) {
        log.info('frontend/.env.example ya existe, omitiendo...');
        return;
    }

    const envPath = join(rootDir, 'frontend', '.env');
    if (existsSync(envPath)) {
        const content = readFileSync(envPath, 'utf-8')
            .replace(/NUXT_PUBLIC_PUSHER_KEY=.+/, 'NUXT_PUBLIC_PUSHER_KEY=');

        writeFileSync(examplePath, content);
        log.success('Archivo frontend/.env.example generado');
    }
}

// ============================================
// Función Principal
// ============================================

function main() {
    log.title('🔐 PlayRKE - Generador de Secretos y Variables de Entorno v2.0');

    // Mostrar ayuda si se solicita
    if (flags.help) {
        showHelp();
    }

    // Mostrar configuración
    log.info(`Modo: ${flags.production ? 'Producción' : 'Desarrollo'}`);
    log.info(`Entorno: ${flags.docker ? 'Docker' : 'Local'}`);
    log.info(`Forzar sobrescritura: ${flags.force ? 'Sí' : 'No'}\n`);

    try {
        // Validar estructura del proyecto
        log.info('Validando estructura del proyecto...');
        validateProjectStructure();
        log.success('Estructura del proyecto válida\n');

        // Generar .env del backend
        log.info('Generando backend/.env...');
        const reverbConfig = generateBackendEnv();

        // Generar .env raíz para Docker Compose
        if (flags.docker) {
            log.info('Generando .env (raíz) para Docker Compose...');
            generateRootEnv(reverbConfig);
        }

        // Generar .env del frontend
        log.info('Generando frontend/.env...');
        generateFrontendEnv(reverbConfig);

        // Generar archivos .env.example si no existen
        if (!flags.force) {
            log.info('Generando archivos .env.example...');
            generateBackendEnvExample();
            generateFrontendEnvExample();
        }

        log.success('\n✅ Todos los archivos .env fueron generados correctamente!\n');

        // Mostrar próximos pasos según el entorno
        console.log(`${colors.bright}Próximos pasos:${colors.reset}`);

        if (flags.docker) {
            console.log('  1. Revisa los archivos .env generados');
            console.log('  2. Inicia Docker: docker-compose up -d');
            console.log('  3. Espera a que los servicios estén listos (30s)');
            console.log('  4. Ejecuta migraciones: docker-compose exec backend php artisan migrate --seed');
            console.log('  5. Accede a: http://localhost:3002\n');
        } else {
            console.log('  1. Revisa los archivos .env generados');
            console.log('  2. Configura tu base de datos MySQL local');
            console.log('  3. Ejecuta: cd backend && php artisan migrate --seed');
            console.log('  4. Inicia los servicios: pnpm run dev');
            console.log('  5. Accede a: http://localhost:3002\n');
        }

        // Mostrar advertencias de seguridad en producción
        if (flags.production) {
            log.warning('\n⚠️  PRODUCCIÓN: Asegúrate de:');
            console.log('  - Revisar todas las credenciales de base de datos');
            console.log('  - Configurar HTTPS y certificados SSL');
            console.log('  - Ajustar configuración de mail para SMTP real');
            console.log('  - Configurar backups automáticos');
            console.log('  - Revisar logs y monitoreo\n');
        }

    } catch (error) {
        log.error(`\n❌ Error al generar archivos: ${error.message}`);
        if (error.stack && !flags.production) {
            console.log(`\n${colors.red}Stack trace:${colors.reset}`);
            console.log(error.stack);
        }
        process.exit(1);
    }
}

// Ejecutar si se llama directamente
// Normalizar rutas para Windows y Unix
const scriptPath = fileURLToPath(import.meta.url);
const executedPath = process.argv[1];
if (scriptPath === executedPath || scriptPath.replace(/\\/g, '/') === executedPath.replace(/\\/g, '/')) {
    main();
}

export {
    generateBackendEnv,
    generateFrontendEnv,
    generateBase64Key,
    generateHexKey,
    generateAlphanumericKey,
};
