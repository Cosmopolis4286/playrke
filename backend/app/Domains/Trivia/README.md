# 🎮 Sistema de Trivia - PlayRKE

Sistema de trivia en tiempo real con arquitectura multi-base de datos (MySQL + PostgreSQL), diseñado para alta concurrencia y preparado para escalar a microservicio.

## 📋 Índice

- [Arquitectura](#arquitectura)
- [Base de Datos](#base-de-datos)
- [Instalación](#instalación)
- [Uso](#uso)
- [API](#api)
- [Frontend](#frontend)
- [Tests](#tests)
- [Eventos WebSocket](#eventos-websocket)

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND (Vue.js)                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │  Lobby   │  │   Game   │  │ Results  │              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
                           │
                    WebSocket/HTTP
                           │
┌─────────────────────────────────────────────────────────┐
│                   LARAVEL BACKEND                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ RoomService  │  │GameEngineSvc │  │ ScoreService │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
└─────────────────────────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              │                         │
    ┌─────────▼─────────┐    ┌─────────▼─────────┐
    │      MySQL        │    │     PostgreSQL    │
    │  (Core: users)    │    │ (Trivia: rooms,   │
    │                   │    │  games, scores)   │
    └───────────────────┘    └───────────────────┘
              │                         │
    ┌─────────▼─────────┐    ┌─────────▼─────────┐
    │      Redis        │    │   WebSocket     │
    │  (Cache, State)   │    │   (Broadcast)   │
    └───────────────────┘    └───────────────────┘
```

---

## 🗄️ Base de Datos

### Esquema PostgreSQL (Trivia)

| Tabla | Descripción | Clave |
|-------|-------------|-------|
| `trivia_rooms` | Salas de juego | ULID PK, código único |
| `trivia_games` | Partidas activas | ULID PK, FK a rooms |
| `trivia_questions` | Banco de preguntas | ULID PK, jsonb options |
| `trivia_game_questions` | Preguntas por partida | ULID PK, orden único |
| `trivia_answers` | Respuestas de jugadores | ULID PK, triple unique |
| `trivia_scores` | Puntuaciones finales | ULID PK, índices analytics |
| `trivia_game_events` | Eventos para replay | ULID PK, sequence determinista |

### Características PRO

- ✅ **ULID** en todas las PKs y FKs (ordenado, único, distribuido)
- ✅ **unsignedInteger** para todos los campos numéricos
- ✅ **jsonb** para datos flexibles en PostgreSQL
- ✅ **Índices optimizados** sin redundancia
- ✅ **Snapshots** (time_limit, points) para proteger de cambios futuros
- ✅ **External references** solo con player_id/host_id

---

## ⚙️ Instalación

### 1. Crear base de datos PostgreSQL

```bash
createdb trivia
```

### 2. Configurar variables de entorno

```env
# PostgreSQL - Trivia
DB_TRIVIA_HOST=127.0.0.1
DB_TRIVIA_PORT=5432
DB_TRIVIA_DATABASE=trivia
DB_TRIVIA_USERNAME=postgres
DB_TRIVIA_PASSWORD=your_password

# WebSocket
VITE_WEBSOCKET_URL=wss://your-domain.com/ws
```

### 3. Ejecutar migraciones

```bash
# Migraciones trivia
php artisan migrate --path=database/migrations/2026_04_09* --database=pgsql_trivia

# O todas
php artisan migrate
```

### 4. Instalar dependencias frontend

```bash
cd backend
npm install
npm run build
```

---

## 🚀 Uso

### Crear una sala

```php
$service = new RoomService();
$room = $service->createRoom([
    'host_id' => 'user_123',
    'title' => 'Mi Sala',
    'max_players' => 50,
]);

echo "Código de sala: {$room->code}"; // ABC123
```

### Unirse a una sala (Frontend)

```typescript
// Lobby.vue
const { joinRoom } = useTrivia(roomCode)

await joinRoom('Player Name')
```

### Iniciar juego

```php
$game = $gameEngine->startGame($roomId);
```

### Enviar respuesta

```typescript
// En Game.vue
axios.post(`/api/trivia/games/${gameId}/answer`, {
    playerId: currentPlayerId,
    questionId: question.id,
    answer: selectedOptionIndex,
    responseTime: timeTaken
})
```

---

## 📡 API Endpoints

### Salas

```
POST   /api/trivia/rooms           Crear sala
GET    /api/trivia/rooms/{id}      Ver sala
PUT    /api/trivia/rooms/{id}      Actualizar
DELETE /api/trivia/rooms/{id}      Eliminar
POST   /api/trivia/rooms/{id}/join Unirse
POST   /api/trivia/rooms/{id}/leave Abandonar
```

### Juegos

```
POST   /api/trivia/games/start/{roomId}  Iniciar
GET    /api/trivia/games/{gameId}        Estado
GET    /api/trivia/games/{gameId}/question  Pregunta actual
POST   /api/trivia/games/{gameId}/answer    Responder
POST   /api/trivia/games/{gameId}/next      Siguiente
POST   /api/trivia/games/{gameId}/finish    Terminar
GET    /api/trivia/games/{gameId}/leaderboard Ranking
```

---

## 💻 Frontend (Vue 3 + Inertia)

### Estructura de Componentes

```
resources/js/
├── Pages/Trivia/
│   ├── Lobby.vue       ← Sala de espera
│   ├── Game.vue        ← Juego en tiempo real
│   └── Results.vue     ← Resultados finales
│
├── Components/Trivia/
│   ├── Room/
│   │   ├── RoomHeader.vue
│   │   ├── PlayerList.vue
│   │   └── JoinForm.vue
│   │
│   ├── Game/
│   │   ├── GameHeader.vue
│   │   ├── QuestionCard.vue
│   │   ├── AnswerOptions.vue
│   │   ├── TimerBar.vue
│   │   └── GameStatus.vue
│   │
│   ├── Score/
│   │   ├── Leaderboard.vue
│   │   └── PlayerScore.vue
│   │
│   └── Shared/
│       ├── ConnectionStatus.vue
│       └── LoadingOverlay.vue
```

### Flujo de Datos

```
WebSocket Event → Game.vue → Props → Componentes hijos

Events:
- game.started      → Redirect Game
- question.sent    → Show Question + Timer
- answer.received  → Disable options
- question.closed  → Show correct answer
- leaderboard.updated → Update scores
- game.finished    → Redirect Results
```

---

## 🧪 Tests

```bash
# Tests completos trivia
php artisan test tests/Unit/Domains/Trivia

# Tests específicos
php artisan test --filter=RoomServiceTest
php artisan test --filter=GameEngineServiceTest
php artisan test --filter=ScoreServiceTest
```

### Cobertura

| Servicio | Tests | Cobertura |
|----------|-------|-----------|
| RoomService | 26 | ✅ 100% |
| GameEngineService | 7 | ✅ 100% |
| ScoreService | 7 | ✅ 100% |

---

## 🔌 Eventos WebSocket

### Cliente → Servidor

```javascript
// Unirse a sala
ws.send(JSON.stringify({
    type: 'room.join',
    roomCode: 'ABC123',
    playerId: 'user_123'
}))

// Responder pregunta
ws.send(JSON.stringify({
    type: 'answer.submit',
    gameId: 'game_ulid',
    questionId: 'question_ulid',
    answer: 0, // Índice opción
    responseTimeMs: 2300
}))
```

### Servidor → Cliente

```javascript
// Nueva pregunta
{
    type: 'question.sent',
    question: {
        id: 'ulid',
        questionText: '...',
        options: ['A', 'B', 'C', 'D'],
        timeLimit: 15
    }
}

// Leaderboard actualizado
{
    type: 'leaderboard.updated',
    leaderboard: [
        { id: 'p1', name: 'Player', score: 500 }
    ]
}
```

---

## 🔧 Configuración Avanzada

### config/trivia.php

```php
[
    'game' => [
        'default_question_count' => 10,
        'default_time_per_question' => 15,
        'base_points_per_question' => 100,
        'streak_bonus' => [1 => 0, 2 => 50, 3 => 100], // Bonus por racha
    ],
    'redis' => [
        'ttl' => [
            'game_state' => 3600,  // 1 hora
            'room' => 7200,        // 2 horas
            'player_session' => 1800, // 30 min
        ],
    ],
    'anti_cheat' => [
        'max_response_time_ms' => 20000, // 20s máximo
        'min_response_time_ms' => 500,   // 0.5s mínimo (bot detection)
    ],
]
```

---

## 📊 Escalabilidad

### Preparado para alta concurrencia

- ✅ **Redis** para estado en tiempo real
- ✅ **PostgreSQL** con índices optimizados
- ✅ **WebSocket** broadcast eficiente
- ✅ **ULID** evita colisiones en distributed systems
- ✅ **Sin joins** entre MySQL y PostgreSQL

### Límites configurables

| Recurso | Límite por defecto | Configurable |
|---------|-------------------|--------------|
| Jugadores/sala | 100 | `max_players` |
| Preguntas/juego | 50 | `question_count` |
| Salas activas | ∞ | - |
| TTL Redis | 2 horas | `redis.ttl` |

---

## 🚀 Migración a Microservicio

El sistema está diseñado para extracción futura:

1. ✅ DB PostgreSQL ya separada
2. ✅ Domain `app/Domains/Trivia/` autocontenido
3. ✅ External references (no FKs a users)
4. ✅ Eventos desacoplados
5. ✅ Config centralizada

### Checklist migración

- [ ] Extraer a repositorio separado
- [ ] Reemplazar `event()` por HTTP/MQ
- [ ] Auth service-to-service
- [ ] CORS cross-domain
- [ ] Mantener o migrar DB trivia

---

## 📝 Licencia

Proprietary - PlayRKE Platform

---

## 👥 Contacto

Equipo PlayRKE - Development Team
