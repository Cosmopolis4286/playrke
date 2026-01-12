<script setup lang="ts">
  import { useHead } from '@vueuse/head'
  import '@/css/layout/guest-layout.css'

  // Definir las props con sus tipos explícitos
  const props = defineProps<{
    title?: string
    bgImg?: string
    logo?: string
  }>()

  // Usamos useHead para manejar el título y los metadatos
  useHead({
    title: `PlayRKE - ${props.title ?? ''}`,
    meta: [
      { property: 'og:type', content: 'website' },
      { property: 'og:url', content: 'https://www.playrke.com/' },
      { property: 'og:site_name', content: 'PlayRKE' },
      {
        name: 'description',
        content:
          'Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online. Gana rápido y retira seguro con PlayRKE.'
      }
    ],
    link: [{ rel: 'icon', href: './img/fav.ico', sizes: '192x192' }]
  })
</script>

<template>
  <main class="guest-layout">
    <section class="guest-layout__card">
      <!-- Header / Logo -->
      <header class="guest-layout__header">
        <figure class="guest-layout__logo">
          <img class="guest-layout__logo-image" :src="'./img/logo.png'" alt="PlayRKE" />
          <figcaption class="guest-layout__logo-text">
            <slot name="logo-text" />
          </figcaption>
        </figure>
      </header>
      <!-- Form -->
      <section class="guest-layout__form">
        <slot name="auth-form" />
      </section>
      <!-- Footer -->
      <footer class="guest-layout__footer">
        <slot name="footer-content" />
        <nav class="guest-layout__legal">
          <a class="guest-layout__legal-link" href="#" data-val="terms"> Terms and Conditions </a>
          <a class="guest-layout__legal-link" href="#" data-val="about"> About Us </a>
          <a class="guest-layout__legal-link" href="#" data-val="privacy"> Privacy </a>
        </nav>
        <section class="guest-layout__footer-bottom">
          <p class="guest-layout__copyright mr-3">PlayRKE © {{ new Date().getFullYear() }}</p>
          <nav class="guest-layout__socials" aria-label="Social media">
            <a
              class="guest-layout__social-icon fab fa-facebook"
              title="Facebook"
              href="https://www.facebook.com/my.play.rke"
              target="_blank"
              aria-label="Facebook"
            />
            <a
              class="guest-layout__social-icon fab fa-instagram"
              title="Instagram"
              href="https://www.instagram.com/my.play.rke"
              target="_blank"
              aria-label="Instagram"
            />
            <a
              class="guest-layout__social-icon fab fa-telegram"
              title="Telegram"
              href="https://t.me/+rV3KWBuuKOtjYzkx"
              target="_blank"
              aria-label="Telegram"
            />
          </nav>
        </section>
      </footer>
    </section>
  </main>
</template>
