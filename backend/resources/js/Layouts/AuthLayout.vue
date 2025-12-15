<template>
  <main id="containerFirst">
    <img id="bgImg" :src="bgImg" alt="Background" />
    <div id="containerRKE">
      <div class="login_logo">
        <img :src="logo" alt="PlayRKE" />
        <p class="login_content_text"><slot name="login-text" /></p>
      </div>
      <div class="container-form">
        <slot />
      </div>
      <div class="login_footer">
        <slot name="pre-footer" />
        <div class="info_rke">
          <a href="#" class="info-user-rke" data-val="terms" @click.prevent="$emit('info', 'terms')">Términos y Condiciones</a>
          <a href="#" class="info-user-rke" data-val="about" @click.prevent="$emit('info', 'about')">Quienes Somos</a>
          <a href="#" class="info-user-rke" data-val="privacy" @click.prevent="$emit('info', 'privacy')">Privacidad</a>
        </div>
        <div class="login_footer_link">
          <div class="mr-3 name_pg">PlayRKE © {{ currentYear }}</div>
          <div class="content_rs">
            <a
              class="fab fa-facebook"
              title="Facebook"
              href="https://www.facebook.com/my.play.rke"
              target="_blank"
              rel="noopener noreferrer"
            ></a>
            <a
              class="fab fa-instagram"
              title="Instagram"
              href="https://www.instagram.com/my.play.rke"
              target="_blank"
              rel="noopener noreferrer"
            ></a>
            <a
              class="fab fa-telegram"
              title="Telegram"
              href="https://t.me/+rV3KWBuuKOtjYzkx"
              target="_blank"
              rel="noopener noreferrer"
            ></a>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const currentYear = new Date().getFullYear();

// Ruta a assets públicos, ajusta según tu configuración Vite o Laravel Mix
const bgImg = new URL('/img/body/wall9.jpg', import.meta.url).href;
const logo = new URL('/img/log.png', import.meta.url).href;
</script>

<style scoped>
/* Aquí puedes añadir el CSS específico que usabas o importar el CSS global */
/* Ejemplo para bgImg: */
#bgImg {
  position: fixed;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: -1;
}
/* ... agrega estilos necesarios para reproducir el diseño */
</style>
