# Dominio: Bingo

## Propósito
Este dominio encapsula toda la lógica de negocio relacionada con el juego de Bingo del sistema RKE.

## Estructura

```
Bingo/
├── Models/          # Entidades del dominio (BingoRoom, BingoCard, etc.)
├── Services/        # Lógica de negocio (GameEngine, RoomManager)
├── Repositories/    # Acceso a datos
├── Events/          # Eventos de dominio
├── Listeners/       # Handlers de eventos
├── DTOs/            # Objetos de transferencia de datos
├── Actions/         # Acciones específicas (Commands)
└── README.md        # Esta documentación
```

## Estado de Migración

### Completado ✅
- [x] Estructura de directorios
- [x] BingoRoom → Domains/Games/Bingo/Models/BingoRoom.php
- [x] BingoRoomConfig → Domains/Games/Bingo/Models/BingoRoomConfig.php
- [x] Capa de compatibilidad para transición gradual

### Pendiente ⏳
- [ ] Migrar resto de modelos (Card, Round, Draw, etc.)
- [ ] Crear Services (BingoGameEngine, RoomManager)
- [ ] Crear Repositories
- [ ] Mover lógica de Controllers a Actions/Services
- [ ] Tests unitarios del dominio

## Capa de Compatibilidad

Durante la migración, los modelos en `app/Models/Games/Bingo/*` extienden los del dominio.

```php
// Código antiguo (sigue funcionando)
use App\Models\Games\Bingo\Room\BingoRoom;

// Código nuevo (recomendado)
use App\Domains\Games\Bingo\Models\BingoRoom;
```

Ambos apuntan a la misma tabla y tienen el mismo comportamiento.

## Principios

1. **Single Responsibility**: Cada clase tiene un propósito claro
2. **Dependency Inversion**: Depender de abstracciones, no concreciones
3. **Domain First**: La lógica de negocio vive aquí, no en controllers
4. **Backward Compatibility**: Transición gradual sin romper código existente

## Relaciones con otros dominios

- `Common`: Usa GameStatus, GameMode, RoomType (shared kernel)
- `Trivia`: Mismo patrón de arquitectura
- `Wallet`: Integración para pagos y premios

## Notas Técnicas

- Stack: Laravel + Vue 3 + Inertia + Reverb
- Tablas: `bingo_rooms`, `bingo_cards`, `bingo_rounds`, etc.
- WebSocket: Canal `bingo.{room_id}` para actualizaciones en tiempo real
