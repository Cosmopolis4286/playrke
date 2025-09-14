# Patrón Repository - Nueva Arquitectura

## Índice
1. [Introducción](#introducción)
2. [Beneficios](#beneficios)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Contratos vs Implementaciones](#contratos-vs-implementaciones)
5. [Cómo Usar los Repositories](#cómo-usar-los-repositories)
6. [Migración desde Modelos con Métodos Estáticos](#migración-desde-modelos-con-métodos-estáticos)
7. [Ejemplos Prácticos](#ejemplos-prácticos)
8. [Mejores Prácticas](#mejores-prácticas)
9. [Testing](#testing)

## Introducción

Hemos implementado el patrón Repository para mejorar la arquitectura del proyecto, separando la lógica de acceso a datos de los modelos Eloquent y controladores.

### ¿Qué es el Patrón Repository?

El patrón Repository encapsula la lógica necesaria para acceder a las fuentes de datos. Centraliza la funcionalidad común de acceso a datos, proporcionando un mejor mantenimiento y desacoplando la infraestructura o tecnología utilizada para acceder a las bases de datos de la capa del modelo de dominio.

## Beneficios

### 🎯 **Separación de Responsabilidades**
- Los **Modelos** solo contienen relaciones, accessors, mutators y configuración
- Los **Repositories** manejan toda la lógica de consultas complejas
- Los **Controladores** solo coordinan entre servicios sin lógica de negocio

### 🧪 **Facilita el Testing**
- Se pueden crear mocks de los repositories fácilmente
- Testing unitario más limpio y enfocado
- Permite test doubles para casos específicos

### 🔧 **Mantenibilidad**
- Código más organizado y legible
- Cambios en la lógica de datos centralizados
- Facilita refactoring y evolución del código

### 📊 **Principios SOLID**
- **S**ingle Responsibility: Cada clase tiene una responsabilidad específica
- **O**pen/Closed: Abierto para extensión, cerrado para modificación
- **D**ependency Inversion: Dependemos de abstracciones, no concreciones

## Estructura de Directorios

```
app/
├── Repositories/
│   ├── Contracts/                          # Interfaces/Contratos
│   │   ├── BaseRepositoryContract.php      # Contrato base
│   │   ├── Users/
│   │   │   └── UserRepositoryContract.php
│   │   └── Games/
│   │       └── Bingo/
│   │           └── BingoRoomRepositoryContract.php
│   ├── BaseRepository.php                  # Implementación base
│   ├── Users/
│   │   └── UserRepository.php              # Implementación específica
│   └── Games/
│       └── Bingo/
│           └── BingoRoomRepository.php
├── Providers/
│   └── RepositoryServiceProvider.php       # Registro de dependencias
└── Models/
    └── [Modelos refactorizados]
```

## Contratos vs Implementaciones

### ¿Por qué usar Contratos?

Los contratos (interfaces) definen **QUÉ** puede hacer un repository, mientras que las implementaciones definen **CÓMO** lo hace.

```php
// ❌ MALO: Dependencia directa de implementación
public function __construct(BingoRoomRepository $repository) {}

// ✅ BUENO: Dependencia de abstracción
public function __construct(BingoRoomRepositoryContract $repository) {}
```

### Beneficios de los Contratos:
1. **Flexibilidad**: Se puede cambiar la implementación sin afectar el código cliente
2. **Testing**: Fácil creación de mocks
3. **Documentación**: El contrato documenta todas las operaciones disponibles
4. **Principio de Inversión de Dependencias**: Dependemos de abstracciones

## Cómo Usar los Repositories

### 1. Registrar el Repository en el ServiceProvider

```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(BingoRoomRepositoryContract::class, BingoRoomRepository::class);
```

### 2. Inyectar en el Controlador

```php
// app/Http/Controllers/BingoController.php
public function __construct(
    private readonly BingoRoomRepositoryContract $bingoRoomRepository
) {}
```

### 3. Usar los Métodos del Repository

```php
// Métodos básicos heredados del BaseRepository
$room = $this->bingoRoomRepository->find($id);
$rooms = $this->bingoRoomRepository->all();
$room = $this->bingoRoomRepository->create($data);

// Métodos específicos del BingoRoomRepository
$room = $this->bingoRoomRepository->findByEncryptedCode($code);
$rooms = $this->bingoRoomRepository->findByCreator($creatorId);
$stats = $this->bingoRoomRepository->getRoomSalesStats($roomId);
```

## Migración desde Modelos con Métodos Estáticos

### ANTES: Modelo con Métodos Estáticos

```php
// ❌ ANTES: Lógica de consultas en el modelo
class BingoRoom extends Model
{
    public static function getById($room, $fields = ['*'])
    {
        return self::select($fields)->where('id', $room)->first();
    }

    public static function getByEncrypted($encryptedID, $fields = ['*'])
    {
        return self::select($fields)->where('encrypted', $encryptedID)->first();
    }

    public static function existsRoom($room)
    {
        return self::where('id', $room)->exists();
    }

    // Más métodos estáticos...
}

// En el controlador
$room = BingoRoom::getById($id);
$exists = BingoRoom::existsRoom($id);
```

### DESPUÉS: Patrón Repository

```php
// ✅ DESPUÉS: Modelo limpio
class BingoRoom extends Model
{
    // Solo configuración, relaciones y accessors
    protected $fillable = [...];
    protected $casts = [...];

    public function status(): BelongsTo
    {
        return $this->belongsTo(GameStatus::class, 'status_id');
    }

    // Sin métodos estáticos de consulta
}

// Repository con la lógica de consultas
class BingoRoomRepository extends BaseRepository
{
    public function findByEncryptedCode(string $encryptedCode): ?BingoRoom
    {
        return $this->model->where('encrypted_code', $encryptedCode)->first();
    }

    // Más métodos específicos...
}

// En el controlador
public function __construct(BingoRoomRepositoryContract $repository)
{
    $this->bingoRoomRepository = $repository;
}

public function show($id)
{
    $room = $this->bingoRoomRepository->find($id);
    $exists = $this->bingoRoomRepository->exists($id);
}
```

## Ejemplos Prácticos

### Ejemplo 1: Buscar Salas por Múltiples Criterios

```php
// En el Repository
public function searchRooms(array $criteria): Collection
{
    $query = $this->model->query();

    if (isset($criteria['creator_id'])) {
        $query->where('creator_user_id', $criteria['creator_id']);
    }

    if (isset($criteria['status_id'])) {
        $query->where('status_id', $criteria['status_id']);
    }

    if (isset($criteria['room_type'])) {
        $query->where('room_type', $criteria['room_type']);
    }

    return $query->orderBy('created_at', 'desc')->get();
}

// En el Controlador
public function index(Request $request)
{
    $criteria = array_filter([
        'creator_id' => $request->get('creator_id'),
        'status_id' => $request->get('status_id'),
        'room_type' => $request->get('room_type'),
    ]);

    $rooms = $this->bingoRoomRepository->searchRooms($criteria);

    return response()->json(['data' => $rooms]);
}
```

### Ejemplo 2: Estadísticas Complejas

```php
// En el Repository
public function getRoomSalesStats(string $roomId): array
{
    $room = $this->model->with(['cards', 'players'])->find($roomId);

    return [
        'total_sales' => $room->total_sales,
        'total_players' => $room->total_players,
        'total_cards' => $room->total_cards,
        'commissions' => [
            'rke' => $room->rke_commission,
            'room' => $room->room_commission,
            'seller' => $room->seller_commission,
        ],
    ];
}

// En el Controlador
public function stats($id)
{
    $stats = $this->bingoRoomRepository->getRoomSalesStats($id);
    return response()->json(['stats' => $stats]);
}
```

## Mejores Prácticas

### 1. **Nomenclatura Consistente**
```php
// ✅ BUENO
findByEmail($email)
findByStatus($status)
getUsersByRole($role)

// ❌ MALO
getEmail($email)
findStatus($status)
usersWithRole($role)
```

### 2. **Métodos Específicos vs Genéricos**
```php
// ✅ BUENO: Método específico y autodocumentado
public function getActiveRoomsWithPendingPrizes(): Collection
{
    return $this->model->active()
        ->withPendingPrizes()
        ->with(['status', 'creator'])
        ->get();
}

// ❌ MALO: Método genérico que requiere conocer la lógica
public function getWhere(['status' => 'active', 'prizes_paid' => false])
```

### 3. **Usar Type Hints**
```php
// ✅ BUENO
public function findByEncryptedCode(string $encryptedCode): ?BingoRoom
public function getRoomSalesStats(string $roomId): array
public function searchRooms(array $criteria): Collection

// ❌ MALO
public function findByEncryptedCode($encryptedCode)
public function getRoomSalesStats($roomId)
```

### 4. **Documentación con PHPDoc**
```php
/**
 * Buscar salas por múltiples criterios
 *
 * @param array $criteria Criterios de búsqueda
 * @return Collection<BingoRoom>
 */
public function searchRooms(array $criteria): Collection
```

### 5. **Manejo de Errores**
```php
// En Repository: No capturar excepciones innecesarias
public function findOrFail(string $id): BingoRoom
{
    return $this->model->findOrFail($id);
}

// En Controller: Capturar y manejar apropiadamente
try {
    $room = $this->bingoRoomRepository->findOrFail($id);
} catch (ModelNotFoundException $e) {
    return response()->json(['error' => 'Sala no encontrada'], 404);
}
```

## Testing

### Testing del Repository

```php
// tests/Unit/Repositories/BingoRoomRepositoryTest.php
class BingoRoomRepositoryTest extends TestCase
{
    private BingoRoomRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(BingoRoomRepositoryContract::class);
    }

    public function test_can_find_room_by_encrypted_code()
    {
        $room = BingoRoom::factory()->create(['encrypted_code' => 'ABC123']);

        $found = $this->repository->findByEncryptedCode('ABC123');

        $this->assertNotNull($found);
        $this->assertEquals($room->id, $found->id);
    }

    public function test_returns_null_when_room_not_found_by_encrypted_code()
    {
        $found = $this->repository->findByEncryptedCode('NONEXISTENT');

        $this->assertNull($found);
    }
}
```

### Testing del Controlador con Mock

```php
// tests/Unit/Controllers/BingoControllerTest.php
class BingoControllerTest extends TestCase
{
    public function test_show_returns_room_data()
    {
        // Crear mock del repository
        $mockRepository = $this->createMock(BingoRoomRepositoryContract::class);
        $mockRepository->method('findOrFail')
                      ->with('123')
                      ->willReturn(new BingoRoom(['id' => '123']));

        // Inyectar mock en el container
        $this->app->instance(BingoRoomRepositoryContract::class, $mockRepository);

        // Hacer la petición
        $response = $this->getJson('/api/bingo/rooms/123');

        // Verificar respuesta
        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }
}
```

## Migración Paso a Paso

### Paso 1: Crear el Contrato
```php
// app/Repositories/Contracts/ModelRepositoryContract.php
interface ModelRepositoryContract extends BaseRepositoryContract
{
    // Métodos específicos
}
```

### Paso 2: Crear la Implementación
```php
// app/Repositories/ModelRepository.php
class ModelRepository extends BaseRepository implements ModelRepositoryContract
{
    protected function makeModel(): Model
    {
        return new YourModel();
    }

    // Implementar métodos específicos
}
```

### Paso 3: Registrar en el ServiceProvider
```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(ModelRepositoryContract::class, ModelRepository::class);
```

### Paso 4: Refactorizar el Modelo
- Mover métodos estáticos al repository
- Mantener solo relaciones, accessors, mutators
- Limpiar el modelo

### Paso 5: Actualizar Controladores
- Inyectar el contrato del repository
- Reemplazar llamadas estáticas del modelo
- Usar métodos del repository

### Paso 6: Crear Tests
- Test unitarios del repository
- Test de integración del controlador
- Verificar funcionamiento

## Conclusión

El patrón Repository nos permite:

✅ **Código más limpio y mantenible**
✅ **Mejor separación de responsabilidades**
✅ **Facilita el testing**
✅ **Sigue principios SOLID**
✅ **Arquitectura escalable**

La migración debe hacerse gradualmente, priorizando los modelos más utilizados y complejos primero.