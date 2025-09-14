<!-- resources/js/components/layouts/AuthLayout.vue -->
<template>
  <div class="auth">
    <main class="flex items-center justify-center min-h-screen relative select-none">
      <!-- Fondo -->
      <img class="absolute inset-0 w-full h-full object-cover" :src="bgImg" alt="Background" />

      <!-- Caja central -->
      <div class="auth__container">
        <!-- Logo -->
        <div class="auth__logo">
          <img :src="logo" alt="PlayRKE" />
        </div>

        <!-- Subtítulo -->
        <p v-if="subtitle" class="text-center text-blue-600 font-light text-sm mb-3">
          {{ subtitle }}
        </p>

        <!-- Contenido dinámico -->
        <div class="w-full">
          <slot />
        </div>

        <!-- Footer -->
        <footer class="flex flex-col items-center mt-6 w-full text-sm">
          <slot name="pre-footer" />

          <!-- Links legales -->
          <div class="auth__footer-links">
            <a href="#" @click.prevent="$emit('open-info', 'terms')">Términos</a>
            <a href="#" @click.prevent="$emit('open-info', 'about')">Quiénes Somos</a>
            <a href="#" @click.prevent="$emit('open-info', 'privacy')">Privacidad</a>
          </div>

          <!-- Social y copyright -->
          <div class="flex items-center justify-center space-x-3 mt-3">
            <span class="text-blue-900/80 text-xs sm:text-sm">PlayRKE © {{ year }}</span>
            <a class="fab fa-facebook text-blue-900/80 hover:text-blue-700" :href="social.facebook" target="_blank"></a>
            <a class="fab fa-instagram text-blue-900/80 hover:text-blue-700" :href="social.instagram"
              target="_blank"></a>
            <a class="fab fa-telegram text-blue-900/80 hover:text-blue-700" :href="social.telegram" target="_blank"></a>
          </div>
        </footer>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import '../../../assets/css/auth.css'

import { computed } from "vue";

interface Props {
  logo?: string;
  bgImg?: string;
  subtitle?: string;
  social?: {
    facebook: string;
    instagram: string;
    telegram: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  logo: "/img/log.png",
  bgImg: "/img/body/wall9.jpg",
  subtitle: "",
  // social: {
  //   // facebook: "https://www.facebook.com/my.play.rke",
  //   // instagram: "https://www.instagram.com/my.play.rke",
  //   // telegram: "https://t.me/+rV3KWBuuKOtjYzkx",
  // },
});

const { logo, bgImg, subtitle, social } = props;

const year = computed(() => new Date().getFullYear());
</script>
