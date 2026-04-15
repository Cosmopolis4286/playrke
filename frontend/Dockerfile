# ============================================
# Dockerfile Multi-Stage optimizado para Nuxt 4
# ============================================

# Stage 1: Base con Node.js
FROM node:20-alpine AS base

# Instalar pnpm versión fija
RUN corepack enable && corepack prepare pnpm@10.25.0 --activate

# Directorio de trabajo común
WORKDIR /app

# Variable por defecto para desarrollo
ENV NODE_ENV=development

# ============================================
# Stage 2: Dependencias (cacheable)
# ============================================
FROM base AS dependencies

# Copiar package.json y lockfile si existe
COPY package.json ./
COPY pnpm-lock.yaml* ./

# Instalar dependencias sin frozen-lockfile (monorepo)
RUN pnpm install

# ============================================
# Stage 3: Desarrollo
# ============================================
FROM base AS development

# Copiar node_modules desde stage dependencies para acelerar arranque
COPY --from=dependencies /app/node_modules ./node_modules

# Crear carpetas necesarias para Nuxt
RUN mkdir -p node_modules .nuxt .output

# Exponer puerto 3000 (puerto por defecto de Nuxt)
EXPOSE 3000

# Variables entorno para desarrollo
ENV HOST=0.0.0.0
ENV PORT=3000

# Comando para levantar servidor en modo desarrollo
CMD ["pnpm", "run", "dev"]

# ============================================
# Stage 4: Builder (compilación)
# ============================================
FROM base AS builder

# Copiar dependencias instaladas
COPY --from=dependencies /app/node_modules ./node_modules

# Copiar todo el código fuente
COPY . .

# Configurar entorno producción para build
ENV NODE_ENV=production

# Ejecutar build
RUN pnpm run build

# ============================================
# Stage 5: Producción
# ============================================
FROM node:20-alpine AS production

# Directorio de trabajo
WORKDIR /app

# Variables entorno producción y deshabilitar telemetría Nuxt
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000
ENV NUXT_TELEMETRY_DISABLED=1

# Copiar solo artefactos necesarios para producción
COPY --from=builder /app/.output /app/.output
COPY --from=builder /app/package.json /app/package.json

# Crear usuario no-root para seguridad
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nuxt -u 1001 && \
    chown -R nuxt:nodejs /app /app/.output

# Usar usuario no-root
USER nuxt

# Exponer puerto 3000 para producción
EXPOSE 3000

# Comando para iniciar servidor Nuxt en producción
CMD ["node", ".output/server/index.mjs"]
