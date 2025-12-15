<script setup lang="ts">
import { useHead } from '@vueuse/head';

// Definir las props con sus tipos explícitos
const props = defineProps<{
    title?: string;
    bgImg?: string;
    logo?: string;
}>();

// Usamos useHead para manejar el título y los metadatos
useHead({
    title: `PlayRKE - ${props.title ?? ''}`,
    meta: [
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: 'https://www.playrke.com/' },
        { property: 'og:site_name', content: 'PlayRKE' },
        { name: 'description', content: 'Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online. Gana rápido y retira seguro con PlayRKE.' }
    ],
    link: [
        { rel: 'icon', href: '/img/fav.ico', sizes: '192x192' }
    ]
});
</script>

<template>
    <main id="containerFirst">
        <!-- Background Image -->
        <img id="bgImg" :src="'./img/background/bg_6.jpg'" alt="Background" />

        <div id="containerRKE">
            <!-- Logo -->
            <div class="login_logo">
                <img :src="'./img/logo.png'" alt="PlayRKE" />
                <p class="login_content_text">
                    <slot name="logo-text" />
                </p>
            </div>

            <!-- Form -->
            <div class="container-form">
                <slot name="auth-form" />
            </div>

            <!-- Footer -->
            <div class="login_footer">
                <slot name="footer-info" />

                <div class="info_rke">
                    <a class="info-user-rke" href="#" data-val="terms">Términos y Condiciones</a>
                    <a class="info-user-rke" href="#" data-val="about">Quiénes Somos</a>
                    <a class="info-user-rke" href="#" data-val="privacy">Privacidad</a>
                </div>

                <div class="login_footer_link">
                    <div class="mr-3 name_pg">
                        PlayRKE © {{ new Date().getFullYear() }}
                    </div>

                    <div class="content_rs">
                        <a class="fab fa-facebook" title="Facebook" href="https://www.facebook.com/my.play.rke"
                            target="_blank" aria-label="Facebook" />
                        <a class="fab fa-instagram" title="Instagram" href="https://www.instagram.com/my.play.rke"
                            target="_blank" aria-label="Instagram" />
                        <a class="fab fa-telegram" title="Telegram" href="https://t.me/+rV3KWBuuKOtjYzkx"
                            target="_blank" aria-label="Telegram" />
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
