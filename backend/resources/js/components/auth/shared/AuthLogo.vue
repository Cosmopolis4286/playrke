<template>
    <div class="guest-layout__logo">
        <img :src="logo" alt="PlayRKE" />
        <p class="guest-layout__logo-text">
            <slot />
        </p>
    </div>
</template>

<script setup lang="ts">
const logo = new URL('/img/logo.png', import.meta.url).href
</script>
