# Pagos, wallets y premios

Este documento recoge invariantes operativas; no certifica que una pasarela o proveedor esté habilitado para fondos reales.

## Reglas financieras

1. Operar solo sobre wallets y destinos propios, salvo permiso explícito.
2. Actualizar saldo, movimiento y estado en una transacción coherente.
3. Adquirir locks sobre filas relevantes y revalidar saldo/estado tras obtenerlos.
4. Hacer idempotentes los reintentos, webhooks y jobs.
5. Conservar actor, beneficiario, referencia externa, importe, estado y auditoría.
6. Conciliar operaciones ambiguas; nunca marcarlas pagadas por conveniencia.

## Bingo

- La elegibilidad de un cartón depende del lote y pagos asociados.
- El sorteo y la reclamación se aíslan por ronda.
- Resolver titularidad desde participación/pago, no desde el vendedor.
- Premios externos retenidos o disputados requieren revisión humana.
- Una reversión de venta offline no prueba que el efectivo haya sido devuelto.
- Mantener workers y colas de premios/reembolsos alineados con Compose.

## Recuperación

Los jobs deben respetar máximo de intentos y backoff. Si se agotan intentos con registros pendientes, exponerlos para revisión manual. Una excepción por premio no debe detener el lote. Nunca convertir estados inciertos en pagados.

## USDT-TRC20

Las variables de configuración están en `backend/.env.example`. El proveedor mock es solo para local/testing. La integración real requiere validar red, token/contrato, proveedor/indexador, confirmaciones, derivación de direcciones, expiración y conciliación. Una XPUB es de solo lectura; nunca sustituirla por seed o clave privada.

## Antes de operar con dinero real

- [ ] Pruebas de concurrencia e idempotencia contra MySQL/Redis reales.
- [ ] Conciliación de depósitos, pagos, retiros, reembolsos y webhooks.
- [ ] Gestión segura de claves y secretos.
- [ ] Monitoreo, alertas, trazabilidad, backups y restauración probada.
- [ ] Procedimiento humano para premios retenidos/disputados.
- [ ] Revisión legal, antifraude y de cumplimiento del territorio de operación.
