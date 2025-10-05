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
onMounted(() => {
  // Detectar contexto basado en el dominio
  const host = window.location.hostname
  const subdomain = host.split('.')[0]

  // Configuración de dominios
  const isAdmin = [
    'admin.playrke.local',
    'admin.playrke.com'
  ].includes(host) || subdomain === 'admin'

  const isPlayer = [
    'play.playrke.local',
    'play.playrke.com',
    'playrke.local',
    'playrke.com',
    'www.playrke.local',
    'www.playrke.com'
  ].includes(host) || ['play', 'www'].includes(subdomain)

  // Redirección con pequeño delay para mostrar el loading
  setTimeout(() => {
    if (isAdmin) {
      navigateTo('/admin', { replace: true })
    } else {
      // Por defecto ir a player
      navigateTo('/player', { replace: true })
    }
  }, 1500)
})
</script>