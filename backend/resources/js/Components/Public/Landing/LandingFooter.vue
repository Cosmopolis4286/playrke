<script setup lang="ts">
import type { FooterContent } from '@js/types/content/landing-section'
import type { NavLink } from '@js/Components/Platform/PublicNavbar.vue'

defineProps<{
  content: FooterContent | null
  navLinks: NavLink[]
}>()

defineEmits<{ 'open-info-modal': [key: 'terms' | 'about' | 'privacy'] }>()

const DEFAULT_SOCIALS = {
  facebook: 'https://www.facebook.com/my.play.rke',
  instagram: 'https://www.instagram.com/my.play.rke',
  telegram: 'https://t.me/+rV3KWBuuKOtjYzkx',
}
</script>

<template>
  <footer class="landing__footer">
    <div class="landing__footer-inner">
      <div class="landing__footer-brand">
        <span class="landing__brand">PLAYRKE</span>
        <p class="landing__footer-tagline">{{ content?.tagline ?? 'Bingo en vivo con premios reales. Ves el pozo, ves quién gana.' }}</p>
        <nav class="landing__footer-socials" aria-label="Redes sociales">
          <a :href="content?.socials?.facebook ?? DEFAULT_SOCIALS.facebook" target="_blank" rel="noopener" aria-label="Facebook" class="fab fa-facebook" />
          <a :href="content?.socials?.instagram ?? DEFAULT_SOCIALS.instagram" target="_blank" rel="noopener" aria-label="Instagram" class="fab fa-instagram" />
          <a :href="content?.socials?.telegram ?? DEFAULT_SOCIALS.telegram" target="_blank" rel="noopener" aria-label="Telegram" class="fab fa-telegram" />
        </nav>
      </div>

      <nav v-if="navLinks.length" class="landing__footer-col" aria-label="Navegación">
        <span class="landing__footer-col-title">Plataforma</span>
        <a v-for="link in navLinks" :key="link.href" :href="link.href">{{ link.label }}</a>
      </nav>

      <nav class="landing__footer-col" aria-label="Legal">
        <span class="landing__footer-col-title">Legal</span>
        <button type="button" class="landing__footer-link" @click="$emit('open-info-modal', 'terms')">Términos y condiciones</button>
        <button type="button" class="landing__footer-link" @click="$emit('open-info-modal', 'about')">Acerca de nosotros</button>
        <button type="button" class="landing__footer-link" @click="$emit('open-info-modal', 'privacy')">Política de privacidad</button>
      </nav>
    </div>

    <div class="landing__footer-bottom">
      <span>PlayRKE © {{ new Date().getFullYear() }}. {{ content?.copyrightSuffix ?? 'Todos los derechos reservados.' }}</span>
      <span class="landing__footer-trust">{{ content?.trustLine ?? 'Retiros verificados · Cartones auditables por serial' }}</span>
    </div>
  </footer>
</template>
