# Visibilidad del sidebar por rol

Sistema que reemplaza el array hardcodeado `exclude_roles` de
`SidebarMenuService::$menuStructure` por una fuente de verdad editable en
base de datos, administrable desde `/sidebar-menu` (solo superadmin).

## Motivación

Antes, ocultar o mostrar un item/submenú del sidebar para un rol específico
requería editar `$menuStructure` en código y desplegar. La estructura del
árbol (título, ícono, ruta, permiso Spatie requerido) sigue viviendo en PHP
— eso no cambió — pero la decisión de **qué rol ve qué item** ahora es un
dato en BD, editable desde una matriz visual en tiempo real.

## Arquitectura

```
$menuStructure (PHP, fuente de verdad de title/icon/route/permission)
        │
        ├── SidebarMenuService::getFlatMenuItemsForMatrix()
        │       └── aplana el árbol a [{key, parent_key, title, permission}]
        │           para alimentar la matriz de administración
        │
        └── SidebarMenuService::isVisibleForRoles(itemKey, userRoles)
                └── consulta sidebar_menu_visibilities (BD)
                    "lista de excepciones" — sin fila = visible por defecto
```

- **Tabla `sidebar_menu_visibilities`**: `item_key` (string, `"padre"` o
  `"padre.hijo"`), `role_id` (FK a `roles`), `is_visible` (boolean, default
  `true`). Único índice compuesto `(item_key, role_id)`.
- **Modelo**: `App\Models\SidebarMenuVisibility`.
- **Resolución de rol**: se indexa por `Role::name` (normalizado a
  minúsculas por el mutator del modelo), **no** por `Role::prefix` — este
  último vive en BD en mayúsculas (`SUPERADMIN`) y no es el campo que
  `$user->getRoleNames()` devuelve ni el que `exclude_roles` comparaba.
- **Superadmin no está blindado**: a diferencia de la Matriz de Permisos
  (donde superadmin siempre aparece marcado y no editable), aquí el
  checkbox de superadmin es editable igual que cualquier otro rol — replica
  el comportamiento que ya existía con `exclude_roles` (ej. "Mis Partidas"
  ya excluía a superadmin antes de esta feature).
- **`permission` (Spatie) no se toca**: sigue siendo el gate real de
  autorización de ruta. Esta tabla solo decide si el link *aparece* en el
  sidebar — no habilita ni deshabilita acceso real a la ruta.

## Componentes

| Capa | Archivo |
|---|---|
| Migración | `database/migrations/2026_07_22_02_create_sidebar_menu_visibilities_table.php` |
| Modelo | `app/Models/SidebarMenuVisibility.php` |
| Seeder | `database/seeders/SidebarMenuVisibilitySeeder.php` |
| Service (menú) | `app/Services/Menu/SidebarMenuService.php` — `isVisibleForRoles()`, `getFlatMenuItemsForMatrix()` |
| Service (admin) | `app/Services/Roles/Admin/SidebarMenuVisibilityService.php` |
| Controller | `app/Http/Controllers/Roles/SidebarMenuVisibilityController.php` |
| Policy | `app/Policies/Roles/Admin/SidebarMenuVisibilityPolicy.php` (requiere `superadmin`) |
| Rutas | `routes/modules/admin.php`, prefijo `sidebar-menu` |
| Tipos TS | `resources/js/types/roles/admin/sidebar-menu.ts` |
| Composable | `resources/js/composables/roles/admin/sidebarMenu/useSidebarMenuVisibility.ts` |
| Matriz (UI) | `resources/js/Components/Roles/Admin/SidebarMenuVisibilityMatrix.vue` |
| Página | `resources/js/Pages/Roles/Admin/SidebarMenu/Index.vue` |
| Helper compartido | `resources/js/utils/roles/roleDisplay.ts` (orden/formato de roles, compartido con `PermissionMatrix.vue`) |

Patrón de UI: matriz item×rol con cambios pendientes (`pendingChanges`)
batcheados y guardado explícito — mismo patrón ya usado por
`PermissionMatrix.vue` + `usePermissions.ts` para la matriz rol×permiso.

## Semántica de "lista de excepciones"

Igual que `modules.is_active`: **ausencia de fila para `(item_key, role_id)`
= visible por defecto**. Un item nuevo agregado a `$menuStructure` no
requiere ninguna migración de datos para ser visible a todos los roles.
Solo se siembran/crean filas para las combinaciones donde el item debe
ocultarse a un rol específico.

## Cómo agregar un item nuevo al sidebar

1. Agregar el item/submenú en `SidebarMenuService::$menuStructure` como
   siempre (title, icon, route, permission).
2. No hace falta tocar `sidebar_menu_visibilities` — el item es visible
   para todos los roles con el `permission` correspondiente hasta que un
   superadmin lo restrinja manualmente desde `/sidebar-menu`.

## Verificación

```bash
# Confirmar que las 3 rutas están registradas
php artisan route:list --path=sidebar-menu

# Probar el pipeline completo vía Tinker (sin depender del navegador)
php artisan tinker --execute="
\$role = App\Models\Role::where('name', 'player')->first();
App\Models\SidebarMenuVisibility::updateOrCreate(
    ['item_key' => 'dashboard', 'role_id' => \$role->id],
    ['is_visible' => false]
);
\$user = App\Models\User::whereHas('roles', fn(\$q) => \$q->where('name', 'player'))->first();
auth()->login(\$user);
\$menu = app(App\Services\Menu\SidebarMenuService::class)->generateMenu();
echo \$menu->has('dashboard') ? 'VISIBLE (fallo)' : 'OCULTO (ok)';
"
```
