# Dominio Bingo

## Propósito

Este dominio contiene las reglas y servicios del juego Bingo: salas, cartones, rondas, sorteos, patrones, participación, pagos, premios y operaciones relacionadas.

## Ubicación y organización

El código está bajo `backend/app/Domains/Games/Bingo/`. La estructura real se organiza por responsabilidad e incluye:
- `Models/`: modelos del dominio;
- `Services/`: reglas de negocio y casos de uso;
- `Repositories/`: acceso a datos;
- `Contracts/`: contratos;
- `DTOs/`: transferencia de datos;
- `Events/`: eventos;
- `Jobs/`: trabajo asíncrono;
- `Policies/`: autorización por recurso;
- `StateMachine/` y `ValueObjects/`: estados y conceptos del dominio.

Los controladores HTTP se encuentran fuera del dominio, en `backend/app/Http/Controllers/Games/Bingo/`; las rutas se cargan desde `backend/routes/modules/games/bingo.php` y el índice principal `backend/routes/web.php`.

## Flujo funcional

1. Se crea/configura una sala y sus rondas.
2. Los participantes se incorporan y compran o reciben cartones según su modalidad.
3. Los pagos y lotes determinan la elegibilidad de los cartones.
4. El sorteo emite números y eventos de actualización en tiempo real.
5. La verificación de patrones determina ganadores dentro de la ronda correspondiente.
6. Los premios se procesan con los servicios financieros compartidos y jobs; los estados retenidos/disputados necesitan conciliación.
7. Reembolsos y recuperaciones se procesan mediante colas específicas.

## Invariantes críticas

- La elegibilidad depende del lote y de los pagos asociados, no solo del estado visible del cartón.
- El sorteo y la reclamación deben quedar aislados por ronda.
- La autorización de una sala requiere permiso y Policy/validación de pertenencia donde corresponda.
- El beneficiario del premio se resuelve desde la participación/pago del cartón, no desde el vendedor que registró la venta.
- Los reintentos deben ser idempotentes; los estados ambiguos pasan a revisión manual.
- La UI no sustituye controles del backend.

## Documentación relacionada

- [Arquitectura general](../../../../../docs/ARCHITECTURE.md)
- [Autenticación específica de Bingo](../../../../docs/security/BINGO_AUTHENTICATION.md)
- [Pagos y premios](../../../../../docs/PAYMENTS_AND_PRIZES.md)
- [Preparación para lanzamiento](../../../../../docs/RELEASE_READINESS.md)

Este documento reemplaza el antiguo checklist de migración del dominio, que describía como pendientes directorios y capas que ya existen en la estructura actual.