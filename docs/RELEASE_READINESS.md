# Preparación para una primera versión estable

**Corte documental:** 6 de noviembre de 2026. Este checklist resume riesgos y controles a partir de la configuración y cambios integrados conocidos. No es certificación de seguridad ni reemplaza una ejecución completa de CI y staging.

## Componentes presentes

- Aplicación Laravel con interfaz Inertia/Vue.
- Dominios Bingo y Trivia; persistencia MySQL y PostgreSQL/Redis para Trivia.
- Autenticación por sesión web, roles/permisos y Policies.
- Reverb para eventos en tiempo real.
- GitHub Actions para PHPUnit, build/tests/typecheck, lint y build Docker condicional.
- Stack productivo con Traefik, Nginx, PHP-FPM, MySQL, Redis, Reverb, workers y scheduler.

## Controles incorporados

- Login regenera sesión y rutas guest tienen throttling.
- Rutas de Trivia aplican roles y verificación de email según acción.
- Elegibilidad de Bingo considera pagos/lotes y aislamiento de ronda.
- Titularidad de premios externos se separa del vendedor; casos no verificables se retienen/disputan.
- Reintentos de premios y reembolsos tienen procesamiento/worker dedicado e idempotencia como requisito.
- Retiros y acreditaciones incorporan controles de propiedad, locks y revalidación transaccional en correcciones integradas.
- CI comprueba suites y construye imágenes cuando hay cambios relevantes.

Estos puntos describen implementación/intención; una PR integrada no prueba por sí sola el comportamiento productivo.

## Bloqueantes antes de procesar dinero real

- [ ] Ejecutar todos los checks de CI sobre el commit candidato y conservar resultados verdes.
- [ ] Ejecutar pruebas integrales en staging con MySQL, PostgreSQL y Redis equivalentes a producción.
- [ ] Probar concurrencia real: compras, retiros, premios y reintentos sobre el mismo recurso.
- [ ] Validar conciliación de depósitos, pagos, retiros, reembolsos y webhooks.
- [ ] Definir procedimiento de revisión/entrega de premios externos `suspended` y `disputed`.
- [ ] Probar recuperación ante caída/reinicio de MySQL, Redis, Reverb y workers.
- [ ] Verificar consumo y monitoreo de `default,bingo-draw` y `bingo-refunds`.
- [ ] Verificar scheduler, alertas, logs, retención y trazabilidad.
- [ ] Probar restauración de backups de MySQL y archivos persistentes.
- [ ] Revisar CORS, cookies seguras, CSP en modo enforce, Turnstile y secretos.
- [ ] Completar revisión legal/regulatoria y antifraude del modelo de entradas/premios.
- [ ] Ejecutar prueba de carga para salas, conexiones WebSocket y colas.
- [ ] Verificar accesibilidad y flujo completo desde registro hasta resultado/retiro.

## Riesgos que no deben ocultarse

1. Premio retenido o disputado no equivale a pago efectuado.
2. Alerta de devolución a comprador externo no confirma entrega física del efectivo.
3. Build Docker verde no equivale a smoke test de despliegue.
4. Tests con driver sync o SQLite no sustituyen pruebas de concurrencia con infraestructura equivalente.
5. Configurar un proveedor blockchain no prueba conciliación ni disponibilidad de fondos.
6. El código no sustituye controles legales, contables, custodia ni soporte.

## Criterio de salida

Declarar una versión estable solo cuando el commit exacto haya pasado CI, staging integral, pruebas de recuperación/backups, revisión de seguridad y revisión legal/operativa. Registrar commit, fecha, evidencias y excepciones aceptadas.