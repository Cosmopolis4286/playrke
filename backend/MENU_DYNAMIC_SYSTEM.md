# Sistema de Menú Dinámico Basado en Permisos

## Overview

Se ha implementado un sistema de menú dinámico que se adapta según los permisos del usuario, eliminando la necesidad de mantener múltiples archivos de sidebar para cada rol.

## Arquitectura

### 1. SidebarMenuService
**Ubicación**: `app/Services/Menu/SidebarMenuService.php`

Servicio central que gestiona la lógica del menú:
- Define la estructura completa del menú con permisos requeridos
- Filtra elementos según permisos del usuario
- Maneja estados activos y clases CSS

### 2. Componentes Blade
- **`dynamic.blade.php`**: Componente principal usando el servicio
- **`dynamic-simple.blade.php`**: Versión simplificada usando helpers
- **`sidebar-selector.blade.php`**: Selector con compatibilidad hacia atrás

### 3. Helpers Globales
**Ubicación**: `app/Helpers/MenuHelpers.php`

Funciones disponibles en todas las vistas:
- `can_access_menu($permission)`: Verifica acceso a menú
- `get_user_menu()`: Obtiene menú filtrado
- `is_menu_active($route)`: Verifica si ruta está activa
- `get_menu_classes($route)`: Obtiene clases CSS
- `get_role_display_name()`: Nombre para mostrar del rol

### 4. Middleware
**Ubicación**: `app/Http/Middleware/LoadUserPermissions.php`

Carga permisos y roles del usuario en caché para mejorar rendimiento.

## Uso

### Activar menú dinámico

En `sidebar-selector.blade.php`:
```php
$useDynamicMenu = true; // Activa sistema nuevo
$useDynamicMenu = false; // Usa sistema antiguo
```

### Estructura del menú

La estructura se define en `SidebarMenuService::$menuStructure`:

```php
'menu_key' => [
    'title' => 'Título del Menú',
    'icon' => 'fas fa-icon',
    'route' => 'route.name',
    'permission' => 'permission.name', // null = accesible para todos
    'submenu' => [
        'submenu_key' => [
            'title' => 'Subtítulo',
            'route' => 'submenu.route',
            'permission' => 'permission.name',
        ],
    ],
],
```

### Agregar nuevo elemento al menú

1. Agregar la estructura en `SidebarMenuService::$menuStructure`
2. Asegurar que el permiso exista en `PermissionSeeder.php`
3. Asignar permiso a los roles en `RolePermissionSeeder.php`

### Verificar permisos en vistas

```php
@if(can_access_menu('users.view'))
    <a href="{{ route('admin.users') }}">Usuarios</a>
@endif
```

### Personalizar para usuario específico

```php
@if(auth()->user()->hasRole('superadmin'))
    <!-- Contenido solo para superadmin -->
@endif
```

## Permisos Definidos

### Gestión de Usuarios
- `users.view`, `users.create`, `users.edit`, `users.delete`, `users.manage`
- `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, `roles.assign`

### Gestión de Comercios
- `commerces.view`, `commerces.create`, `commerces.edit`, `commerces.delete`, `commerces.approve`

### Gestión Financiera
- `treasury.view`, `treasury.manage`, `treasury.approve.transactions`, `treasury.reports`
- `exchange.view`, `exchange.manage`, `exchange.update`

### Juegos y Reportes
- `games.view`, `games.create`, `games.edit`, `games.delete`, `games.manage`
- `reports.view`, `reports.generate`, `reports.export`, `reports.financial`, `reports.gaming`

### Sistema
- `system.settings`, `system.maintenance`, `system.logs`, `system.backup`
- `audit.view`, `audit.export`
- `superadmin.access` (acceso total)

## Ventajas

1. **Mantenimiento Centralizado**: Un solo lugar para modificar el menú
2. **Basado en Permisos**: Flexible y granular
3. **Rendimiento**: Caché de permisos para mejorar velocidad
4. **Compatibilidad**: Sistema antiguo disponible como fallback
5. **Escalable**: Fácil agregar nuevos elementos y permisos

## Migración desde Sistema Antiguo

1. **Identificar permisos necesarios** para cada elemento de menú actual
2. **Mapear roles existentes** a nuevos permisos
3. **Probar con diferentes usuarios** para asegurar visibilidad correcta
4. **Eliminar archivos antiguos** cuando esté seguro del funcionamiento

## Registro del Middleware

Agregar al grupo `web` en `app/Http/Kernel.php`:

```php
'web' => [
    // ... otros middleware
    \App\Http\Middleware\LoadUserPermissions::class,
],
```

## Actualización de Composer

Ejecutar después de agregar helpers:
```bash
composer dump-autoload
```
