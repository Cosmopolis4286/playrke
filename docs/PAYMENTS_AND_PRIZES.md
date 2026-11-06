# Pagos, wallets y premios

Este documento define invariantes y límites operativos. No sustituye una auditoría de código ni certifica que el sistema esté listo para procesar fondos reales.

## Reglas financieras

1. **Autorización:** operar solo sobre wallets y destinos propios, salvo permisos explícitos.
2. **Atomicidad:** saldo, movimiento y estado se actualizan en una transacción coherente.
3. **Concurrencia:** adquirir locks en filas relevantes y volver a leer saldo/estado después de obtenerlos.
4. **Idempotencia:** reintentos, webhooks y jobs no deben acreditar/debitar dos veces.
5. **Trazabilidad:** conservar actor, beneficiario, referencia externa, estado, importes y auditoría.
6. **Conciliación:** operaciones ambiguas o agotadas no se marcan como pagadas; quedan visibles para revisión.

## Wallet y retiros

Validar que el destino exista, esté activo y pertenezca al dueño de la wallet. Serializar solicitudes concurrentes por wallet y revalidar límites/saldo dentro de la sección crítica. Los límites antifraude compartidos entre varias wallets deben sincronizarse por usuario. Los errores de propiedad deben ser genéricos y no revelar si un identificador ajeno existe.

## Bingo: elegibilidad, premios y reembolsos

- Un cartón solo puede ganar si su lote y todos los pagos asociados son elegibles; pagos pendientes, rechazados, cancelados o soft-deleted no habilitan premio.
- El sorteo y la reclamación se aíslan por ronda activa.
- Mantener orden consistente de locks.
- La titularidad se resuelve desde participación/pago del cartón, no desde el vendedor.
- Premios de compradores externos pueden quedar `suspended`; casos históricos inconsistentes pueden quedar `disputed`. No acreditar automáticamente esos importes al vendedor.
- Revertir una venta offline no demuestra devolución física del efectivo. La obligación debe quedar registrada para conciliación.
- El worker `bingo-refunds` está separado del worker de sorteos.

## Recuperación de premios

Los jobs respetan máximo de intentos y backoff. Si se agotan los intentos y quedan registros `waiting` o `processing`, deben contarse y exponerse para revisión manual. Una excepción por premio no debe detener el resto del lote. Nunca convertir automáticamente un estado incierto en pagado.

## USDT-TRC20

La configuración se controla con variables `PAYMENTS_TRON_*` en `backend/.env.example`. El proveedor mock es solo para local/testing y el provider de servicio impide su uso en producción. La integración real requiere revisar red, contrato/token, proveedor/indexador, confirmaciones, derivación de direcciones, expiración y conciliación de depósitos.

La XPUB es material de solo lectura; nunca sustituirla por seed o clave privada. No exponer secretos mediante variables `VITE_*`.

## Antes de operar con dinero real

- [ ] Pruebas de concurrencia e idempotencia contra MySQL/Redis reales.
- [ ] Conciliación de webhooks, depósitos y operaciones ambiguas.
- [ ] Revisión de seguridad de claves y secretos.
- [ ] Monitoreo, alertas y trazabilidad.
- [ ] Pruebas de recuperación y backups.
- [ ] Revisión legal, antifraude y de cumplimiento del territorio de operación.

Premios externos retenidos/disputados requieren procedimiento humano y evidencia de entrega; no deben presentarse como desembolsados.