<template>
    <div class="guest-layout__logo">
        <img :src="logo" alt="PlayRKE" />
        <p class="login_content_text">
            <slot />
        </p>
    </div>
</template>

<script setup lang="ts">
const logo = new URL('/img/logo.png', import.meta.url).href
</script>
