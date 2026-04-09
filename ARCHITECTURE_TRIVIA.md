# Arquitectura de Trivia en Tiempo Real

## Resumen

Sistema de trivia multi-sala en tiempo real, diseñado como módulo desacoplado dentro de Laravel, preparado para migración a microservicios.

---

## Estructura del Módulo

```
app/Domains/Trivia/
├── Contracts/          # Interfaces (desacoplamiento)
│   ├── GameEngineInterface.php
│   ├── RoomServiceInterface.php
│   ├── ScoreServiceInterface.php
│   └── GameStateRepositoryInterface.php
├── DTOs/              # Objetos de transferencia
│   ├── GameStateDTO.php
│   ├── QuestionDTO.php
│   ├── AnswerResultDTO.php
│   ├── RoomDTO.php
│   ├── PlayerDTO.php
│   ├── ScoreDTO.php
│   └── LeaderboardDTO.php
├── Events/            # Eventos de dominio (broadcasting)
│   ├── GameStarted.php
│   ├── QuestionSent.php
│   ├── AnswerSubmitted.php
│   ├── ScoreUpdated.php
│   ├── GameFinished.php
│   ├── PlayerJoined.php
│   └── PlayerLeft.php
├── Models/            # Eloquent (tablas trivia_*)
│   ├── TriviaRoom.php
│   ├── TriviaGame.php
│   ├── TriviaQuestion.php
│   ├── TriviaAnswer.php
│   └── TriviaScore.php
├── Repositories/      # Implementaciones de storage
│   └── RedisGameStateRepository.php
├── Services/          # Lógica de negocio
│   ├── GameEngineService.php
│   ├── RoomService.php
│   └── ScoreService.php
└── Actions/           # Casos de uso
    ├── StartGameAction.php
    ├── SubmitAnswerAction.php
    ├── FinishGameAction.php
    └── JoinRoomAction.php
```

---

## Flujo de Datos

### 1. Usuario Crea Sala
```
POST /api/trivia/rooms
     ↓
RoomService::createRoom()
     ↓
TriviaRoom (DB) + RoomDTO (Redis)
```

### 2. Jugadores Se Unen
```
POST /api/trivia/rooms/{id}/join
     ↓
JoinRoomAction::execute()
     ↓
RoomService::joinRoom()
     ↓
PlayerJoined Event → Broadcast WebSocket
```

### 3. Inicia Juego
```
POST /api/trivia/games/start/{roomId}
     ↓
StartGameAction::execute()
     ↓
GameEngineService::startGame()
     ↓
GameStarted Event → Broadcast a sala
     ↓
sendNextQuestion() automático
     ↓
QuestionSent Event → Broadcast a jugadores
```

### 4. Jugador Responde
```
POST /api/trivia/games/{gameId}/answer
     ↓
SubmitAnswerAction::execute()
     ↓
GameEngineService::submitAnswer()
     ↓
Validación + Cálculo puntos
     ↓
AnswerSubmitted Event → Broadcast
     ↓
ScoreUpdated Event → Leaderboard actualizado
```

### 5. Finaliza Juego
```
Auto: Última pregunta respondida
     ↓
GameEngineService::finishGame()
     ↓
Persistir scores en DB
     ↓
GameFinished Event → Broadcast resultados
```

---

## Estados en Redis

| Key | TTL | Contenido |
|-----|-----|-----------|
| `trivia:game:{id}` | 1h | GameStateDTO |
| `trivia:score:{game}:{player}` | 1h | ScoreDTO |
| `trivia:leaderboard:{game}` | 1h | Sorted set |
| `trivia:room:{id}` | 2h | RoomDTO |
| `trivia:player:{room}:{id}` | 2h | PlayerDTO |
| `trivia:question:{game}` | 1h | QuestionDTO |

---

## Canales WebSocket

```javascript
// Públicos
trivia.room.{roomId}      // Eventos de sala
trivia.game.{gameId}      // Eventos de juego

// Privados
trivia.player.{playerId}  // Resultados personales
```

---

## Eventos Disponibles

| Evento | Canal | Payload |
|--------|-------|---------|
| `trivia.game.started` | room, game | game_id, total_questions |
| `trivia.question.sent` | game | question, server_timestamp |
| `trivia.answer.submitted` | game, player | is_correct, points, position |
| `trivia.score.updated` | game | leaderboard, highlighted_player |
| `trivia.game.finished` | room, game | final_leaderboard, winners |
| `trivia.player.joined` | room | player, current_count |
| `trivia.player.left` | room | player_id, current_count |

---

## Preparación para Microservicios

### Interfaces Claras
Todas las dependencias usan interfaces:
```php
interface GameEngineInterface {
    public function startGame(string $roomId, array $playerIds): GameStateDTO;
    public function submitAnswer(...): AnswerResultDTO;
}
```

### DTOs Serializables
```json
{
  "game_id": "123",
  "status": "active",
  "player_ids": ["1", "2", "3"],
  "scores": [...]
}
```

### Eventos Desacoplados
Los eventos pueden enviarse a:
- Laravel Reverb (actual)
- RabbitMQ/SQS (futuro microservicio)
- Webhooks a API externa

### Sin Acoplamiento al Core
- Tablas con prefijo `trivia_`
- No foreign keys a `users` table
- Referencias externas como strings

---

## API Endpoints

### Salas
```
GET    /api/trivia/rooms
POST   /api/trivia/rooms
GET    /api/trivia/rooms/{id}
PUT    /api/trivia/rooms/{id}
DELETE /api/trivia/rooms/{id}
POST   /api/trivia/rooms/{id}/join
POST   /api/trivia/rooms/{id}/leave
```

### Juegos
```
POST   /api/trivia/games/start/{roomId}
GET    /api/trivia/games/{gameId}
GET    /api/trivia/games/{gameId}/question
POST   /api/trivia/games/{gameId}/answer
POST   /api/trivia/games/{gameId}/next
POST   /api/trivia/games/{gameId}/finish
POST   /api/trivia/games/{gameId}/cancel
GET    /api/trivia/games/{gameId}/leaderboard
```

---

## Configuración

```php
// config/trivia.php
[
    'game' => [
        'default_question_count' => 10,
        'default_time_per_question' => 15,
        'base_points_per_question' => 100,
    ],
    'redis' => [
        'ttl' => [
            'game_state' => 3600,
            'room' => 7200,
        ],
    ],
    'microservice' => [
        'enabled' => false,
        'base_url' => env('TRIVIA_SERVICE_URL'),
    ],
]
```

---

## Cliente Frontend (Ejemplo)

```javascript
import { useTrivia } from './echo-trivia.js';

const trivia = useTrivia(roomId, gameId, playerId);

trivia.join();

trivia.on('questionSent', ({ question }) => {
    displayQuestion(question);
    startTimer(question.time_limit_seconds);
});

trivia.on('scoreUpdated', ({ leaderboard }) => {
    updateLeaderboard(leaderboard.scores);
});
```

---

## Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate --path=database/migrations/2026_04_09*

# Seed de sala demo
php artisan db:seed --class=Database\Seeders\Trivia\TriviaRoomSeeder

# Tests
php artisan test tests/Unit/Domains/Trivia
```

---

## Checklist Migración a Microservicio

- [ ] Extraer `app/Domains/Trivia/` a repo separado
- [ ] Reemplazar `event()` por HTTP calls o message broker
- [ ] Implementar autenticación service-to-service
- [ ] Configurar CORS para comunicación cross-domain
- [ ] Mantener DB trivia_* o migrar a DB del microservicio
- [ ] Actualizar frontend para apuntar a nueva URL
