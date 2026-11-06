# Documentación PlayRKE

La implementación y configuración versionadas prevalecen sobre cualquier documento. Esta carpeta contiene guías mantenidas; los changelogs, auditorías y planes conservan contexto histórico y deben leerse con su fecha/estado.

## Guías mantenidas

| Documento | Contenido |
|---|---|
| [Arquitectura](ARCHITECTURE.md) | Aplicaciones, stack, módulos, persistencia y eventos |
| [Desarrollo](DEVELOPMENT.md) | Instalación local, entorno, comandos y pruebas |
| [Autenticación](AUTHENTICATION.md) | Login, sesiones, CSRF, guards y autorización |
| [Pagos y premios](PAYMENTS_AND_PRIZES.md) | Wallet, pagos, premios, reintentos y conciliación |
| [CI](CI.md) | GitHub Actions, jobs y criterios de aceptación |
| [Preparación de lanzamiento](RELEASE_READINESS.md) | Riesgos y controles previos a producción |
| [Contribución](CONTRIBUTING.md) | Flujo de trabajo y criterios de PR |

## Documentación de dominios y operaciones

- [Despliegue Docker](../backend/README-DOCKER.md)
- [Dominio Bingo](../backend/app/Domains/Games/Bingo/README.md)
- [Dominio Trivia](../backend/app/Domains/Games/Trivia/README.md)
- [Arquitectura detallada de Trivia](../backend/app/Domains/Games/Trivia/ARCHITECTURE_TRIVIA.md)
- [Autenticación específica de Bingo](../backend/docs/security/BINGO_AUTHENTICATION.md)
- [Jerarquía de roles](../backend/docs/ROLE_HIERARCHY.md)
- [Visibilidad del menú por rol](../backend/docs/SIDEBAR_MENU_VISIBILITY.md)
- [Rutas Laravel](../backend/routes/README.md)

## Decisiones, auditorías y planes conservados

Estos documentos son referencias fechadas; no son automáticamente estado actual ni instrucciones de despliegue:

- [Alcance funcional de Bingo, Trivia y comercio](../ALCANCE_JUEGOS_Y_COMERCIO.md)
- [Estado del ecosistema](../ESTADO_ECOSISTEMA.md)
- [Changelog de arquitectura](../CHANGELOG_ARQUITECTURA.md)
- [Auditoría de arquitectura Marketplace](../MARKETPLACE_ARCHITECTURE_AUDIT.md)
- [Diseño USDT-TRC20](../PAYMENTS_USDT_TRC20_DESIGN.md)
- [Estrategia de producto: análisis](../progreso/estrategia-producto-analisis.md)
- [Estrategia de producto: plan](../progreso/estrategia-producto-plan.md)
- [Planes de Game Engine, matchmaking y XP/logros](../progreso/)

## Fuente de verdad

- Stack y frontend: `package.json`, `pnpm-workspace.yaml`, `backend/package.json`, `frontend/package.json`.
- CI: `.github/workflows/ci.yml`.
- Producción: `backend/docker-compose.prod.yml`, `backend/deploy.sh`.
- Variables: `backend/.env.example` y documentación de despliegue, sin copiar secretos.

Evita mantener manuales duplicados. Cuando cambie un contrato, actualiza la guía canónica y enlaza las notas históricas si aportan contexto.
