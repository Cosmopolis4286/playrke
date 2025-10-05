# WebSockets con Laravel Reverb - PlayRKE Bingo

Guía completa de configuración y uso de WebSockets en tiempo real para el sistema de bingo.

## 📋 Tabla de Contenidos

- [Introducción](#introducción)
- [Configuración](#configuración)
- [Eventos Disponibles](#eventos-disponibles)
- [Canales de Broadcasting](#canales-de-broadcasting)
- [Uso desde el Frontend](#uso-desde-el-frontend)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Introducción

Laravel Reverb es el servidor WebSocket oficial de Laravel que permite comunicación en tiempo real bidireccional entre el servidor y los clientes.

### ¿Por qué Reverb?

- ✅ Integración nativa con Laravel
- ✅ Gratis y de código abierto
- ✅ Escalable con Redis
- ✅ Compatible con Laravel Echo
- ✅ Sin dependencias externas como Pusher

---

## ⚙️ Configuración

### 1. Variables de Entorno

Asegúrate de tener estas variables en tu `.env`:

```bash
# Broadcasting
BROADCAST_CONNECTION=reverb

# Laravel Reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=localhost
REVERB_PORT=6001
REVERB_SCHEME=http

# Reverb Server
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=6001
```

### 2. Iniciar Servidor Reverb

```bash
php artisan reverb:start
```

**Salida esperada**:
```
  INFO  Starting server on 0.0.0.0:6001.

  Local: ws://localhost:6001 ✓
```

### 3. Verificar Conexión

En otra terminal:

```bash
php artisan reverb:ping
```

---

## 📡 Eventos Disponibles

### BingoNumberCalled

Se dispara cuando se canta un nuevo número en el bingo.

**Archivo**: `app/Events/Games/Bingo/BingoNumberCalled.php`

**Payload**:
```json
{
  "number": 42,
  "roomId": 123
}
```

**Uso en el backend**:
```php
use App\Events\BingoNumberCalled;

broadcast(new BingoNumberCalled(42, 123));
```

### BingoGameStarted

Se dispara cuando inicia un nuevo juego.

**Archivo**: `app/Events/Games/Bingo/BingoGameStarted.php`

**Payload**:
```json
{
  "room_id": 123,
  "draw_id": 456,
  "start_time": "2025-10-05T12:00:00.000000Z"
}
```

**Uso en el backend**:
```php
use App\Events\Games\Bingo\BingoGameStarted;

broadcast(new BingoGameStarted($roomId, $drawId));
```

### BingoGameEnded

Se dispara cuando termina un juego.

**Archivo**: `app/Events/Games/Bingo/BingoGameEnded.php`

**Payload**:
```json
{
  "room_id": 123,
  "draw_id": 456,
  "has_winner": true,
  "end_time": "2025-10-05T12:15:00.000000Z"
}
```

**Uso en el backend**:
```php
use App\Events\Games\Bingo\BingoGameEnded;

broadcast(new BingoGameEnded($roomId, $drawId, true));
```

### BingoWinnerDeclared

Se dispara cuando se declara un ganador.

**Archivo**: `app/Events/Games/Bingo/BingoWinnerDeclared.php`

**Payload**:
```json
{
  "room_id": 123,
  "draw_id": 456,
  "winner": {
    "id": 789,
    "name": "Juan Pérez",
    "card_id": 101,
    "pattern": "full_card",
    "prize": 1000.00
  },
  "timestamp": "2025-10-05T12:14:30.000000Z"
}
```

**Uso en el backend**:
```php
use App\Events\Games\Bingo\BingoWinnerDeclared;

broadcast(new BingoWinnerDeclared(
    roomId: 123,
    drawId: 456,
    winnerId: 789,
    winnerName: 'Juan Pérez',
    cardId: 101,
    pattern: 'full_card',
    prize: 1000.00
));
```

### BingoPlayerJoined

Se dispara cuando un jugador se une a una sala.

**Archivo**: `app/Events/Games/Bingo/BingoPlayerJoined.php`

**Payload**:
```json
{
  "room_id": 123,
  "player": {
    "id": 789,
    "name": "Juan Pérez"
  },
  "current_players": 15,
  "timestamp": "2025-10-05T12:00:00.000000Z"
}
```

**Uso en el backend**:
```php
use App\Events\Games\Bingo\BingoPlayerJoined;

broadcast(new BingoPlayerJoined($roomId, $playerId, $playerName, $currentPlayers));
```

---

## 📻 Canales de Broadcasting

### Public Channel: `bingo.room.{roomId}`

Canal público para eventos de sala de bingo. **No requiere autenticación**.

**Eventos que se transmiten**:
- `NumberCalled` - Nuevo número cantado
- `GameStarted` - Juego iniciado
- `GameEnded` - Juego terminado
- `WinnerDeclared` - Ganador declarado
- `PlayerJoined` - Jugador se unió

**Ejemplo de suscripción (frontend)**:
```javascript
Echo.channel('bingo.room.123')
    .listen('NumberCalled', (e) => {
        console.log('Número:', e.number)
    })
```

### Private Channel: `bingo.player.{userId}`

Canal privado para notificaciones específicas del jugador. **Requiere autenticación**.

**Autorización** (routes/channels.php):
```php
Broadcast::channel('bingo.player.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

**Ejemplo de suscripción (frontend)**:
```javascript
Echo.private('bingo.player.123')
    .listen('PrizeAwarded', (e) => {
        console.log('Premio ganado:', e.amount)
    })
```

### Presence Channel: `bingo.room.{roomId}.presence`

Canal de presencia para ver quién está conectado. **Requiere autenticación**.

**Autorización** (routes/channels.php):
```php
Broadcast::channel('bingo.room.{roomId}.presence', function ($user, $roomId) {
    return [
        'id' => $user->id,
        'name' => $user->first_name . ' ' . $user->last_name,
        'username' => $user->username,
    ];
});
```

**Ejemplo de suscripción (frontend)**:
```javascript
Echo.join('bingo.room.123.presence')
    .here((users) => {
        console.log('Usuarios en línea:', users)
    })
    .joining((user) => {
        console.log(user.name + ' se unió')
    })
    .leaving((user) => {
        console.log(user.name + ' se fue')
    })
```

---

## 💻 Uso desde el Frontend (Nuxt 3)

### Configuración Laravel Echo

El frontend ya tiene configurado `useWebSocket.ts` composable.

**Ejemplo de uso**:

```typescript
// En un componente Vue
const { listenToChannel, disconnect } = useWebSocket()

onMounted(async () => {
  await connect()

  // Escuchar números cantados
  listenToChannel('bingo.room.123', 'NumberCalled', (data) => {
    console.log('Número cantado:', data.number)
    // Actualizar UI
  })

  // Escuchar inicio de juego
  listenToChannel('bingo.room.123', 'GameStarted', (data) => {
    console.log('Juego iniciado')
  })

  // Escuchar ganador
  listenToChannel('bingo.room.123', 'WinnerDeclared', (data) => {
    console.log('Ganador:', data.winner.name)
    console.log('Premio:', data.winner.prize)
  })
})

onUnmounted(() => {
  disconnect()
})
```

### Uso del Composable Especializado

```typescript
const { startListening, stopListening } = useBingoWebSocket(roomId)

onMounted(() => {
  startListening()
})

onUnmounted(() => {
  stopListening()
})
```

El composable `useBingoWebSocket` automáticamente:
- Se conecta al canal de la sala
- Escucha todos los eventos de bingo
- Actualiza el `bingoStore` automáticamente
- Maneja reconexión

---

## 🧪 Testing

### Test Manual con PHP

```php
// En tinker: php artisan tinker

use App\Events\Games\Bingo\BingoNumberCalled;

// Disparar evento
broadcast(new BingoNumberCalled(42, 123));
```

### Test con curl

```bash
# Requiere que Reverb esté corriendo
curl -X POST http://localhost:6001/apps/local/events \
  -H "Content-Type: application/json" \
  -d '{
    "name": "NumberCalled",
    "channel": "bingo.room.123",
    "data": "{\"number\": 42, \"roomId\": 123}"
  }'
```

### Monitoring en Tiempo Real

```bash
# Ver logs de Reverb
php artisan reverb:start --debug
```

---

## 🔧 Troubleshooting

### Error: "Connection refused at port 6001"

**Problema**: Reverb no está corriendo.

**Solución**:
```bash
php artisan reverb:start
```

### Error: "CORS policy error"

**Problema**: Frontend no está en la lista de orígenes permitidos.

**Solución**: Verificar `config/reverb.php`:
```php
'allowed_origins' => ['*'], // O especificar: ['http://localhost:3000']
```

### Error: "401 Unauthorized" en canales privados

**Problema**: Usuario no está autenticado o no tiene permisos.

**Solución**:
1. Verificar que el usuario esté autenticado
2. Verificar la lógica de autorización en `routes/channels.php`
3. Asegurarse de que Laravel Echo esté enviando el token/cookie correcto

### Eventos no se reciben en el frontend

**Problema**: Canal o nombre de evento incorrecto.

**Solución**:
1. Verificar que el nombre del canal coincida: `bingo.room.{roomId}`
2. Verificar que el nombre del evento coincida (sin prefijo): `NumberCalled`
3. Revisar logs de Reverb con `--debug`
4. Verificar que el evento implemente `ShouldBroadcastNow`

### Múltiples conexiones/desconexiones

**Problema**: El componente se monta/desmonta múltiples veces.

**Solución**:
- Usar `onMounted` y `onUnmounted` correctamente
- Llamar `disconnect()` en cleanup
- Evitar crear múltiples instancias de Echo

---

## 📊 Arquitectura del Sistema

```
┌─────────────────┐
│  Laravel App    │
│  (Backend)      │
└────────┬────────┘
         │
         │ broadcast()
         ▼
┌─────────────────┐
│ Laravel Reverb  │
│  Port: 6001     │
└────────┬────────┘
         │
         │ WebSocket
         ▼
┌─────────────────┐
│  Nuxt 3 App     │
│  (Frontend)     │
└─────────────────┘
```

### Flujo de un Evento

1. **Backend dispara evento**:
   ```php
   broadcast(new BingoNumberCalled(42, 123));
   ```

2. **Reverb recibe y retransmite**:
   - Valida el canal
   - Serializa el payload
   - Envía a todos los clientes suscritos

3. **Frontend recibe evento**:
   ```typescript
   Echo.channel('bingo.room.123')
       .listen('NumberCalled', (e) => {
           // Actualizar UI
       })
   ```

---

## 📝 Mejores Prácticas

### 1. Usar `ShouldBroadcastNow`

Para eventos que deben transmitirse inmediatamente:

```php
class BingoNumberCalled implements ShouldBroadcastNow
```

### 2. Limitar el Payload

Solo enviar datos necesarios:

```php
public function broadcastWith()
{
    return [
        'number' => $this->number,
        'roomId' => $this->roomId,
        // No enviar objetos completos innecesariamente
    ];
}
```

### 3. Manejar Reconexión

```typescript
const echo = new Echo({
    broadcaster: 'reverb',
    // ...
    enabledTransports: ['ws', 'wss'],
})

echo.connector.pusher.connection.bind('disconnected', () => {
    // Intentar reconectar
})
```

### 4. Cleanup en Frontend

Siempre desconectar cuando el componente se desmonta:

```typescript
onUnmounted(() => {
    Echo.leave('bingo.room.123')
})
```

---

## 🚀 Despliegue a Producción

### Variables de Entorno

```bash
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=production_app_id
REVERB_APP_KEY=your-secure-key
REVERB_APP_SECRET=your-secure-secret
REVERB_HOST=wss.tudominio.com
REVERB_PORT=443
REVERB_SCHEME=https

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=6001
```

### SSL/TLS

Configurar certificado SSL en `config/reverb.php`:

```php
'options' => [
    'tls' => [
        'local_cert' => '/path/to/cert.pem',
        'local_pk' => '/path/to/key.pem',
    ],
],
```

### Supervisor

Configurar Supervisor para mantener Reverb corriendo:

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
```

---

## ✅ Checklist

- [ ] Variables de Reverb en `.env`
- [ ] `BROADCAST_CONNECTION=reverb`
- [ ] Reverb corriendo (`php artisan reverb:start`)
- [ ] Eventos creados en `app/Events/Games/Bingo/`
- [ ] Canales configurados en `routes/channels.php`
- [ ] Frontend configurado con Laravel Echo
- [ ] Test de conexión exitoso
- [ ] Eventos se reciben en el frontend
- [ ] Cleanup implementado en componentes

---

**Última actualización**: 2025-10-05
**Versión**: 1.0.0
**Compatible con**: Laravel 10.x, Nuxt 3
