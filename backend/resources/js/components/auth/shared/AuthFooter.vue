<template>
    <div class="login_footer">
        <slot name="pre-footer" />
        <div class="guest-layout__legal">
            <a class="guest-layout__legal-link" href="#" data-val="terms">Términos y Condiciones</a>
            <a class="guest-layout__legal-link" href="#" data-val="about">Quienes Somos</a>
            <a class="guest-layout__legal-link" href="#" data-val="privacy">Privacidad</a>
        </div>
        <div class="guest-layout__footer-bottom">
            <div class="mr-3 guest-layout__copyright">PlayRKE © {{ new Date().getFullYear() }}</div>
            <div class="guest-layout__socials">
                <a class="fab fa-facebook" title="Facebook" href="https://www.facebook.com/my.play.rke"
                    target="_blank"></a>
                <a class="fab fa-instagram" title="Instagram" href="https://www.instagram.com/my.play.rke"
                    target="_blank"></a>
                <a class="fab fa-telegram" title="Telegram" href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank"></a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * Footer de auth pages
 */
</script>
