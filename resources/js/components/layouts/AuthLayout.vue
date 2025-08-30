<template>
    <main id="containerFirst">
        <img id="bgImg" src="/img/body/wall9.jpg" />

        <div id="containerRKE">
            <LoginLogo />

            <!-- Aquí inyectamos el formulario -->
            <div class="container-form">
                <slot name="form"></slot>
            </div>

            <FooterLinks>
                <template #pre-footer>
                    <slot name="pre-footer"></slot>
                </template>
            </FooterLinks>
        </div>
    </main>
</template>

<script setup>
import LoginLogo from '../auth/shared/Logo.vue'
import FooterLinks from '../auth/shared/FooterLinks.vue'
</script>
