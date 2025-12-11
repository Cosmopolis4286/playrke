<!--
  Página de redirección automática.

  Detecta el contexto (admin/player) y redirige automáticamente
  a la sección correspondiente basándose en el dominio/subdomain.
-->

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="text-center">
      <!-- Logo y título -->
      <div class="mb-8">
        <h1 class="font-gaming text-6xl font-bold text-white mb-4 text-glow">
          PlayRKE
        </h1>
        <p class="text-xl text-slate-300">
          Redirigiendo...
        </p>
      </div>

      <!-- Spinner de carga -->
      <div class="flex justify-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500"></div>
      </div>

      <!-- Mensaje de carga -->
      <p class="text-slate-400 mt-4">
        Detectando contexto de la aplicación
      </p>
    </div>
  </div>
</template>

<script setup>
// Configuración de la página
definePageMeta({
  layout: false // Sin layout específico
})

// SEO
useHead({
  title: 'PlayRKE - Cargando...',
  meta: [
    { name: 'description', content: 'PlayRKE - Sistema de Bingo Online' }
  ]
})

// Lógica de redirección automática
const { checkAuth } = useAuth()

onMounted(async () => {
  // Detectar contexto basado en el dominio
  const host = window.location.hostname
  const subdomain = host.split('.')[0]

  // Determinar si es contexto admin
  const isAdmin = [
    'admin.playrke.local',
    'admin.playrke.com'
  ].includes(host) || subdomain === 'admin'

  // Verificar autenticación para redirección inteligente
  let targetPath = '/player/auth/login'

  try {
    const user = await checkAuth()

    if (user) {
      // Usuario autenticado: redirigir según rol
      const userRole = user.roles?.[0]?.name || 'player'

      if (isAdmin || userRole === 'admin') {
        targetPath = '/admin/dashboard'
      } else {
        targetPath = '/player/lobby'
      }
    } else {
      // Usuario no autenticado: redirigir a login
      targetPath = isAdmin ? '/admin/auth/login' : '/player/auth/login'
    }
  } catch (error) {
    // Error al verificar auth: ir a login
    targetPath = isAdmin ? '/admin/auth/login' : '/player/auth/login'
  }

  // Redirección inmediata (sin delay artificial)
  await navigateTo(targetPath, { replace: true })
})
</script>