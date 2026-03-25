<?php

if (!function_exists('can_access_menu')) {
    /**
     * Verifica si el usuario actual puede acceder a un ítem de menú
     * basado en sus permisos
     */
    function can_access_menu(string $permission): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Superadmin tiene acceso a todo
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Si no se requiere permiso específico, permitir acceso
        if (empty($permission)) {
            return true;
        }

        return $user->can($permission);
    }
}

if (!function_exists('get_user_menu')) {
    /**
     * Obtiene el menú filtrado según los permisos del usuario
     */
    function get_user_menu(): array
    {
        $menuService = app(\App\Services\Menu\SidebarMenuService::class);
        return $menuService->generateMenu()->toArray();
    }
}

if (!function_exists('is_menu_active')) {
    /**
     * Verifica si una ruta de menú está activa
     */
    function is_menu_active(string $route): bool
    {
        $menuService = app(\App\Services\Menu\SidebarMenuService::class);
        return $menuService->isActive($route);
    }
}

if (!function_exists('get_menu_classes')) {
    /**
     * Obtiene las clases CSS para un ítem de menú
     */
    function get_menu_classes(string $route): string
    {
        $menuService = app(\App\Services\Menu\SidebarMenuService::class);
        return $menuService->getMenuItemClasses($route);
    }
}

if (!function_exists('get_role_display_name')) {
    /**
     * Obtiene el nombre para mostrar del rol del usuario
     */
    function get_role_display_name(): string
    {
        $menuService = app(\App\Services\Menu\SidebarMenuService::class);
        return $menuService->getUserRoleDisplay();
    }
}
