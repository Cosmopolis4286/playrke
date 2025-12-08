# ============================================
# Base con Node.js + pnpm
# ============================================
FROM node:20-alpine AS base

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@10.18.1 --activate

ENV HOST=0.0.0.0
ENV PORT=3000

# ============================================
# Copy Workspace (para PNPM Workspaces)
# ============================================
FROM base AS workspace

# Copiar archivos necesarios del monorepo
COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./

# Copiar package.json del frontend
COPY frontend/package.json ./frontend/package.json

# ============================================
# Dependencias
# ============================================
FROM workspace AS dependencies

WORKDIR /app/frontend

# Instalar SOLO dependencias del frontend dentro del workspace
RUN pnpm install --frozen-lockfile --filter ./frontend...

# ============================================
# Desarrollo
# ============================================
FROM base AS development

WORKDIR /app/frontend

COPY --from=dependencies /app/frontend/node_modules ./node_modules
COPY frontend ./

EXPOSE 3000
ENV NODE_ENV=development

CMD ["pnpm", "dev", "--host"]

# ============================================
# Builder (Nuxt 4)
# ============================================
FROM base AS builder

WORKDIR /app/frontend

COPY --from=dependencies /app/frontend/node_modules ./node_modules
COPY frontend ./

ENV NODE_ENV=production

RUN pnpm build

# ============================================
# Producción
# ============================================
FROM base AS production

WORKDIR /app/frontend

ENV NODE_ENV=production

# Copiar artefactos
COPY --from=builder /app/frontend/.output .output

# Copiar package.json para poder ejecutar en producción
COPY frontend/package.json ./

# Crear usuario seguro
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nuxt -u 1001

USER nuxt

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
