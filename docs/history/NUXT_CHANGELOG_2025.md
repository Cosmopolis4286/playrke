# Changelog histórico

Este archivo conserva el registro de la primera etapa del frontend separado. **No es un inventario del estado actual** ni un plan de lanzamiento vigente. La entrada `1.0.0` fue escrita el 2025-10-05 y describe una versión inicial de Nuxt 3; desde entonces el repositorio evolucionó: el paquete separado declara Nuxt 4, y la interfaz principal activa está en `backend/resources/js` con Inertia/Vue.

Para el estado actual consulta:
- [README raíz](../../README.md)
- [Arquitectura vigente](../architecture/OVERVIEW.md)
- [Estado y límites de lanzamiento](../guides/RELEASE_READINESS.md)
- [README del frontend Nuxt separado](../frontends/NUXT4.md)

Las rutas/documentos enumerados en las notas históricas pueden haber sido movidos o eliminados. No reconstruir el entorno actual a partir de esta bitácora. Los cambios nuevos deben registrarse con fecha y alcance verificables, preferiblemente en el changelog del componente correspondiente.

---

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-05

### ✨ Agregado - Frontend Nuxt 3

#### Autenticación
- Sistema completo de autenticación con Laravel Sanctum
- Composable `useAuth` con login/register/logout
- Store `authStore` con persistencia en localStorage
- Middleware de protección de rutas (auth, guest, role)
- Redirección automática por roles

#### Panel de Jugador
- Página de lobby con lista de salas filtrable
- Sala de juego con BingoBoard y BingoCards
- Sistema de compra de cartones
- Historial de juegos con filtros y paginación
- Billetera multi-moneda (USD, VES, BRL, USDT)
- Perfil de usuario con 3 tabs (Info, Seguridad, Preferencias)
- Upload de avatar
- Estadísticas del jugador

#### Panel de Admin
- Dashboard con métricas y gráficas
- CRUD completo de clientes con filtros
- CRUD completo de tesoreros
- CRUD completo de vendedores
- Gestión de salas de bingo
- Gestión de transacciones (aprobar/rechazar)
- Reportes con analytics y gráficas
- Configuración del sistema (5 secciones)

#### Componentes UI
- 15+ componentes reutilizables (Button, Modal, Input, Card, etc.)
- 8 variantes de botones
- 7 tamaños de modales
- Sistema de notificaciones toast
- Soporte completo para dark mode
- Animaciones con Vue Transition

#### API Integration
- Composable `useBingoApi` con 10 métodos
- Composable `useAdminApi` con 18 métodos
- Composable base `useApi` con error handling
- Interceptor de requests con CSRF token
- Manejo centralizado de errores

#### WebSockets
- Composable `useWebSocket` con Laravel Echo
- Composable especializado `useBingoWebSocket`
- Soporte para canales públicos, privados y de presencia
- Auto-reconnect en desconexión
- Eventos: NumberCalled, GameStarted, GameEnded, WinnerDeclared

#### State Management
- Store `authStore` - Autenticación
- Store `bingoStore` - Estado del juego
- Store `walletStore` - Billeteras multi-moneda
- Store `uiStore` - UI state (notificaciones, modales, tema)
- Persistencia selectiva en localStorage

#### Utilidades
- `formatters.ts` - 45+ funciones (currency, date, mask, etc.)
- `validators.ts` - 35+ funciones (email, password, forms, etc.)
- `helpers.ts` - 50+ funciones (debounce, clone, array, etc.)

#### Layouts
- Layout `default.vue` - Páginas públicas
- Layout `player.vue` - Panel de jugador
- Layout `admin.vue` - Panel administrativo
- Responsive design con menús móviles

#### Configuración
- Nuxt 3.13.2 configurado
- TypeScript strict mode
- Tailwind CSS con dark mode
- Arquitectura en layers (admin/player/shared)
- Auto-imports de composables y componentes
- ESLint + Prettier configurados

### 📚 Documentación

- `docs/installation.md` - Guía completa de instalación
- `docs/quick-start.md` - Inicio rápido en 5 minutos
- `docs/api-endpoints-required.md` - Documentación completa de API
- `docs/checklist-verificacion.md` - Checklist de verificación
- `docs/migracion-completada.md` - Resumen de migración
- `frontend/README.md` - Documentación del frontend
- `README.md` - Documentación del monorepo
- `CONTRIBUTING.md` - Guía de contribución

### 🛠️ Scripts

- `frontend/setup-env.js` - Script interactivo de configuración
- `frontend/setup-env.bat` - Script para Windows
- `frontend/setup-env.sh` - Script para Linux/Mac
- `npm run setup` - Configurar .env interactivo
- `npm run setup:prod` - Configurar .env para producción

### 🎨 Estándares

- Configuración de ESLint para TypeScript y Vue
- Configuración de Prettier con plugin de Tailwind
- Settings de VSCode para mejor DX
- Extensiones recomendadas de VSCode
- `.gitignore` actualizado para monorepo

### 🔧 Configuración

- Variables de entorno documentadas en `.env.example`
- CORS configurado para frontend
- Sanctum stateful domains configurado
- WebSockets con Laravel Reverb
- Multi-currency wallet system

---

## [0.9.0] - 2025-09-XX

### Backend Laravel (Estado previo a migración frontend)

- Sistema de autenticación con Laravel Sanctum
- API RESTful completa
- WebSockets con Laravel Reverb
- Sistema de roles y permisos
- Gestión de salas de bingo
- Sistema de cartones y juegos
- Billetera multi-moneda
- Transacciones y pagos
- Panel administrativo (Blade views - migradas)

---

## Tipos de Cambios

- `✨ Agregado` - Para nuevas funcionalidades
- `🔄 Cambiado` - Para cambios en funcionalidades existentes
- `⚠️ Deprecado` - Para funcionalidades que serán removidas
- `🗑️ Removido` - Para funcionalidades removidas
- `🐛 Corregido` - Para bug fixes
- `🔒 Seguridad` - Para vulnerabilidades
- `📚 Documentación` - Para cambios en documentación
- `🎨 Estilo` - Para cambios que no afectan funcionalidad
- `♻️ Refactor` - Para refactorización de código
- `⚡ Performance` - Para mejoras de rendimiento
- `✅ Tests` - Para agregar o modificar tests

---

## Versionado

Este proyecto usa [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0) - Cambios incompatibles con versiones anteriores
- **MINOR** (0.X.0) - Nueva funcionalidad compatible con versiones anteriores
- **PATCH** (0.0.X) - Bug fixes compatibles con versiones anteriores

---

## Próximos Releases

### [1.1.0] - Planeado

#### ✨ Agregado
- [ ] Testing suite completo (Vitest + Cypress)
- [ ] i18n - Internacionalización (ES/EN/PT)
- [ ] PWA support
- [ ] Notificaciones push
- [ ] Modo offline básico

#### 🔄 Cambiado
- [ ] Optimización de bundle size
- [ ] Lazy loading de componentes pesados
- [ ] Image optimization

#### 📚 Documentación
- [ ] Guía de deployment
- [ ] Guía de testing
- [ ] API reference completa
- [ ] Storybook para componentes

### [1.2.0] - Futuro

#### ✨ Agregado
- [ ] Sistema de chat en tiempo real
- [ ] Torneos de bingo
- [ ] Logros y badges
- [ ] Estadísticas avanzadas
- [ ] Admin analytics dashboard mejorado

#### ⚡ Performance
- [ ] Redis caching
- [ ] CDN para assets
- [ ] Database query optimization
- [ ] API response caching

---

**Mantenido por**: Equipo PlayRKE
**Última actualización**: 2025-10-05
