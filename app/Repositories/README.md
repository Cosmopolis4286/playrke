# Repositories

Este directorio contiene la implementación del patrón Repository para el proyecto.

## Estructura

```
Repositories/
├── Contracts/                              # Interfaces/Contratos
│   ├── BaseRepositoryContract.php          # Contrato base con métodos comunes
│   ├── Users/
│   │   └── UserRepositoryContract.php      # Contrato específico para usuarios
│   └── Games/
│       └── Bingo/
│           └── BingoRoomRepositoryContract.php
├── BaseRepository.php                      # Implementación base abstracta
├── Users/
│   └── UserRepository.php                  # Implementación para usuarios
└── Games/
    └── Bingo/
        └── BingoRoomRepository.php         # Implementación para salas de bingo
```

## Uso Rápido

### 1. Inyectar en Controlador

```php
use App\Repositories\Contracts\Users\UserRepositoryContract;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {}

    public function show($id)
    {
        $user = $this->userRepository->find($id);
        return response()->json(['user' => $user]);
    }
}
```

### 2. Métodos Disponibles

#### BaseRepository (todos los repositories heredan):
- `all()` - Obtener todos los registros
- `find($id)` - Buscar por ID
- `findOrFail($id)` - Buscar por ID o lanzar excepción
- `create(array $data)` - Crear nuevo registro
- `update($id, array $data)` - Actualizar registro
- `delete($id)` - Eliminar registro
- `where(array $conditions)` - Buscar con condiciones
- `exists($id)` - Verificar existencia
- `count()` - Contar registros

#### Métodos Específicos por Repository:
Cada repository tiene métodos específicos para su dominio.

## Repositories Disponibles

### UserRepository
```php
// Buscar por email
$user = $userRepository->findByEmail('user@example.com');

// Usuarios por rol
$sellers = $userRepository->getUsersByRole('seller');

// Usuarios referidos
$referrals = $userRepository->getReferredUsers($userId);

// Estadísticas de usuario
$stats = $userRepository->getUserStats($userId);
```

### BingoRoomRepository
```php
// Buscar por código encriptado
$room = $bingoRoomRepository->findByEncryptedCode('ABC123');

// Salas por creador
$rooms = $bingoRoomRepository->findByCreator($creatorId);

// Estadísticas de sala
$stats = $bingoRoomRepository->getRoomSalesStats($roomId);

// Salas con premios pendientes
$rooms = $bingoRoomRepository->getRoomsWithPendingPrizes();
```

## Crear Nuevo Repository

### 1. Crear el Contrato
```php
// app/Repositories/Contracts/YourModelRepositoryContract.php
<?php

namespace App\Repositories\Contracts;

use App\Models\YourModel;
use Illuminate\Database\Eloquent\Collection;

interface YourModelRepositoryContract extends BaseRepositoryContract
{
    /**
     * Método específico para tu modelo
     */
    public function findByCustomField(string $value): ?YourModel;

    /**
     * Otro método específico
     */
    public function getBySpecialCriteria(array $criteria): Collection;
}
```

### 2. Crear la Implementación
```php
// app/Repositories/YourModelRepository.php
<?php

namespace App\Repositories;

use App\Models\YourModel;
use App\Repositories\Contracts\YourModelRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class YourModelRepository extends BaseRepository implements YourModelRepositoryContract
{
    protected function makeModel(): Model
    {
        return new YourModel();
    }

    public function findByCustomField(string $value): ?YourModel
    {
        return $this->model->where('custom_field', $value)->first();
    }

    public function getBySpecialCriteria(array $criteria): Collection
    {
        $query = $this->model->query();

        // Lógica específica aquí

        return $query->get();
    }
}
```

### 3. Registrar en ServiceProvider
```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(YourModelRepositoryContract::class, YourModelRepository::class);
```

## Principios a Seguir

### ✅ DO (Hacer)
- Usar contratos (interfaces) para inyección de dependencias
- Métodos con nombres descriptivos y autodocumentados
- Type hints en parámetros y retorno
- Documentación PHPDoc
- Separar lógica de consultas complejas del modelo
- Un repository por modelo principal

### ❌ DON'T (No Hacer)
- Inyectar implementaciones directas en controladores
- Métodos genéricos que requieren conocer lógica interna
- Lógica de negocio en repositories (eso va en services)
- Repositories gigantes con demasiadas responsabilidades
- Duplicar lógica entre repositories

## Testing

Todos los repositories deben tener tests unitarios:

```php
// tests/Unit/Repositories/YourModelRepositoryTest.php
class YourModelRepositoryTest extends TestCase
{
    private YourModelRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(YourModelRepositoryContract::class);
    }

    public function test_can_find_by_custom_field()
    {
        $model = YourModel::factory()->create(['custom_field' => 'test']);

        $found = $this->repository->findByCustomField('test');

        $this->assertNotNull($found);
        $this->assertEquals($model->id, $found->id);
    }
}
```

## Documentación Completa

Para documentación más detallada, ver: [docs/arquitectura/REPOSITORY_PATTERN.md](../../docs/arquitectura/REPOSITORY_PATTERN.md)