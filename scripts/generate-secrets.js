#!/usr/bin/env node

/**
 * PlayRKE - Generador de Secretos y Variables de Entorno
 *
 * Este script genera automáticamente:
 * - Archivos .env para backend y frontend
 * - Claves de encriptación seguras
 * - Credenciales de WebSockets
 * - Variables de configuración
 */

import { randomBytes } from 'crypto';
import { writeFileSync, existsSync, readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const rootDir = join(__dirname, '..');

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
// Funciones de Generación de Secretos
// ============================================

/**
 * Genera una clave base64 de 32 bytes
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
 * Genera una clave alfanumérica aleatoria
 */
function generateAlphanumericKey(length = 20) {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Genera una Laravel APP_KEY compatible
 */
function generateLaravelKey() {
    try {
        // Intenta usar artisan key:generate
        const backendPath = join(rootDir, 'backend');
        const key = execSync('php artisan key:generate --show', {
            cwd: backendPath,
            encoding: 'utf-8'
        }).trim();
        return key;
    } catch (error) {
        // Fallback: genera manualmente
        log.warning('No se pudo usar artisan, generando clave manualmente');
        return `base64:${generateBase64Key()}`;
    }
}

// ============================================
// Generadores de .env
// ============================================

/**
 * Genera el archivo .env para el backend (Laravel)
 */
function generateBackendEnv(options = {}) {
    const {
        appName = 'PlayRKE',
        appEnv = 'local',
        appUrl = 'http://localhost:8000',
        dbDatabase = 'playrke',
        dbUsername = 'root',
        dbPassword = '',
        useExisting = true,
    } = options;

    // Generar claves
    const appKey = generateLaravelKey();
    const jsonEncryptionKey = generateBase64Key();
    const ctmerEncryptionKey = generateBase64Key();
    const reverbAppId = generateAppId();
    const reverbAppKey = generateAlphanumericKey();
    const reverbAppSecret = generateAlphanumericKey();

    const envContent = `APP_NAME="${appName}"
APP_ENV=${appEnv}
APP_KEY=${appKey}
APP_DEBUG=true
APP_TIMEZONE=America/Caracas
APP_URL=${appUrl}
ASSET_URL=${appUrl}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${dbDatabase}
DB_USERNAME=${dbUsername}
DB_PASSWORD=${dbPassword}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"

REVERB_APP_ID=${reverbAppId}
REVERB_APP_KEY=${reverbAppKey}
REVERB_APP_SECRET=${reverbAppSecret}
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="\${REVERB_HOST}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"

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
        reverbAppKey,
        reverbHost: 'localhost',
        reverbPort: 8080,
        reverbScheme: 'http',
    };
}

/**
 * Genera el archivo .env para el frontend (Nuxt)
 */
function generateFrontendEnv(reverbConfig, options = {}) {
    const {
        apiBase = 'http://localhost:8000/api',
        appUrl = 'http://localhost:3000',
        useExisting = true,
    } = options;

    const envContent = `# PlayRKE Bingo - Frontend Environment Variables
# Configuración de desarrollo local

# API Configuration
NUXT_PUBLIC_API_BASE=${apiBase}

# WebSocket Configuration (Laravel Reverb)
NUXT_PUBLIC_PUSHER_KEY=${reverbConfig.reverbAppKey}
NUXT_PUBLIC_PUSHER_HOST=${reverbConfig.reverbHost}
NUXT_PUBLIC_PUSHER_PORT=${reverbConfig.reverbPort}
NUXT_PUBLIC_PUSHER_CLUSTER=mt1
NUXT_PUBLIC_PUSHER_TLS=false

# App Configuration
NUXT_PUBLIC_APP_NAME=PlayRKE Bingo
NUXT_PUBLIC_APP_URL=${appUrl}
NODE_ENV=development
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
    log.title('🔐 PlayRKE - Generador de Secretos y Variables de Entorno');

    log.info('Iniciando generación de archivos .env...');

    try {
        // Generar .env del backend
        log.info('Generando backend/.env...');
        const reverbConfig = generateBackendEnv({
            appName: 'PlayRKE',
            appEnv: 'local',
            appUrl: 'http://localhost:8000',
            dbDatabase: 'playrke',
            dbUsername: 'root',
            dbPassword: '',
            useExisting: true,
        });

        // Generar .env del frontend
        log.info('Generando frontend/.env...');
        generateFrontendEnv(reverbConfig, {
            apiBase: 'http://localhost:8000/api',
            appUrl: 'http://localhost:3000',
            useExisting: true,
        });

        // Generar archivos .env.example
        log.info('Generando archivos .env.example...');
        generateBackendEnvExample();
        generateFrontendEnvExample();

        log.success('\n✅ Todos los archivos .env fueron generados correctamente!\n');

        console.log(`${colors.bright}Próximos pasos:${colors.reset}`);
        console.log('  1. Revisa los archivos .env generados');
        console.log('  2. Configura tu base de datos MySQL');
        console.log('  3. Ejecuta: cd backend && php artisan migrate');
        console.log('  4. Inicia los servicios con: pnpm run dev\n');

    } catch (error) {
        log.error(`Error al generar archivos: ${error.message}`);
        process.exit(1);
    }
}

// Ejecutar si se llama directamente
if (import.meta.url === `file://${process.argv[1]}`) {
    main();
}

export {
    generateBackendEnv,
    generateFrontendEnv,
    generateBase64Key,
    generateHexKey,
    generateAlphanumericKey,
};
