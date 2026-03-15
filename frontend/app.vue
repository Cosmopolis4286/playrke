<template>
  <div class="min-h-screen bg-background">
    <!-- Contenido principal -->
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>

    <!-- Notificaciones globales -->
    <NotificationContainer />
    <SimpleNotificationContainer />

    <!-- Modales globales -->
    <ModalContainer />
  </div>
</template>

<script setup lang="ts">
/**
 * Componente raíz de la aplicación PlayRKE Frontend.
 *
 * Maneja la configuración global, el estado de carga y los componentes
 * compartidos entre todas las capas (admin/player/shared).
 */

// Meta configuración de la aplicación
useHead({
  title: 'PlayRKE - Plataforma de Bingo Online',
  meta: [
    { name: 'description', content: 'La mejor plataforma de bingo online con salas en tiempo real' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    { name: 'format-detection', content: 'telephone=no' },
    { property: 'og:title', content: 'PlayRKE - Plataforma de Bingo Online' },
    { property: 'og:description', content: 'La mejor plataforma de bingo online con salas en tiempo real' },
    { property: 'og:type', content: 'website' },
    { name: 'twitter:card', content: 'summary_large_image' }
  ],
  link: [
    { rel: 'icon', type: 'image/x-icon', href: 'img/fav.ico' },
    { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' }
  ],
  htmlAttrs: {
    lang: 'es'
  }
})

// Configuración del color mode
const colorMode = useColorMode()

// Configuración de SEO dinámico
useSeoMeta({
  titleTemplate: '%s | PlayRKE',
  description: 'Plataforma de bingo online con salas en tiempo real, premios instantáneos y la mejor experiencia de juego.',
  ogTitle: 'PlayRKE - Plataforma de Bingo Online',
  ogDescription: 'La mejor plataforma de bingo online con salas en tiempo real',
  ogImage: '/og-image.jpg',
  ogUrl: 'https://playrke.com',
  twitterTitle: 'PlayRKE - Bingo Online',
  twitterDescription: 'La mejor plataforma de bingo online',
  twitterImage: '/twitter-image.jpg',
  twitterCard: 'summary_large_image'
})

// Configuración del JSON-LD estructurado para SEO
// TODO: Instalar @nuxtjs/seo o nuxt-schema-org para habilitar useJsonld

// Configuración de error handling global
onErrorCaptured((error) => {
  console.error('Error capturado en app.vue:', error)
  // Aquí puedes enviar errores a un servicio de logging
  return false
})
</script>

<style>
/**
 * Estilos globales base para la aplicación.
 * Los estilos específicos de cada capa se definen en sus respectivos layers.
 */

/* Importación de fuentes y estilos base */
@import url('~/assets/css/main.css');

/* Configuración global del html y body */
html {
  scroll-behavior: smooth;
}

body {
  @apply font-sans antialiased;
  @apply text-foreground bg-background;
  @apply overflow-x-hidden;
}

/* Variables CSS para temas */
:root {
  --font-family-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
  --font-family-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
}

/* Transiciones globales */
* {
  @apply transition-colors duration-200 ease-in-out;
}

/* Scroll personalizado */
::-webkit-scrollbar {
  @apply w-2 h-2;
}

::-webkit-scrollbar-track {
  @apply bg-muted;
}

::-webkit-scrollbar-thumb {
  @apply bg-muted-foreground/30 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-muted-foreground/50;
}

/* Animaciones globales */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

/* Clases de utilidad */
.fade-in {
  animation: fadeIn 0.3s ease-out;
}

.slide-up {
  animation: slideUp 0.4s ease-out;
}

.pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Estilos para focus-visible */
.focus-visible {
  @apply outline-none ring-2 ring-primary ring-offset-2 ring-offset-background;
}

/* Estilos para elementos interactivos */
.interactive {
  @apply cursor-pointer transition-all duration-200 ease-in-out;
}

.interactive:hover {
  @apply scale-105;
}

.interactive:active {
  @apply scale-95;
}

/* Estilos para estados de carga */
.loading {
  @apply pointer-events-none opacity-60;
}

.loading::after {
  content: '';
  @apply absolute inset-0 bg-background/50 rounded-md;
  animation: pulse 1.5s ease-in-out infinite;
}
</style>
