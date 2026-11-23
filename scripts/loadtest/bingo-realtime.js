/**
 * Prueba de carga del tiempo real de Bingo (Reverb, protocolo Pusher).
 *
 * Abre VUS conexiones WebSocket simultáneas, cada una suscrita al canal
 * público de una sala (bingo.room.{ROOM_ID}), y las mantiene abiertas
 * DURATION segundos contando cuántas bolas (NumberCalled) recibe cada una.
 * Mide lo que importa para lanzar: cuántos jugadores conectados a la vez
 * reciben el sorteo en vivo, y con qué latencia de conexión.
 *
 * Uso (contra un servidor de PRUEBAS, nunca producción):
 *   k6 run \
 *     -e WS_URL=wss://ws.staging.example.com \
 *     -e APP_KEY=<REVERB_APP_KEY> \
 *     -e ROOM_ID=<id numérico de una sala con sorteo en curso> \
 *     -e VUS=2000 -e DURATION=300 \
 *     scripts/loadtest/bingo-realtime.js
 *
 * Inicia un sorteo en esa sala mientras corre la prueba. Criterios de éxito
 * (thresholds abajo): ≥99 % de conexiones establecidas y suscritas, y cada
 * conexión recibe bolas. Ver scripts/loadtest/README.md.
 */
import ws from 'k6/ws'
import { check } from 'k6'
import { Counter, Rate, Trend } from 'k6/metrics'

const WS_URL = __ENV.WS_URL || 'ws://localhost:8080'
const APP_KEY = __ENV.APP_KEY || 'localkey'
const ROOM_ID = __ENV.ROOM_ID || '1'
const VUS = Number(__ENV.VUS || 500)
const DURATION = Number(__ENV.DURATION || 120)
// Rampa: no abrir todas las conexiones en el mismo segundo.
const RAMP = Number(__ENV.RAMP || Math.max(30, Math.ceil(VUS / 50)))

const subscribed = new Rate('ws_subscribed')
const receivedBalls = new Rate('ws_received_balls')
const ballsPerConnection = new Trend('ws_balls_per_connection')
const connectTime = new Trend('ws_connect_ms', true)
const errors = new Counter('ws_errors')

export const options = {
  scenarios: {
    players: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: `${RAMP}s`, target: VUS },
        { duration: `${DURATION}s`, target: VUS },
        { duration: '10s', target: 0 },
      ],
      gracefulRampDown: '15s',
    },
  },
  thresholds: {
    ws_subscribed: ['rate>0.99'],
    ws_received_balls: ['rate>0.99'],
    ws_connect_ms: ['p(95)<2000'],
  },
}

export default function () {
  const url = `${WS_URL}/app/${APP_KEY}?protocol=7&client=k6&version=8.4.0&flash=false`
  const started = Date.now()
  let balls = 0
  let isSubscribed = false

  const res = ws.connect(url, {}, socket => {
    socket.on('message', raw => {
      let msg
      try { msg = JSON.parse(raw) } catch { return }

      if (msg.event === 'pusher:connection_established') {
        connectTime.add(Date.now() - started)
        socket.send(JSON.stringify({ event: 'pusher:subscribe', data: { channel: `bingo.room.${ROOM_ID}` } }))
      } else if (msg.event === 'pusher_internal:subscription_succeeded') {
        isSubscribed = true
      } else if (msg.event === 'pusher:ping') {
        socket.send(JSON.stringify({ event: 'pusher:pong', data: {} }))
      } else if (typeof msg.event === 'string' && msg.event.endsWith('NumberCalled')) {
        balls++
      } else if (msg.event === 'pusher:error') {
        errors.add(1)
      }
    })

    socket.on('error', () => errors.add(1))

    // Cada VU queda conectado toda la prueba, como un jugador mirando la sala.
    socket.setTimeout(() => socket.close(), (DURATION + RAMP) * 1000)
  })

  check(res, { 'handshake 101': r => r && r.status === 101 })
  subscribed.add(isSubscribed)
  receivedBalls.add(balls > 0)
  ballsPerConnection.add(balls)
}
