<!--
  Sidebar de navegación para administradores.

  Menu lateral con navegación jerárquica, estados activos
  y gestión de permisos por rol.
-->

<template>
  <aside class="admin-sidebar w-64 h-full bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col">
    <!-- Logo y branding -->
    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
          <Icon name="lucide:shield-check" class="w-5 h-5 text-white" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">PlayRKE</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Panel Admin</p>
        </div>
      </div>
    </div>

    <!-- Navegación principal -->
    <nav class="flex-1 overflow-y-auto py-4">
      <div class="px-3 space-y-1">
        <!-- Dashboard -->
        <SidebarItem
          to="/admin"
          icon="lucide:layout-dashboard"
          label="Dashboard"
          :exact="true"
        />

        <!-- Gestión de Usuarios -->
        <SidebarGroup
          label="Gestión de Usuarios"
          :open="userMenuOpen"
          @toggle="userMenuOpen = !userMenuOpen"
        >
          <SidebarItem
            to="/admin/users"
            icon="lucide:users"
            label="Todos los Usuarios"
            permission="admin.users.view"
          />
          <SidebarItem
            to="/admin/users/admins"
            icon="lucide:shield"
            label="Administradores"
            permission="admin.users.view"
          />
          <SidebarItem
            to="/admin/users/players"
            icon="lucide:gamepad-2"
            label="Jugadores"
            permission="admin.users.view"
          />
          <SidebarItem
            to="/admin/users/roles"
            icon="lucide:lock"
            label="Roles y Permisos"
            permission="admin.users.edit"
          />
        </SidebarGroup>

        <!-- Gestión de Juegos -->
        <SidebarGroup
          label="Gestión de Juegos"
          :open="gameMenuOpen"
          @toggle="gameMenuOpen = !gameMenuOpen"
        >
          <SidebarItem
            to="/admin/games/rooms"
            icon="lucide:home"
            label="Salas de Bingo"
            permission="admin.games.config"
          />
          <SidebarItem
            to="/admin/games/matches"
            icon="lucide:play"
            label="Partidas Activas"
            permission="admin.games.config"
          />
          <SidebarItem
            to="/admin/games/prizes"
            icon="lucide:trophy"
            label="Premios y Jackpots"
            permission="admin.games.config"
          />
          <SidebarItem
            to="/admin/games/settings"
            icon="lucide:settings"
            label="Configuración"
            permission="admin.games.config"
          />
        </SidebarGroup>

        <!-- Finanzas -->
        <SidebarGroup
          label="Finanzas"
          :open="financeMenuOpen"
          @toggle="financeMenuOpen = !financeMenuOpen"
        >
          <SidebarItem
            to="/admin/finance/transactions"
            icon="lucide:credit-card"
            label="Transacciones"
            permission="admin.finance.view"
          />
          <SidebarItem
            to="/admin/finance/deposits"
            icon="lucide:arrow-down"
            label="Depósitos"
            permission="admin.finance.view"
          />
          <SidebarItem
            to="/admin/finance/withdrawals"
            icon="lucide:arrow-up"
            label="Retiros"
            permission="admin.finance.view"
          />
          <SidebarItem
            to="/admin/finance/reports"
            icon="lucide:bar-chart-3"
            label="Reportes"
            permission="admin.reports.view"
          />
        </SidebarGroup>

        <!-- Soporte -->
        <SidebarGroup
          label="Soporte"
          :open="supportMenuOpen"
          @toggle="supportMenuOpen = !supportMenuOpen"
        >
          <SidebarItem
            to="/admin/support/tickets"
            icon="lucide:help-circle"
            label="Tickets"
            permission="admin.support.view"
          />
          <SidebarItem
            to="/admin/support/chat"
            icon="lucide:message-circle"
            label="Chat en Vivo"
            permission="admin.support.view"
          />
          <SidebarItem
            to="/admin/support/faq"
            icon="lucide:book-open"
            label="FAQ"
            permission="admin.support.edit"
          />
        </SidebarGroup>

        <!-- Reportes y Analíticas -->
        <SidebarItem
          to="/admin/analytics"
          icon="lucide:trending-up"
          label="Analíticas"
          permission="admin.reports.view"
        />

        <!-- Sistema -->
        <SidebarGroup
          label="Sistema"
          :open="systemMenuOpen"
          @toggle="systemMenuOpen = !systemMenuOpen"
        >
          <SidebarItem
            to="/admin/system/logs"
            icon="lucide:file-text"
            label="Logs del Sistema"
            permission="admin.system.view"
          />
          <SidebarItem
            to="/admin/system/maintenance"
            icon="lucide:wrench"
            label="Mantenimiento"
            permission="admin.system.edit"
          />
          <SidebarItem
            to="/admin/system/backups"
            icon="lucide:database"
            label="Respaldos"
            permission="admin.system.edit"
          />
        </SidebarGroup>
      </div>
    </nav>

    <!-- Footer del sidebar -->
    <div class="p-4 border-t border-slate-200 dark:border-slate-700">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-slate-200 dark:bg-slate-600 rounded-full flex items-center justify-center">
          <Icon name="lucide:user" class="w-4 h-4 text-slate-600 dark:text-slate-300" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
            {{ user?.name || 'Admin' }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
            {{ adminLevel }}
          </p>
        </div>
        <button
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          @click="logout"
        >
          <Icon name="lucide:log-out" class="w-4 h-4" />
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
// Composables
const { user, logout, hasPermission } = useAuth()
const { getAdminLevel } = useAuthStore()

// Estados de menús
const userMenuOpen = ref(true)
const gameMenuOpen = ref(true)
const financeMenuOpen = ref(false)
const supportMenuOpen = ref(false)
const systemMenuOpen = ref(false)

// Computed
const adminLevel = computed(() => {
  const level = getAdminLevel()
  const levelNames = {
    super: 'Super Admin',
    admin: 'Administrador',
    moderator: 'Moderador',
    support: 'Soporte'
  }
  return levelNames[level] || 'Admin'
})
</script>

<style scoped>
.admin-sidebar {
  transition: all 0.3s ease;
}

.admin-sidebar::-webkit-scrollbar {
  width: 4px;
}

.admin-sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.admin-sidebar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 2px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}
</style>