<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Menu\SidebarMenuService;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

echo "=== DEPURACIÓN DEL MENÚ SIDEBAR ===\n\n";

// Obtener usuario admin para pruebas
$user = User::whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->first();

if (!$user) {
    echo "❌ No se encontró usuario con rol admin\n";
    exit(1);
}

echo "👤 Usuario encontrado: {$user->username} ({$user->email})\n";
echo "📋 Roles del usuario:\n";
foreach ($user->roles as $role) {
    echo "  - {$role->name} ({$role->prefix}) - guard: {$role->guard_name}\n";
}

echo "\n🔐 Permisos del usuario (vía getAllPermissions):\n";
$permissions = $user->getAllPermissions();
foreach ($permissions as $permission) {
    echo "  - {$permission->name} (guard: {$permission->guard_name})\n";
}

echo "\n🎯 Verificación de permiso 'games.bingo':\n";
echo "  - can('games.bingo'): " . ($user->can('games.bingo') ? '✅ SÍ' : '❌ NO') . "\n";
echo "  - hasPermissionTo('games.bingo'): " . ($user->hasPermissionTo('games.bingo') ? '✅ SÍ' : '❌ NO') . "\n";
echo "  - hasPermissionTo('games.bingo', 'web'): " . ($user->hasPermissionTo('games.bingo', 'web') ? '✅ SÍ' : '❌ NO') . "\n";

// Verificar directamente en los permisos
$permissions = $user->getAllPermissions();
$hasGameBingo = collect($permissions)->contains('name', 'games.bingo');
echo "  - games.bingo en lista de permisos: " . ($hasGameBingo ? '✅ SÍ' : '❌ NO') . "\n";

// Verificar guard actual
echo "  - Guard actual del usuario: " . auth()->getDefaultDriver() . "\n";

// Verificar si el rol admin tiene el permiso asignado
echo "\n🔍 Verificación del rol 'admin':\n";
$adminRole = SpatieRole::where('name', 'admin')->where('guard_name', 'web')->first();
if ($adminRole) {
    echo "  - ✅ Rol admin encontrado\n";
    $adminPermissions = $adminRole->permissions;
    echo "  - Permisos del rol admin ({$adminPermissions->count()}):\n";
    foreach ($adminPermissions as $permission) {
        echo "    - {$permission->name}\n";
    }
    $hasGameBingo = $adminRole->hasPermissionTo('games.bingo');
    echo "  - Rol admin tiene 'games.bingo': " . ($hasGameBingo ? '✅ SÍ' : '❌ NO') . "\n";
} else {
    echo "  - ❌ Rol admin NO encontrado\n";
}

// Probar el servicio de menú
echo "\n📋 Menú generado por SidebarMenuService:\n";
$menuService = new SidebarMenuService();

// Verificar la estructura completa
echo "  - Estructura completa del menú:\n";
foreach ($menuService->menuStructure as $key => $item) {
    echo "    - {$key}: {$item['title']} (permiso: " . ($item['permission'] ?? 'null') . ")\n";
}

$menuItems = $menuService->generateMenu();
echo "  - Total de ítems en menú: " . $menuItems->count() . "\n";

// Depurar cada ítem del menú
echo "  - Depuración de cada ítem:\n";
foreach ($menuService->menuStructure as $key => $item) {
    $hasPermission = empty($item['permission']) || $user->can($item['permission']);
    echo "    - {$key}: ¿tiene permiso? " . ($hasPermission ? '✅' : '❌') . "\n";
    if (!$hasPermission && !empty($item['permission'])) {
        echo "      Permiso requerido: {$item['permission']}\n";
        echo "      Usuario puede: " . ($user->can($item['permission']) ? '✅ SÍ' : '❌ NO') . "\n";
    }
}

foreach ($menuItems as $key => $item) {
    echo "  - {$key}: {$item['title']}\n";
    if (isset($item['submenu'])) {
        foreach ($item['submenu'] as $subkey => $subitem) {
            echo "    └─ {$subkey}: {$subitem['title']}\n";
        }
    }
}

// Verificar específicamente el ítem game_bingo
echo "\n🎲 Verificación específica del ítem 'game_bingo':\n";
$gameBingoItem = $menuService->menuStructure['game_bingo'] ?? null;
if ($gameBingoItem) {
    echo "  - ✅ Ítem game_bingo existe en la estructura\n";
    echo "  - Título: {$gameBingoItem['title']}\n";
    echo "  - Permiso requerido: {$gameBingoItem['permission']}\n";
    echo "  - Usuario tiene permiso: " . ($user->can($gameBingoItem['permission']) ? '✅ SÍ' : '❌ NO') . "\n";
} else {
    echo "  - ❌ Ítem game_bingo NO existe en la estructura\n";
}

echo "\n=== FIN DE DEPURACIÓN ===\n";
