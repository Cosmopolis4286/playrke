# ============================================
# Dockerfile Multi-Stage para Nuxt 3 Frontend
# ============================================

# Stage 1: Base con Node.js
FROM node:20-alpine AS base

# Instalar pnpm
RUN corepack enable && corepack prepare pnpm@10.18.1 --activate

# Configurar directorio de trabajo
WORKDIR /app

# Configurar variables de entorno de Node
ENV NODE_ENV=development

# ============================================
# Stage 2: Dependencias (para cache)
# ============================================
FROM base AS dependencies

# Copiar archivos de configuración de dependencias
COPY package.json pnpm-lock.yaml ./

# Instalar dependencias con frozen-lockfile para reproducibilidad
RUN pnpm install --frozen-lockfile

# ============================================
# Stage 3: Desarrollo
# ============================================
FROM base AS development

# Copiar dependencias desde stage anterior
COPY --from=dependencies /app/node_modules ./node_modules

# Copiar el código de la aplicación
COPY . .

# Exponer puerto de Nuxt (3000 por defecto)
EXPOSE 3000

# Variables de entorno para desarrollo
ENV HOST=0.0.0.0
ENV PORT=3000

# Comando de desarrollo con hot reload
CMD ["pnpm", "run", "dev"]

# ============================================
# Stage 4: Builder (compilación)
# ============================================
FROM base AS builder

# Copiar dependencias
COPY --from=dependencies /app/node_modules ./node_modules

# Copiar código fuente
COPY . .

# Configurar para producción
ENV NODE_ENV=production

# Build de la aplicación
RUN pnpm run build

# ============================================
# Stage 5: Producción
# ============================================
FROM base AS production

# Cambiar a producción
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000

# Copiar solo lo necesario para ejecutar
COPY --from=builder /app/.output /app/.output
COPY --from=builder /app/package.json /app/package.json

# Crear usuario no-root para seguridad
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nuxt -u 1001

# Cambiar ownership
RUN chown -R nuxt:nodejs /app

# Usar usuario no-root
USER nuxt

# Exponer puerto
EXPOSE 3000

# Comando de inicio para producción
CMD ["node", ".output/server/index.mjs"]
