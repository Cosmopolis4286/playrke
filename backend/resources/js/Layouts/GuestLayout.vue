<script setup lang="ts">
  import { useHead } from '@vueuse/head'
  import '@/css/layout/guest-layout.css'
  import '@fortawesome/fontawesome-free/css/all.min.css'

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
        <div class="guest-layout__logo">
          <img class="guest-layout__logo-image" :src="'./img/logo.png'" alt="PlayRKE" />
        </div>
        <div class="guest-layout__header-text">
          <slot name="header-text" />
        </div>
      </header>
      <!-- Form -->
      <section class="guest-layout__form">
        <slot name="form-content" />
      </section>
      <!-- Footer -->
      <footer class="guest-layout__footer">
        <aside class="guest-layout__footer-content">
          <slot name="footer-content" />
        </aside>
        <section class="guest-layout__footer-socials">
          <span class="guest-layout__copyright">PlayRKE © {{ new Date().getFullYear() }}</span>
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
        <nav class="guest-layout__legal">
          <a class="guest-layout__legal-link" href="#" data-val="terms"> Terms and Conditions </a>
          <a class="guest-layout__legal-link" href="#" data-val="about"> About Us </a>
          <a class="guest-layout__legal-link" href="#" data-val="privacy"> Privacy </a>
        </nav>
      </footer>
    </section>
  </main>
</template>
