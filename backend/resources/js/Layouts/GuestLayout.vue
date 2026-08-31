<script setup lang="ts">
  import { ref, onMounted, onUnmounted } from 'vue'
  import { useHead } from '@vueuse/head'
  import '@/css/layout/guest-layout.css'
  import '@fortawesome/fontawesome-free/css/all.min.css'
  import InfoModal from '@js/Components/auth/InfoModal.vue'

  type ModalKey = 'terms' | 'about' | 'privacy'

  export interface CarouselSlide {
    icon: string
    title: string
    desc: string
  }

  const DEFAULT_SLIDES: CarouselSlide[] = [
    {
      icon: 'fa-solid fa-trophy',
      title: '¡Compite y gana!',
      desc: 'Participa en torneos de bingo y lleva tus ganancias al siguiente nivel.'
    },
    {
      icon: 'fa-solid fa-bolt',
      title: 'Juegos en tiempo real',
      desc: 'Salas en vivo con miles de jugadores conectados alrededor del mundo.'
    },
    {
      icon: 'fa-solid fa-coins',
      title: 'Premios reales',
      desc: 'Convierte tus habilidades en recompensas reales. Retiros rápidos y seguros.'
    },
    {
      icon: 'fa-solid fa-shield-halved',
      title: 'Seguro y confiable',
      desc: 'Plataforma verificada con cifrado de extremo a extremo y soporte 24/7.'
    }
  ]

  const props = defineProps<{
    title?: string
    bgImg?: string
    logo?: string
    slides?: CarouselSlide[]
  }>()

  useHead({
    title: props.title ? `PlayRKE - ${props.title}` : 'PlayRKE',
    meta: [
      { property: 'og:type', content: 'website' },
      { property: 'og:url', content: 'https://www.playrke.com/' },
      { property: 'og:site_name', content: 'PlayRKE' },
      {
        name: 'description',
        content:
          'Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online.'
      }
    ],
    link: [
      { rel: 'icon', type: 'image/x-icon', href: '/img/fav.ico' },
      { rel: 'shortcut icon', type: 'image/x-icon', href: '/img/fav.ico' },
      { rel: 'preload', as: 'image', href: '/img/background/bg_6.jpg' }
    ]
  })

  const activeSlides = props.slides?.length ? props.slides : DEFAULT_SLIDES

  // URLs construidas en runtime para que Vite no las trate como imports estáticos
  const bgSrc   = `${window.location.origin}/img/background/bg_6.jpg`
  const logoSrc = `${window.location.origin}/img/logo.png`

  const current  = ref(0)
  const bgLoaded = ref(false)
  let timer: ReturnType<typeof setInterval> | null = null

  const next    = () => { current.value = (current.value + 1) % activeSlides.length }
  const goTo    = (i: number) => { current.value = i; resetTimer() }
  const resetTimer = () => {
    if (timer) clearInterval(timer)
    timer = setInterval(next, 4000)
  }

  const onBgLoad = () => { bgLoaded.value = true }

  onMounted(() => {
    timer = setInterval(next, 4000)

    // Fallback: si por alguna razón el img ya cacheó y no dispara onload
    const img = new Image()
    img.src = '/img/background/bg_6.jpg'
    if (img.complete) bgLoaded.value = true
  })
  onUnmounted(() => { if (timer) clearInterval(timer) })

  const activeModal = ref<ModalKey | null>(null)
  const openModal   = (key: ModalKey) => { activeModal.value = key }

  // Expuesto para que páginas que usan este layout (ej. Auth/Register.vue,
  // checkbox de términos) puedan abrir los mismos modales sin duplicar
  // InfoModal ni navegar a /info/terms fuera de la SPA.
  defineExpose({ openModal })
</script>

<template>
  <main class="guest-layout" :class="{ 'guest-layout--ready': bgLoaded }">

    <!-- BG como <img> para disparo de onload y fetchpriority -->
    <img
      class="guest-layout__bg"
      :src="bgSrc"
      alt=""
      aria-hidden="true"
      fetchpriority="high"
      decoding="async"
      @load="onBgLoad"
    />

    <!-- Overlay oscuro encima del BG -->
    <div class="guest-layout__overlay" aria-hidden="true"></div>

    <!-- ── CARD SPLIT ─────────────────────────────── -->
    <div class="guest-layout__card">

      <!-- ── PANEL IZQUIERDO: carrusel ────────────── -->
      <aside class="guest-layout__panel guest-layout__panel--left">

        <!-- orbe decorativo -->
        <div class="gl-orb" aria-hidden="true"></div>

        <!-- Branding -->
        <div class="gl-panel__brand">
          <img class="gl-panel__logo" :src="logoSrc" alt="PlayRKE" />
        </div>

        <!-- Slides -->
        <div class="gl-carousel">
          <transition name="carousel-fade" mode="out-in">
            <div :key="current" class="gl-carousel__slide">
              <div class="gl-carousel__icon-wrap">
                <i :class="['gl-carousel__icon', activeSlides[current].icon]" aria-hidden="true"></i>
              </div>
              <h2 class="gl-carousel__title">{{ activeSlides[current].title }}</h2>
              <p  class="gl-carousel__desc">{{ activeSlides[current].desc }}</p>
            </div>
          </transition>
        </div>

        <!-- Dots -->
        <nav class="gl-carousel__dots" aria-label="Carrusel de características">
          <button
            v-for="(_, i) in activeSlides"
            :key="i"
            class="gl-carousel__dot"
            :class="{ 'gl-carousel__dot--active': i === current }"
            :aria-label="`Ir a diapositiva ${i + 1}`"
            @click="goTo(i)"
          />
        </nav>

        <!-- Redes sociales -->
        <nav class="gl-panel__socials" aria-label="Social media">
          <a class="gl-panel__social-icon fab fa-facebook"  href="https://www.facebook.com/my.play.rke"  target="_blank" aria-label="Facebook" />
          <a class="gl-panel__social-icon fab fa-instagram" href="https://www.instagram.com/my.play.rke" target="_blank" aria-label="Instagram" />
          <a class="gl-panel__social-icon fab fa-telegram"  href="https://t.me/+rV3KWBuuKOtjYzkx"         target="_blank" aria-label="Telegram" />
        </nav>
      </aside>

      <!-- ── PANEL DERECHO: formulario ────────────── -->
      <section class="guest-layout__panel guest-layout__panel--right">
        <!-- Header -->
        <header class="guest-layout__header">
          <p class="guest-layout__tagline">
            <slot name="tagline">Tu plataforma de juegos premium</slot>
          </p>
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
          <nav class="guest-layout__legal">
            <button class="guest-layout__legal-link" @click="openModal('terms')">Terms</button>
            <button class="guest-layout__legal-link" @click="openModal('about')">About</button>
            <button class="guest-layout__legal-link" @click="openModal('privacy')">Privacy</button>
          </nav>
          <span class="guest-layout__copyright">PlayRKE © {{ new Date().getFullYear() }}</span>
        </footer>
      </section>

    </div>

    <InfoModal v-model="activeModal" />
  </main>
</template>
