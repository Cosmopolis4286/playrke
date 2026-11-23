# Pruebas de carga de Bingo

Antes de anunciar el lanzamiento, medir cuántos jugadores aguanta una sala en
vivo. **Siempre contra un servidor de pruebas** con la misma imagen y recursos
que producción, nunca contra producción.

## Tiempo real (lo más crítico)

`bingo-realtime.js` abre N conexiones WebSocket a Reverb suscritas a una sala
y cuenta las bolas que recibe cada una.

1. Instalar k6: https://grafana.com/docs/k6/latest/set-up/install-k6/
2. Crear en el servidor de pruebas una sala con inicio manual y algunos cartones.
3. Correr la prueba y, ya con las conexiones abiertas, iniciar el sorteo:

```bash
k6 run \
  -e WS_URL=wss://ws.staging.example.com \
  -e APP_KEY=<REVERB_APP_KEY> \
  -e ROOM_ID=<id de la sala> \
  -e VUS=2000 -e DURATION=300 \
  scripts/loadtest/bingo-realtime.js
```

| Métrica | Qué significa | Meta |
|---|---|---|
| `ws_subscribed` | conexiones que lograron suscribirse a la sala | > 99 % |
| `ws_received_balls` | conexiones que recibieron bolas durante el sorteo | > 99 % |
| `ws_connect_ms` p95 | tiempo hasta conectar | < 2 s |
| `ws_errors` | errores del protocolo o del socket | ~0 |

Ir subiendo `VUS` (500 → 1000 → 2000 → 5000) hasta que falle algún umbral:
ese es el techo de una instancia de Reverb. Sin `ext-uv` el techo es ~1.000.

**La máquina que corre k6** también necesita un límite alto de archivos
abiertos (`ulimit -n 65536`) o se queda sin sockets antes que el servidor.

## Qué mirar en el servidor durante la prueba

- `docker stats playrke_reverb`: CPU y memoria de Reverb.
- Logs de `queue-bingo-draw`: que el sorteo no se atrase entre fichas.
- `php artisan bingo:active-draws` y el lobby de la sala: el sorteo termina y
  paga normalmente.
