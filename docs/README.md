# Documentación PlayRKE

La implementación y configuración versionadas prevalecen sobre cualquier documento. Esta carpeta contiene la documentación técnica mantenida; los informes de auditoría y planes históricos son evidencia temporal, no especificaciones vigentes.

| Documento | Contenido |
|---|---|
| [Arquitectura](ARCHITECTURE.md) | Stack real, módulos, persistencia, rutas y eventos |
| [Desarrollo](DEVELOPMENT.md) | Instalación local, entorno, comandos y pruebas |
| [Autenticación](AUTHENTICATION.md) | Login, sesiones, CSRF, guards y autorización |
| [Pagos y premios](PAYMENTS_AND_PRIZES.md) | Wallet, pagos, premios, reintentos y conciliación |
| [CI](CI.md) | GitHub Actions, jobs y criterios de aceptación |
| [Preparación de lanzamiento](RELEASE_READINESS.md) | Límites conocidos y controles previos a producción |

Para producción, las fuentes de verdad son `backend/docker-compose.prod.yml`, `backend/deploy.sh` y las variables de `backend/.env.example` (sin copiar secretos). Mantén un único documento canónico por tema y enlázalo desde este índice.