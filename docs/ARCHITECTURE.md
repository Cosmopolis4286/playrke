# Arquitectura

## Visión general

PlayRKE es una aplicación Laravel monolítica modular. El backend concentra la lógica de dominio y sirve la interfaz Vue mediante Inertia. Laravel Reverb entrega eventos en tiempo real. MySQL es la persistencia principal; Trivia usa una conexión PostgreSQL dedicada y Redis para coordinación/cache/colas.

No describir la aplicación como Laravel + Nuxt: el frontend mantenido está dentro de `backend/resources/js` y se construye con Vite.

## Componentes

- Laravel 11 / PHP: HTTP, sesiones, validación, Policies, jobs, scheduler y comandos.
- Inertia + Vue 3 + TypeScript: SPA integrada en Laravel.
- Vite + pnpm: compilación de assets y herramientas frontend.
- MySQL: datos principales de aplicación.
- PostgreSQL (`pgsql_trivia`): persistencia dedicada a Trivia.
- Redis: cache, colas, locks y estado efímero.
- Reverb / Echo: broadcasting y suscripciones en tiempo real.
- Docker Compose: Traefik, Nginx, PHP-FPM, MySQL, Redis, Reverb, workers y scheduler.

## Organización

`backend/routes/web.php` es el índice de rutas y carga autenticación, módulos de plataforma y módulos de Bingo/Trivia/pagos/wallet. El código se organiza entre `app/Domains`, `app/Http`, `app/Models`, `app/Services` y proveedores de servicio.

La autorización debe permanecer en middleware y Policies del servidor. La interfaz puede ocultar controles, pero nunca constituye el control de acceso.

## Dominios

### Bingo

Gestión de salas y cartones, rondas, sorteos, replay, historial, premios y reportes. La elegibilidad de un cartón debe considerar el estado del lote y de todos sus pagos; no basta el estado visual del cartón. La ronda activa delimita fichas y resultados.

Los compradores externos se identifican por la relación de pago/participación correspondiente, no por el usuario vendedor. Los premios externos retenidos o disputados requieren conciliación y no deben acreditarse al vendedor.

### Trivia

Juego multiusuario con salas, lobby, participantes, preguntas, respuestas, puntuaciones, power-ups y resultados. Sus rutas están en `routes/modules/games/trivia.php`. Usa `pgsql_trivia` y Redis para coordinación/estado.

### Pagos y wallet

Los movimientos deben persistir transaccionalmente, verificar propiedad, impedir doble gasto y mantener trazabilidad. Los procesos asíncronos necesitan idempotencia y estados recuperables; operaciones no resueltas pasan a conciliación.

## Datos y conexiones

| Conexión | Uso |
|---|---|
| `mysql` | Base principal Laravel |
| `pgsql_trivia` | Base PostgreSQL de Trivia |
| `redis` | Cache, colas, locks y estado efímero |

Variables Trivia: `DB_TRIVIA_HOST`, `DB_TRIVIA_PORT`, `DB_TRIVIA_DATABASE`, `DB_TRIVIA_USERNAME`, `DB_TRIVIA_PASSWORD` y `DB_TRIVIA_SCHEMA`.

## Tiempo real

Los canales se declaran en `backend/routes/channels.php`. Hay canales públicos para información de sala/lobby y feed de eventos, canales privados por usuario y administración, y presence channels con autorización. Los canales públicos solo deben transportar datos destinados a todos los clientes; nunca publicar datos personales, financieros o de juego reservados.

## Colas de producción

El Compose productivo separa:
- `queue`: `default,bingo-draw`
- `bingo-refunds`: reembolsos aislados para no quedar bloqueados por sorteos prolongados
- `scheduler`: tareas programadas

No cambiar el nombre de una cola sin actualizar workers, despliegue y pruebas.

## Invariantes

1. La lógica financiera no debe residir únicamente en controladores.
2. La autorización del servidor es obligatoria.
3. Aplicar locks a los recursos concurrentes y mantener un orden consistente.
4. Eventos y jobs no sustituyen el estado persistente.
5. Reintentos seguros requieren idempotencia; los casos no resolubles pasan a conciliación.
6. No inferir beneficiario financiero desde el actor que ejecutó la venta.