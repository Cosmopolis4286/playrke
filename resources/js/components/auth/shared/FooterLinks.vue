<template>
    <div class="login_footer">
        <slot name="pre-footer"></slot>

        <div class="info_rke">
            <a class="info-user-rke" href="#" data-val="terms">Términos y Condiciones</a>
            <a class="info-user-rke" href="#" data-val="about">Quienes Somos</a>
            <a class="info-user-rke" href="#" data-val="privacy">Privacidad</a>
        </div>

        <div class="login_footer_link">
            <div class="mr-3 name_pg">PlayRKE © {{ new Date().getFullYear() }}</div>
            <div class="content_rs">
                <a class="fab fa-facebook" title="Facebook" href="https://www.facebook.com/my.play.rke"
                    target="_blank"></a>
                <a class="fab fa-instagram" title="Instagram" href="https://www.instagram.com/my.play.rke"
                    target="_blank"></a>
                <a class="fab fa-telegram" title="Telegram" href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank"></a>
            </div>
        </div>
    </div>
</template>

<script setup>
// Si los enlaces cambian, conviértelos en props
</script>
