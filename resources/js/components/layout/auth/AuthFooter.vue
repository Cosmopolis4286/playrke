<template>
    <div class="auth-footer">
        <slot />

        <div class="links">
            <a href="#" @click.prevent="$emit('open-info', 'terms')">Términos</a>
            <a href="#" @click.prevent="$emit('open-info', 'about')">Quiénes Somos</a>
            <a href="#" @click.prevent="$emit('open-info', 'privacy')">Privacidad</a>
        </div>

        <div class="social">
            <span>PlayRKE © {{ year }}</span>
            <a class="fab fa-facebook" :href="social.facebook" target="_blank"></a>
            <a class="fab fa-instagram" :href="social.instagram" target="_blank"></a>
            <a class="fab fa-telegram" :href="social.telegram" target="_blank"></a>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    year: number
    social: {
        facebook: string
        instagram: string
        telegram: string
    }
}
defineProps<Props>()
</script>

<style scoped>
.auth-footer {
    margin-top: 2rem;
    text-align: center;
    font-size: 0.9rem;
}

.links {
    margin-bottom: 1rem;
}

.links a {
    margin: 0 0.5rem;
    color: #333;
    text-decoration: none;
}

.social {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}
</style>
