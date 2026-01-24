# Plan de Refactorización de Modelos con Patrón Repository

## 🎯 Objetivo

Completar la refactorización sistemática de todos los modelos del proyecto aplicando el **patrón Repository**, siguiendo las mejores prácticas de programación, principios SOLID y clean code establecidas en las instrucciones del proyecto.

---

## 📊 Estado Actual

### Progreso General
- **Total de modelos**: 74
- **Modelos refactorizados**: 67 (90.5%)
- **Modelos pendientes**: 7 (9.5%)
- **Tarea en progreso**: T006 - Refactorización Completa de Models y Repositories

### Estado de la Tarea T006
- **Estado**: 🟡 En Progreso
- **Progreso**: 90.5% completado
- **Tiempo estimado restante**: ~38 minutos
- **Último modelo completado**: User, Wallet, Setting
- **Siguiente modelo**: SellerRoomLimits

---

## 🔄 Modelos Restantes por Refactorizar

### Lista en Orden Alfabético Estricto

1. **SellerRoomLimits** 🟡 *En progreso*
   - **Ubicación**: `app/Models/Users/Roles/Seller/SellerRoomLimits.php`
   - **Problemas detectados**:
     - Método `getGameRoomValueBySeller()` con errores de sintaxis
     - Variables no definidas (`$game`, `$room`, `$seller`)
     - Relación `Seller()` apunta a campo incorrecto
     - Inconsistencia en nomenclatura de campos
   - **Prioridad**: 🔥 Crítica (contiene errores)

2. **TimeZone**
   - **Ubicación**: `app/Models/Users/Shared/TimeZone.php`
   - **Estimado**: 5 minutos

3. **Transaction**
   - **Ubicación**: `app/Models/Games/Shared/Transactions/Transaction.php`
   - **Estimado**: 5 minutos

4. **Treasurer**
   - **Ubicación**: `app/Models/Users/Roles/Treasurer/Treasurer.php`
   - **Estimado**: 5 minutos

5. **TreasurerCountryCurrency**
   - **Ubicación**: `app/Models/Users/Roles/Treasurer/TreasurerCountryCurrency.php`
   - **Estimado**: 5 minutos

6. **UniqueCode**
   - **Ubicación**: `app/Models/Users/Shared/UniqueCode.php`
   - **Estimado**: 5 minutos

7. **UserPurchaseCode**
   - **Ubicación**: `app/Models/Users/Roles/Player/External/UserPurchaseCode.php`
   - **Estimado**: 5 minutos

---

## 🏗️ Metodología de Refactorización

### Patrón Establecido

Cada modelo debe seguir la siguiente estructura:

```
📁 app/
├── 📁 Models/
│   └── [NombreModelo].php          # Solo relaciones, accessors, scopes
├── 📁 Repositories/
│   ├── 📁 [Categoria]/
│   │   ├── 📁 Contracts/
│   │   │   └── [Nombre]RepositoryContract.php   # Interface
│   │   └── [Nombre]Repository.php               # Implementación
└── 📁 Providers/
    └── RepositoryServiceProvider.php            # Inyección de dependencias
```

### Proceso por Modelo (5 pasos)

#### 1. 📋 Análisis del Modelo Actual
- Revisar migración para entender estructura
- Identificar relaciones existentes
- Detectar consultas complejas en el modelo
- Documentar problemas y errores

#### 2. 📝 Crear Contrato del Repository
- Definir interface con métodos específicos del dominio
- Usar **PHPDoc completo en español**
- Seguir nomenclatura descriptiva en **inglés**
- Extender `BaseRepositoryInterface`

```php
<?php

namespace App\Repositories\[Categoria]\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de [descripción del modelo].
 *
 * Define las operaciones específicas para [descripción de funcionalidad],
 * incluyendo [lista de operaciones principales].
 */
interface [Nombre]RepositoryContract extends BaseRepositoryInterface
{
    /**
     * [Descripción del método en español].
     *
     * @param string $parametro Descripción del parámetro
     * @return Collection<[Modelo]> Descripción del retorno
     */
    public function metodoCamelCase(string $parametro): Collection;
}
```

#### 3. 🏗️ Implementar Repository
- Crear clase que implemente el contrato
- Extender `BaseRepository`
- Mover **TODA** la lógica de consultas del modelo
- Implementar métodos específicos del dominio
- Usar **PHPDoc completo en español**

```php
<?php

namespace App\Repositories\[Categoria];

use App\Models\[Modelo];
use App\Repositories\BaseRepository;
use App\Repositories\[Categoria]\Contracts\[Nombre]RepositoryContract;

/**
 * Repositorio para la gestión de [descripción].
 *
 * Implementa las operaciones específicas para el modelo [Modelo],
 * incluyendo [lista de funcionalidades principales].
 */
class [Nombre]Repository extends BaseRepository implements [Nombre]RepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param [Modelo] $model Instancia del modelo [Modelo]
     */
    public function __construct([Modelo] $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function metodoCamelCase(string $parametro): Collection
    {
        return $this->model->where('campo', $parametro)->get();
    }
}
```

#### 4. 🧹 Refactorizar Modelo
- **ELIMINAR** todas las consultas complejas
- **MANTENER** solo:
  - Propiedades (`$fillable`, `$table`, etc.)
  - Relaciones (`belongsTo`, `hasMany`, etc.)
  - Accessors y Mutators
  - Scopes simples
  - Método `boot()` para Snowflake ID

```php
<?php

namespace App\Models\[Categoria];

use App\Traits\HasSnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para [descripción del modelo].
 *
 * Representa [descripción de la entidad] en el sistema,
 * gestionando [descripción de responsabilidades].
 */
class [Modelo] extends Model
{
    use HasSnowflakeId;

    protected $table = "tabla_name";
    protected $fillable = ['campo1', 'campo2'];

    /**
     * Relación con [ModeloRelacionado].
     */
    public function relacion(): BelongsTo
    {
        return $this->belongsTo(ModeloRelacionado::class, 'campo_foraneo');
    }
}
```

#### 5. ⚙️ Registrar en ServiceProvider
- Agregar binding en `RepositoryServiceProvider.php`
- Usar inyección de dependencias

```php
// En RepositoryServiceProvider.php
$this->app->bind(
    [Nombre]RepositoryContract::class,
    [Nombre]Repository::class
);
```

---

## 📚 Mejores Prácticas Aplicadas

### Principios SOLID

#### Single Responsibility Principle (SRP)
- **Modelos**: Solo representar entidades y relaciones
- **Repositories**: Solo gestionar consultas y persistencia
- **Contracts**: Solo definir interfaces

#### Open/Closed Principle (OCP)
- Uso de interfaces permite extensión sin modificación
- Nuevos repositories pueden implementar contratos existentes

#### Liskov Substitution Principle (LSP)
- Todas las implementaciones respetan los contratos
- BaseRepository proporciona funcionalidad común

#### Interface Segregation Principle (ISP)
- Contratos específicos por dominio
- No depender de métodos no utilizados

#### Dependency Inversion Principle (DIP)
- Controladores dependen de abstracciones (contratos)
- Inyección de dependencias automática

### Clean Code

#### Nomenclatura
```php
// ✅ Correcto - Inglés descriptivo
public function getActiveUsersByCountry(string $countryId): Collection

// ❌ Incorrecto - Español o abreviado
public function obtenerUsuariosActivos($pais): Collection
public function getActUsrs($c): Collection
```

#### Funciones Pequeñas
```php
// ✅ Correcto - Una responsabilidad
public function getActiveUsers(): Collection
{
    return $this->model->where('status', true)->get();
}

// ❌ Incorrecto - Múltiples responsabilidades
public function getUsersAndUpdateLastLogin(): Collection
{
    $users = $this->model->where('status', true)->get();
    $this->updateLastLoginTime();
    return $users;
}
```

#### Documentación PHPDoc
```php
/**
 * Obtiene usuarios activos filtrados por país.
 *
 * Retorna una colección de usuarios que tienen estado activo
 * y pertenecen al país especificado, ordenados por nombre.
 *
 * @param string $countryId ID del país a filtrar
 * @param bool $includeInactive Incluir usuarios inactivos
 * @return Collection<User> Colección de usuarios filtrados
 * @throws \InvalidArgumentException Si el país no existe
 *
 * @example
 * $users = $repository->getActiveUsersByCountry('ve', false);
 */
public function getActiveUsersByCountry(string $countryId, bool $includeInactive = false): Collection
```

---

## 🔒 Consideraciones de Seguridad

### Validación de Datos
```php
/**
 * Valida los datos antes de crear/actualizar.
 *
 * @param array $data Datos a validar
 * @param string|null $excludeId ID a excluir de validaciones
 * @return array Array de errores de validación
 */
public function validateData(array $data, ?string $excludeId = null): array
{
    $errors = [];

    // Validar campos requeridos
    if (empty($data['email'])) {
        $errors['email'] = 'El email es requerido';
    }

    // Validar formato
    if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El formato del email es inválido';
    }

    return $errors;
}
```

### Sanitización
```php
/**
 * Sanitiza los datos de entrada.
 *
 * @param array $data Datos a sanitizar
 * @return array Datos sanitizados
 */
private function sanitizeData(array $data): array
{
    if (isset($data['email'])) {
        $data['email'] = trim(strtolower($data['email']));
    }

    if (isset($data['name'])) {
        $data['name'] = trim(strip_tags($data['name']));
    }

    return $data;
}
```

---

## 📈 Estimaciones de Tiempo

### Por Modelo
- **SellerRoomLimits**: 8 minutos (corrección de errores)
- **Modelos restantes**: 5 minutos c/u
- **Total estimado**: 38 minutos

### Desglose de Tiempo por Paso
1. **Análisis**: 1 minuto
2. **Crear contrato**: 2 minutos
3. **Implementar repository**: 3 minutos
4. **Refactorizar modelo**: 2 minutos
5. **Registrar en ServiceProvider**: 30 segundos

---

## ✅ Criterios de Aceptación

### Por Modelo Completado
- [ ] Contrato creado con PHPDoc completo en español
- [ ] Repository implementado con todos los métodos
- [ ] Modelo refactorizado (solo relaciones y propiedades)
- [ ] ServiceProvider actualizado
- [ ] No hay errores de sintaxis
- [ ] Nomenclatura consistente (código en inglés)
- [ ] Seguimiento actualizado en `seguimiento-tareas.md`

### Al Completar T006
- [ ] 74/74 modelos refactorizados
- [ ] Arquitectura consistente en todo el proyecto
- [ ] Separación clara de responsabilidades
- [ ] Documentación completa
- [ ] Tests funcionales (si existen)
- [ ] Build exitoso sin errores

---

## 🚀 Beneficios Esperados

### Arquitectura
- **Separación de responsabilidades** clara
- **Escalabilidad** mejorada
- **Mantenibilidad** simplificada
- **Testing** más fácil con inyección de dependencias

### Calidad de Código
- **Consistencia** en toda la aplicación
- **Reutilización** de código común
- **Documentación** completa y actualizada
- **Principios SOLID** aplicados

### Desarrollo
- **Productividad** mejorada
- **Onboarding** más rápido para nuevos desarrolladores
- **Debugging** simplificado
- **Refactoring** futuro más seguro

---

## 📋 Checklist de Finalización

### Verificaciones Técnicas
- [ ] Todos los modelos tienen su repository correspondiente
- [ ] Todos los contracts están implementados
- [ ] ServiceProvider registra todos los bindings
- [ ] No hay consultas complejas en los modelos
- [ ] PHPDoc completo en todos los archivos

### Verificaciones de Calidad
- [ ] Nomenclatura consistente (inglés para código)
- [ ] Comentarios en español
- [ ] Principios SOLID aplicados
- [ ] No hay código duplicado
- [ ] Validación y sanitización implementada

### Verificaciones de Proyecto
- [ ] `seguimiento-tareas.md` actualizado
- [ ] T006 marcada como 🟢 Finalizada
- [ ] Métricas del proyecto actualizadas
- [ ] Build ejecutado sin errores
- [ ] Documentación actualizada

---

## 📞 Notas Adicionales

### Convenciones del Proyecto
- **Idioma de comentarios**: Español
- **Idioma de código**: Inglés
- **Formato de commits**: `feat(models): refactorizar [Modelo] con patrón Repository`
- **Actualización obligatoria**: `seguimiento-tareas.md` en cada cambio

### Recursos de Referencia
- `instrucciones.md` - Pautas generales de desarrollo
- `seguimiento-tareas.md` - Estado actual del proyecto
- `docs/arquitectura/REPOSITORY_PATTERN.md` - Documentación del patrón

---

*Documento generado el 2025-09-16 - Plan de refactorización sistemática siguiendo mejores prácticas de desarrollo*