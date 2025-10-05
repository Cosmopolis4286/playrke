# Guía de Testing - PlayRKE Bingo

Guía completa para ejecutar y crear tests en el proyecto PlayRKE Bingo.

## 📋 Tabla de Contenidos

- [Introducción](#introducción)
- [Configuración](#configuración)
- [Ejecutar Tests](#ejecutar-tests)
- [Tests Creados](#tests-creados)
- [Escribir Nuevos Tests](#escribir-nuevos-tests)
- [Mejores Prácticas](#mejores-prácticas)

---

## 🎯 Introducción

El proyecto utiliza **PHPUnit** para testing de la API y **Laravel Event Fake** para tests de WebSockets.

### Cobertura de Tests

- ✅ **Autenticación** (13 tests)
- ✅ **Usuario** (14 tests)
- ✅ **Bingo** (18 tests)
- ✅ **WebSockets** (10 tests)
- ✅ **Integración** (5 tests end-to-end)

**Total**: 60+ tests implementados

---

## ⚙️ Configuración

### 1. Archivo phpunit.xml

El archivo `phpunit.xml` está configurado para usar SQLite en memoria:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Esto hace que los tests sean:
- ✅ Rápidos (no hay I/O de disco)
- ✅ Aislados (cada test usa una DB limpia)
- ✅ Sin afectar la DB de desarrollo

### 2. Variables de Entorno de Testing

El archivo `phpunit.xml` sobrescribe estas variables durante testing:

```xml
<env name="APP_ENV" value="testing"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="BROADCAST_DRIVER" value="log"/>
```

---

## 🏃 Ejecutar Tests

### Todos los Tests

```bash
php artisan test
```

o

```bash
./vendor/bin/phpunit
```

### Tests Específicos

```bash
# Solo tests de Feature
php artisan test --testsuite=Feature

# Solo tests de Unit
php artisan test --testsuite=Unit

# Un archivo específico
php artisan test tests/Feature/Api/AuthTest.php

# Un test específico
php artisan test --filter test_user_can_login
```

### Con Cobertura de Código

```bash
php artisan test --coverage
```

### Con Output Detallado

```bash
php artisan test --verbose
```

### En Paralelo (más rápido)

```bash
php artisan test --parallel
```

---

## 📦 Tests Creados

### 1. AuthTest.php (13 tests)

Ubicación: `tests/Feature/Api/AuthTest.php`

**Tests incluidos**:
- ✅ `user_can_register` - Registro exitoso
- ✅ `user_cannot_register_with_duplicate_email` - Validación de email único
- ✅ `user_can_login_with_email` - Login con email
- ✅ `user_can_login_with_username` - Login con username
- ✅ `user_cannot_login_with_invalid_credentials` - Credenciales inválidas
- ✅ `authenticated_user_can_get_their_info` - Obtener info de usuario
- ✅ `unauthenticated_user_cannot_get_user_info` - Sin autenticación
- ✅ `user_can_logout` - Logout exitoso
- ✅ `registration_requires_all_fields` - Validación de campos
- ✅ `password_must_be_confirmed` - Confirmación de contraseña
- ✅ `user_is_assigned_player_role_on_registration` - Rol por defecto

**Ejecutar**:
```bash
php artisan test tests/Feature/Api/AuthTest.php
```

### 2. UserTest.php (14 tests)

Ubicación: `tests/Feature/Api/UserTest.php`

**Tests incluidos**:
- ✅ Obtener perfil
- ✅ Actualizar perfil
- ✅ Cambiar contraseña
- ✅ Subir avatar
- ✅ Eliminar avatar
- ✅ Obtener/actualizar preferencias
- ✅ Obtener estadísticas
- ✅ Validaciones (tamaño de archivo, tipo de archivo)
- ✅ Protección de endpoints (guest no puede acceder)

**Ejecutar**:
```bash
php artisan test tests/Feature/Api/UserTest.php
```

### 3. BingoTest.php (18 tests)

Ubicación: `tests/Feature/Api/BingoTest.php`

**Tests incluidos**:
- ✅ Listar salas
- ✅ Filtrar salas por status
- ✅ Obtener detalle de sala
- ✅ Comprar cartones
- ✅ Validar saldo suficiente
- ✅ Obtener cartones del jugador
- ✅ Obtener sorteo actual
- ✅ Obtener números cantados
- ✅ Verificar ganador
- ✅ Obtener ganadores
- ✅ Historial de juegos
- ✅ Filtros de historial
- ✅ Validaciones (cantidad de cartones)
- ✅ Protección de rutas

**Ejecutar**:
```bash
php artisan test tests/Feature/Api/BingoTest.php
```

### 4. BingoEventsTest.php (10 tests)

Ubicación: `tests/Feature/WebSocket/BingoEventsTest.php`

**Tests incluidos**:
- ✅ Eventos se disparan correctamente
- ✅ Canales de broadcasting correctos
- ✅ Nombres de eventos correctos
- ✅ Payload de eventos correcto
- ✅ `ShouldBroadcastNow` implementado
- ✅ Todos los eventos (NumberCalled, GameStarted, GameEnded, WinnerDeclared, PlayerJoined)

**Ejecutar**:
```bash
php artisan test tests/Feature/WebSocket/BingoEventsTest.php
```

### 5. BingoGameFlowTest.php (5 tests)

Ubicación: `tests/Feature/Integration/BingoGameFlowTest.php`

**Tests de integración end-to-end**:
- ✅ Flujo completo de juego (registro → compra → juego → verificar ganador)
- ✅ Múltiples jugadores en misma sala
- ✅ Validación de moneda
- ✅ Filtrado de salas
- ✅ Flujo completo de autenticación

**Ejecutar**:
```bash
php artisan test tests/Feature/Integration/BingoGameFlowTest.php
```

---

## ✍️ Escribir Nuevos Tests

### Estructura Básica

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyNewTest extends TestCase
{
    use RefreshDatabase; // Limpia DB después de cada test

    /** @test */
    public function it_does_something()
    {
        // Arrange: Preparar datos
        $user = User::factory()->create();

        // Act: Ejecutar acción
        $response = $this->actingAs($user)
            ->getJson('/api/endpoint');

        // Assert: Verificar resultados
        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}
```

### Factories

Usar factories para crear datos de prueba:

```php
// Crear un usuario
$user = User::factory()->create();

// Con atributos específicos
$user = User::factory()->create([
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);

// Crear múltiples
$users = User::factory()->count(5)->create();
```

### Assertions Comunes

```php
// Status codes
$response->assertOk();              // 200
$response->assertCreated();         // 201
$response->assertNoContent();       // 204
$response->assertUnauthorized();    // 401
$response->assertForbidden();       // 403
$response->assertNotFound();        // 404
$response->assertStatus(422);       // Cualquier código

// JSON
$response->assertJson(['key' => 'value']);
$response->assertJsonFragment(['key' => 'value']);
$response->assertJsonStructure(['key1', 'key2']);
$response->assertJsonCount(5, 'items');

// Database
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);

// Auth
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);
$this->assertGuest();

// Validation
$response->assertJsonValidationErrors(['email', 'password']);
```

### Testing con Autenticación

```php
/** @test */
public function authenticated_user_can_access_endpoint()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/protected-endpoint');

    $response->assertOk();
}
```

### Testing de Eventos

```php
use Illuminate\Support\Facades\Event;

/** @test */
public function event_is_dispatched()
{
    Event::fake();

    // Código que dispara el evento
    broadcast(new MyEvent());

    Event::assertDispatched(MyEvent::class);

    Event::assertDispatched(MyEvent::class, function ($event) {
        return $event->data === 'expected';
    });
}
```

### Testing de Storage

```php
use Illuminate\Support\Facades\Storage;

/** @test */
public function file_is_uploaded()
{
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg');

    // Upload file
    $response = $this->post('/upload', ['photo' => $file]);

    // Assert file was stored
    Storage::disk('public')->assertExists('photos/photo.jpg');
}
```

---

## 🎯 Mejores Prácticas

### 1. Nombres Descriptivos

```php
// ✅ Bueno
/** @test */
public function user_can_purchase_multiple_bingo_cards_with_sufficient_balance()

// ❌ Malo
/** @test */
public function test1()
```

### 2. AAA Pattern (Arrange, Act, Assert)

```php
/** @test */
public function user_can_update_profile()
{
    // Arrange: Preparar
    $user = User::factory()->create(['first_name' => 'Old']);

    // Act: Actuar
    $response = $this->actingAs($user)
        ->putJson('/api/user/profile', ['first_name' => 'New']);

    // Assert: Afirmar
    $response->assertOk();
    $this->assertEquals('New', $user->fresh()->first_name);
}
```

### 3. Un Test, Un Concepto

```php
// ✅ Bueno - Tests separados
/** @test */
public function user_can_login_with_email() { /* ... */ }

/** @test */
public function user_can_login_with_username() { /* ... */ }

// ❌ Malo - Múltiples conceptos en un test
/** @test */
public function login_works() {
    // Test con email
    // Test con username
    // Test con credenciales inválidas
}
```

### 4. Limpiar Después de Cada Test

Usar `RefreshDatabase` para tener una DB limpia:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    // Cada test empieza con DB vacía
}
```

### 5. Factories sobre Creación Manual

```php
// ✅ Bueno
$user = User::factory()->create();

// ❌ Malo
$user = new User();
$user->name = 'Test';
$user->email = 'test@test.com';
// ... muchas líneas más
$user->save();
```

### 6. Tests Independientes

Cada test debe poder ejecutarse solo:

```php
// ✅ Bueno
/** @test */
public function test_a()
{
    $user = User::factory()->create(); // Crea sus propios datos
    // ...
}

// ❌ Malo
protected $user; // Compartido entre tests

public function setUp()
{
    parent::setUp();
    $this->user = User::factory()->create(); // ¡Peligroso!
}
```

### 7. Fake External Services

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/** @test */
public function it_sends_email()
{
    Mail::fake();

    // Código que envía email

    Mail::assertSent(WelcomeEmail::class);
}
```

---

## 📊 Coverage

### Generar Reporte de Cobertura

```bash
php artisan test --coverage --min=80
```

Esto falla si la cobertura es menor al 80%.

### Ver Reporte HTML

```bash
./vendor/bin/phpunit --coverage-html coverage
```

Abre `coverage/index.html` en el navegador.

---

## 🐛 Debugging Tests

### Ver Output Detallado

```bash
php artisan test --verbose
```

### Ver Queries SQL

```php
/** @test */
public function my_test()
{
    DB::enableQueryLog();

    // Código que ejecuta queries

    dd(DB::getQueryLog());
}
```

### Usar dump() y dd()

```php
/** @test */
public function my_test()
{
    $response = $this->getJson('/api/endpoint');

    $response->dump();      // Ver response
    $response->dumpHeaders();  // Ver headers
    $response->dumpSession();  // Ver session

    dd($response->json());  // Dump and die
}
```

---

## ✅ Checklist de Testing

Antes de hacer commit:

- [ ] Todos los tests pasan (`php artisan test`)
- [ ] Coverage mínimo alcanzado (80%)
- [ ] Tests nuevos para funcionalidades nuevas
- [ ] Tests tienen nombres descriptivos
- [ ] Tests son independientes
- [ ] No hay `dd()` o `dump()` olvidados
- [ ] Factories usadas en lugar de creación manual

---

## 📝 Convenciones

### Nomenclatura

```php
// Método de test
public function user_can_do_something() // Snake case

// Clase de test
class UserTest extends TestCase // PascalCase + Test suffix

// Archivo
UserTest.php // Igual que la clase
```

### Organización

```
tests/
├── Feature/          # Tests de endpoints/features
│   ├── Api/
│   │   ├── AuthTest.php
│   │   ├── UserTest.php
│   │   └── BingoTest.php
│   ├── WebSocket/
│   │   └── BingoEventsTest.php
│   └── Integration/
│       └── BingoGameFlowTest.php
└── Unit/             # Tests unitarios
    └── Services/
        └── MyServiceTest.php
```

---

## 🚀 CI/CD Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: php artisan test --coverage --min=80
```

---

## 📚 Recursos Adicionales

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Factories](https://laravel.com/docs/database-testing#defining-model-factories)
- [HTTP Tests](https://laravel.com/docs/http-tests)

---

**Última actualización**: 2025-10-05
**Versión**: 1.0.0
**Tests totales**: 60+
