<template>
    <main id="containerFirst">
        <img id="bgImg" :src="bgImg" alt="Background" />
        <div id="containerRKE">
            <AuthLogo />
            <slot /> <!-- Aquí va el contenido principal (LoginForm) -->
            <AuthFooter>
                <slot name="pre-footer" />
            </AuthFooter>
        </div>
    </main>
</template>

<script setup lang="ts">
import AuthLogo from '@/Components/Auth/Shared/AuthLogo.vue'
import AuthFooter from '@/Components/Auth/Shared/AuthFooter.vue'

const bgImg = new URL('/img/body/wall9.jpg', import.meta.url).href
</script>
