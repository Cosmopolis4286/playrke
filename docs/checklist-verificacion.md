# ✅ Checklist de Verificación - PlayRKE Bingo Frontend

Este checklist te ayudará a verificar que todo el sistema frontend está correctamente instalado y funcionando.

---

## 📋 Pre-Instalación

### Sistema

- [ ] Node.js v18.0.0 o superior instalado
  ```bash
  node --version
  ```
- [ ] npm v8.0.0 o superior instalado
  ```bash
  npm --version
  ```
- [ ] Git instalado (opcional pero recomendado)
  ```bash
  git --version
  ```

### Backend Laravel

- [ ] Backend Laravel instalado y funcionando
- [ ] Backend corriendo en `http://localhost:8000`
  ```bash
  php artisan serve
  ```
- [ ] Base de datos configurada y migrada
  ```bash
  php artisan migrate
  ```
- [ ] Laravel Reverb instalado (para WebSockets)
  ```bash
  php artisan reverb:start
  ```

---

## 📦 Instalación

### Dependencias

- [ ] Navegado al directorio frontend
  ```bash
  cd c:\laragon\www\playrke\frontend
  ```
- [ ] Dependencias instaladas sin errores
  ```bash
  npm install
  ```
- [ ] Verificar que `node_modules` existe
  ```bash
  ls node_modules  # o dir node_modules en Windows
  ```

### Configuración

- [ ] Archivo `.env` creado
  ```bash
  # Opción 1: Automático
  npm run setup

  # Opción 2: Manual
  cp .env.example .env
  ```
- [ ] Variables de entorno configuradas correctamente:
  - [ ] `NUXT_PUBLIC_API_BASE` apunta al backend
  - [ ] `NUXT_PUBLIC_PUSHER_KEY` configurado
  - [ ] `NUXT_PUBLIC_PUSHER_HOST` configurado
  - [ ] `NUXT_PUBLIC_PUSHER_PORT` configurado
  - [ ] `NUXT_PUBLIC_APP_URL` configurado

---

## 🚀 Ejecución

### Servidor de Desarrollo

- [ ] Servidor inicia sin errores
  ```bash
  npm run dev
  ```
- [ ] Mensaje de inicio muestra:
  ```
  Nuxt 3.x.x with Nitro 2.x.x
    > Local:    http://localhost:3000
  ```
- [ ] Navegador abre en `http://localhost:3000`
- [ ] Página de inicio carga correctamente
- [ ] No hay errores en consola del navegador (F12)

---

## 🔌 Conectividad

### Backend API

- [ ] Verificar CORS en Laravel (`config/cors.php`):
  ```php
  'supports_credentials' => true,
  'allowed_origins' => ['http://localhost:3000'],
  ```
- [ ] Verificar Sanctum en Laravel (`config/sanctum.php`):
  ```php
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),
  ```
- [ ] `.env` de Laravel configurado:
  ```
  SESSION_DOMAIN=localhost
  SANCTUM_STATEFUL_DOMAINS=localhost:3000
  ```

### WebSockets

- [ ] Laravel Reverb corriendo en puerto 6001
  ```bash
  php artisan reverb:start
  ```
- [ ] Configuración de Reverb en `.env` de Laravel:
  ```
  BROADCAST_CONNECTION=reverb
  REVERB_APP_ID=local
  REVERB_APP_KEY=local
  REVERB_APP_SECRET=local
  ```
- [ ] Variables de WebSocket en frontend coinciden:
  ```
  NUXT_PUBLIC_PUSHER_KEY=local
  NUXT_PUBLIC_PUSHER_PORT=6001
  ```

---

## 🔐 Autenticación

### Flujo de Login

- [ ] Navegar a `/auth/login` funciona
- [ ] Formulario de login renderiza correctamente
- [ ] Campos de email/username y password visibles
- [ ] Botón "Iniciar Sesión" funciona

### Prueba de Login

**Prerequisito**: Crear usuario de prueba en Laravel

```bash
php artisan tinker
```
```php
$user = \App\Models\User::create([
    'first_name' => 'Test',
    'last_name' => 'Player',
    'username' => 'testplayer',
    'email' => 'test@playrke.com',
    'password' => bcrypt('password'),
]);
$user->assignRole('player');
```

- [ ] Login con credenciales de prueba exitoso
- [ ] Cookie de sesión establecida (F12 → Application → Cookies)
- [ ] Redirección al panel correcto:
  - Admin → `/admin/dashboard`
  - Player → `/player/lobby`
- [ ] Usuario autenticado visible en UI
- [ ] Botón de logout visible

### Verificación de Sesión

- [ ] Refrescar página mantiene sesión
- [ ] Cerrar y abrir navegador mantiene sesión (si remember=true)
- [ ] Logout cierra sesión correctamente
- [ ] Redirige a `/auth/login` después de logout

---

## 🎮 Funcionalidades Player

### Lobby

- [ ] `/player/lobby` accesible (requiere login)
- [ ] Lista de salas renderiza
- [ ] Stats cards visibles (Total, Activas, Jugadores, Mi Balance)
- [ ] Filtros funcionan (moneda, tipo, estado)
- [ ] Buscador funciona
- [ ] Botón "Unirse" funciona

### Sala de Juego

- [ ] `/player/game/[id]` accesible
- [ ] BingoBoard renderiza
- [ ] Botón "Comprar Cartones" funciona
- [ ] Modal de compra se abre
- [ ] GameStats se actualizan
- [ ] Lista de ganadores visible (si hay ganadores)

### Historial

- [ ] `/player/history` accesible
- [ ] Stats cards visibles
- [ ] Tabla de historial renderiza
- [ ] Filtros por fecha funcionan
- [ ] Paginación funciona
- [ ] Modal de detalles se abre al hacer click

### Billetera

- [ ] `/player/wallet` accesible
- [ ] Wallets cards visibles para cada moneda
- [ ] Balance correcto mostrado
- [ ] Botón "Depositar" abre modal
- [ ] Botón "Retirar" abre modal
- [ ] Tabla de transacciones renderiza
- [ ] Filtros funcionan

### Perfil

- [ ] `/player/profile` accesible
- [ ] 3 tabs renderizados (Información, Seguridad, Preferencias)
- [ ] Formulario de información editable
- [ ] Cambio de contraseña funciona
- [ ] Upload de avatar funciona (si implementado en backend)
- [ ] Stats del jugador visibles

---

## 🛡️ Funcionalidades Admin

### Dashboard

- [ ] `/admin/dashboard` accesible (requiere rol admin)
- [ ] Stats cards visibles
- [ ] Gráficas renderizadas
- [ ] Datos actualizados

### Clientes

- [ ] `/admin/customers` accesible
- [ ] Lista de clientes renderiza
- [ ] Botón "Nuevo Cliente" funciona
- [ ] Modal de crear se abre
- [ ] Búsqueda funciona
- [ ] Filtros funcionan
- [ ] Edición funciona
- [ ] Eliminación funciona (con confirmación)

### Tesoreros

- [ ] `/admin/treasurers` accesible
- [ ] CRUD completo funciona
- [ ] Asignación de moneda funciona
- [ ] Comisión editable

### Vendedores

- [ ] `/admin/sellers` accesible
- [ ] CRUD completo funciona
- [ ] Comisión editable

### Salas

- [ ] `/admin/rooms` accesible
- [ ] Lista de salas renderiza
- [ ] Stats cards visibles
- [ ] Crear sala funciona
- [ ] Editar sala funciona
- [ ] Botón "Iniciar Juego" funciona
- [ ] Filtros funcionan

### Transacciones

- [ ] `/admin/transactions` accesible
- [ ] Lista de transacciones renderiza
- [ ] Stats cards visibles
- [ ] Botón "Aprobar" funciona
- [ ] Botón "Rechazar" funciona
- [ ] Modal de detalles muestra info completa
- [ ] Filtros por tipo/estado funcionan

### Reportes

- [ ] `/admin/reports` accesible
- [ ] 4 metric cards visibles con cambios porcentuales
- [ ] Gráfica de ingresos renderiza
- [ ] Gráfica de jugadores renderiza
- [ ] Top 5 jugadores tabla renderiza
- [ ] Top 5 salas tabla renderiza
- [ ] Filtros por período funcionan
- [ ] Botón "Exportar CSV" funciona (si implementado)

### Configuración

- [ ] `/admin/settings` accesible
- [ ] 5 tabs visibles (General, Juego, Pagos, Email, Seguridad)
- [ ] Formularios editables
- [ ] Botón "Guardar Cambios" funciona
- [ ] Notificación de éxito al guardar

---

## 🌐 WebSockets (Tiempo Real)

### Conexión

- [ ] Abrir consola del navegador (F12)
- [ ] Entrar a una sala de juego
- [ ] Verificar mensaje de conexión:
  ```
  Echo connected
  Subscribed to channel: bingo.room.xxx
  ```
- [ ] Sin errores de WebSocket en consola

### Eventos (Requiere juego activo)

- [ ] Evento `NumberCalled` recibido cuando se canta número
- [ ] Número se marca automáticamente en BingoBoard
- [ ] Evento `GameStarted` recibido al iniciar juego
- [ ] Evento `GameEnded` recibido al finalizar
- [ ] Evento `WinnerDeclared` muestra ganador

---

## 🎨 UI/UX

### Diseño Responsive

- [ ] Diseño se adapta en móvil (< 640px)
- [ ] Menú móvil funciona (hamburger menu)
- [ ] Tablas scrollean horizontalmente en móvil
- [ ] Modales se ajustan a pantalla pequeña

### Dark Mode

- [ ] Toggle de tema visible (en admin)
- [ ] Cambio de tema funciona
- [ ] Tema persiste al refrescar
- [ ] Todos los componentes soportan dark mode

### Animaciones

- [ ] Transiciones de modales suaves
- [ ] Transiciones de páginas funcionan
- [ ] Loading spinners visibles durante carga
- [ ] Toast notifications aparecen correctamente

### Accesibilidad

- [ ] Botones tienen labels descriptivos
- [ ] Inputs tienen placeholders
- [ ] Formularios tienen labels
- [ ] Navegación por teclado funciona (Tab)
- [ ] Escape cierra modales

---

## 🔍 Testing de Navegación

### Rutas Públicas

- [ ] `/` - Home page accesible
- [ ] `/auth/login` - Login accesible
- [ ] `/auth/register` - Registro accesible
- [ ] `/auth/forgot-password` - Recuperar contraseña accesible

### Rutas Player (requiere auth)

- [ ] `/player/lobby` - Accesible con auth
- [ ] `/player/game/[id]` - Accesible con auth
- [ ] `/player/history` - Accesible con auth
- [ ] `/player/wallet` - Accesible con auth
- [ ] `/player/profile` - Accesible con auth

### Rutas Admin (requiere auth + rol admin)

- [ ] `/admin/dashboard` - Accesible solo con rol admin
- [ ] `/admin/customers` - Accesible solo con rol admin
- [ ] `/admin/treasurers` - Accesible solo con rol admin
- [ ] `/admin/sellers` - Accesible solo con rol admin
- [ ] `/admin/rooms` - Accesible solo con rol admin
- [ ] `/admin/transactions` - Accesible solo con rol admin
- [ ] `/admin/reports` - Accesible solo con rol admin
- [ ] `/admin/settings` - Accesible solo con rol admin

### Middleware

- [ ] Rutas protegidas redirigen a `/auth/login` si no autenticado
- [ ] Login redirige a lobby si ya autenticado
- [ ] Rutas admin redirigen si no tiene rol admin
- [ ] Redirección por rol funciona correctamente

---

## 🐛 Testing de Errores

### API Errors

- [ ] Error 401 (no autenticado) muestra mensaje apropiado
- [ ] Error 403 (sin permisos) muestra mensaje apropiado
- [ ] Error 404 (no encontrado) muestra mensaje apropiado
- [ ] Error 422 (validación) muestra errores de campo
- [ ] Error 500 (servidor) muestra mensaje genérico

### Network Errors

- [ ] Detener backend y verificar mensaje de error
- [ ] Error de conexión muestra toast/notificación
- [ ] Reintentar conexión funciona

### Validation Errors

- [ ] Email inválido muestra error
- [ ] Password corto muestra error
- [ ] Campos requeridos muestran error
- [ ] Formato de moneda inválido muestra error

---

## 📊 Performance

### Lighthouse (Chrome DevTools)

- [ ] Performance Score > 80
- [ ] Accessibility Score > 90
- [ ] Best Practices Score > 90
- [ ] SEO Score > 80 (opcional)

### Network

- [ ] Tiempo de carga inicial < 3 segundos
- [ ] Peticiones API < 1 segundo
- [ ] Imágenes optimizadas (< 200KB)
- [ ] Sin peticiones bloqueantes

### Bundle Size

- [ ] Verificar tamaño del bundle:
  ```bash
  npm run build
  ```
- [ ] Bundle principal < 500KB (gzipped)
- [ ] Lazy loading de rutas funcionando

---

## 📝 Documentación

- [ ] README.md completo y actualizado
- [ ] installation.md accesible
- [ ] quick-start.md accesible
- [ ] api-endpoints-required.md completo
- [ ] migracion-completada.md actualizado

---

## 🚀 Pre-Producción

### Build

- [ ] Build de producción exitoso:
  ```bash
  npm run build
  ```
- [ ] Sin errores de TypeScript
- [ ] Sin warnings críticos
- [ ] Output generado en `.output/`

### Preview

- [ ] Preview funciona:
  ```bash
  npm run preview
  ```
- [ ] Aplicación funciona en modo producción
- [ ] Performance mejorado vs desarrollo

### Variables de Entorno

- [ ] `.env.example` actualizado con todas las variables
- [ ] Valores de producción preparados
- [ ] Secrets configurados (no commiteados)

---

## ✅ Checklist Final

### Desarrollo

- [ ] Todos los componentes funcionan correctamente
- [ ] Todas las páginas renderizadas sin errores
- [ ] API integration completa
- [ ] WebSockets funcionando
- [ ] State management funcionando
- [ ] Routing correcto
- [ ] Middleware funcionando

### Calidad

- [ ] Sin errores en consola
- [ ] Sin warnings críticos
- [ ] TypeScript sin errores
- [ ] Código formateado (Prettier)
- [ ] Código linteado (ESLint)

### Documentación

- [ ] README completo
- [ ] Guías de instalación actualizadas
- [ ] API endpoints documentados
- [ ] Código comentado donde necesario

### Preparación para Producción

- [ ] Variables de entorno configuradas
- [ ] Build de producción exitoso
- [ ] Performance optimizado
- [ ] Seguridad verificada

---

## 📞 Soporte

Si encuentras algún problema durante esta verificación:

1. Revisa los logs del navegador (F12 → Console)
2. Revisa los logs de Nuxt en la terminal
3. Revisa los logs de Laravel (`storage/logs/laravel.log`)
4. Consulta [installation.md](./installation.md) para troubleshooting
5. Consulta [api-endpoints-required.md](./api-endpoints-required.md) para verificar endpoints

---

## 🎉 ¡Verificación Completada!

Si todos los checks están marcados, el sistema está **listo para desarrollo/producción**.

**Siguiente paso**: Comenzar desarrollo de funcionalidades específicas o deployment a producción.

---

**Última actualización**: 2025-10-05
**Versión del checklist**: 1.0.0
