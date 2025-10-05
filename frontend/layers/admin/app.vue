<template>
  <div class="admin-app min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Header de administración -->
    <AdminHeader />

    <!-- Layout principal con sidebar -->
    <div class="flex">
      <!-- Sidebar de navegación -->
      <AdminSidebar class="hidden lg:block" />

      <!-- Contenido principal -->
      <main class="flex-1 lg:ml-64">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
          <!-- Breadcrumb -->
          <AdminBreadcrumb class="mb-6" />

          <!-- Contenido de la página -->
          <NuxtPage />
        </div>
      </main>
    </div>

    <!-- Notificaciones de admin -->
    <AdminNotifications />

    <!-- Modales de admin -->
    <AdminModals />
  </div>
</template>

<script setup lang="ts">
/**
 * Layout principal para la sección de administración.
 *
 * Proporciona la estructura base con header, sidebar, breadcrumb
 * y área de contenido para todas las páginas de administración.
 */

// Meta configuración para admin
useHead({
  titleTemplate: '%s | Admin - PlayRKE',
  meta: [
    { name: 'robots', content: 'noindex, nofollow' },
    { name: 'description', content: 'Panel de administración de PlayRKE' }
  ],
  bodyAttrs: {
    class: 'admin-layout'
  }
})

// Configuración del color mode para admin (tema azul)
const colorMode = useColorMode()

// Store de autenticación de admin
const { isAuthenticated, user, logout } = useAdminAuth()

// Verificar autenticación al montar
onMounted(async () => {
  if (!isAuthenticated.value) {
    await navigateTo('/admin/login')
  }
})

// Manejar cierre de sesión
const handleLogout = async () => {
  await logout()
  await navigateTo('/admin/login')
}

// Configuración de SEO para admin
useSeoMeta({
  robots: 'noindex, nofollow'
})
</script>

<style scoped>
/**
 * Estilos específicos para el layout de administración.
 * Utiliza un esquema de colores profesional y corporativo.
 */

.admin-app {
  /* Variables CSS específicas para admin */
  --admin-primary: #0ea5e9;
  --admin-primary-dark: #0284c7;
  --admin-sidebar-width: 16rem;
}

/* Responsive design para admin */
@media (max-width: 1024px) {
  .admin-app {
    --admin-sidebar-width: 0;
  }
}

/* Animaciones específicas de admin */
.admin-app main {
  @apply transition-all duration-300 ease-in-out;
}

/* Tema oscuro para admin */
.dark .admin-app {
  --admin-bg: #1e293b;
  --admin-surface: #334155;
}
</style>