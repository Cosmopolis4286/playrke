# Documentación PlayRKE

Toda la documentación propia del proyecto vive aquí. El [README raíz](../README.md) es solo la puerta de entrada. Los documentos incluidos con dependencias o plantillas de terceros se mantienen junto a esas dependencias y no se consideran documentación PlayRKE.

**Regla de precedencia:** el código y la configuración versionados prevalecen sobre los documentos. Los archivos en `history/` y `product/` conservan contexto; no son runbooks ni una declaración del estado actual.

## Guías de trabajo

- [Desarrollo e instalación](guides/DEVELOPMENT.md)
- [Contribución y PR](guides/CONTRIBUTING.md)
- [CI y validaciones](guides/CI.md)
- [Preparación para lanzamiento](guides/RELEASE_READINESS.md)

## Arquitectura y dominios

- [Arquitectura general](architecture/OVERVIEW.md)
- [Mapa de rutas Laravel](architecture/ROUTES.md)
- [Bingo](domains/BINGO.md)
- [Trivia](domains/TRIVIA.md)
- [Arquitectura detallada de Trivia](domains/TRIVIA_ARCHITECTURE.md)
- [Helpers de formularios](frontend/FORM_HELPERS.md)
- [Frontend Nuxt 4 aislado](frontends/NUXT4.md)

## Seguridad y finanzas

- [Autenticación y autorización](security/AUTHENTICATION.md)
- [Autenticación de Bingo](security/BINGO_AUTHENTICATION.md)
- [Jerarquía de roles](security/ROLE_HIERARCHY.md)
- [Visibilidad del menú por rol](security/SIDEBAR_MENU_VISIBILITY.md)
- [Pagos, wallets y premios](payments/PAYMENTS_AND_PRIZES.md)
- [Diseño USDT-TRC20](payments/USDT_TRC20_DESIGN.md)
- [Auditoría Marketplace](payments/MARKETPLACE_ARCHITECTURE_AUDIT.md)

## Operación

- [Despliegue Docker](operations/DEPLOYMENT_DOCKER.md)
- [Laravel IDE Helper](operations/IDE_HELPER.md)
- [Estado del ecosistema](operations/ESTADO_ECOSISTEMA.md)

## Producto e historial

`product/` contiene alcance, estrategia y planes de producto/ingeniería conservados como material de referencia. `history/` contiene changelogs y auditorías fechadas. Antes de ejecutar una tarea descrita allí, verifica si sigue vigente en el código.

- [Alcance de juegos y comercio](product/ALCANCE_JUEGOS_Y_COMERCIO.md)
- [Estrategia de producto](product/ESTRATEGIA_ANALISIS.md) · [plan](product/ESTRATEGIA_PLAN.md)
- [Planes técnicos](product/GAME_ENGINE_PLAN.md), [matchmaking](product/MATCHMAKING_PLAN.md), [XP/logros](product/XP_LOGROS_PLAN.md)
- [Historial de arquitectura](history/CHANGELOG_ARQUITECTURA.md)
- [Changelog backend](history/BACKEND_CHANGELOG.md)
- [Auditoría Trivia 2026-08](history/TRIVIA_AUDIT_2026_08.md)
- [Changelog Nuxt histórico 2025](history/NUXT_CHANGELOG_2025.md)

## Fuentes de verdad

| Área | Fuente |
|---|---|
| Workspace JS | `pnpm-workspace.yaml`, `package.json`, `backend/package.json`, `frontend/package.json` |
| CI | `.github/workflows/ci.yml` |
| Runtime y dependencias PHP | `backend/composer.json`, `backend/config/` |
| Producción | `backend/docker-compose.prod.yml`, `backend/deploy.sh` |
| Variables de entorno | `backend/.env.example` |

No dupliques manuales. Actualiza la guía canónica cuando cambie un contrato y conserva en el historial únicamente decisiones que aporten contexto.
