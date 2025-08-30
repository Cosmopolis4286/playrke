<!-- resources/js/Components/Layouts/AuthLayout.vue -->
<template>
    <main id="containerFirst">
        <img id="bgImg" src="/img/body/wall9.jpg" alt="Background" />

        <div id="containerRKE">
            <LoginLogo />

            <!-- Inyección del formulario -->
            <div class="container-form">
                <slot name="form" />
            </div>

            <FooterLinks>
                <template #pre-footer>
                    <slot name="pre-footer" />
                </template>
            </FooterLinks>
        </div>
    </main>
</template>

<script setup lang="ts">
import LoginLogo from '@/components/auth/shared/Logo.vue'
import FooterLinks from '@/components/auth/shared/FooterLinks.vue'

// 👇 Asignamos un nombre explícito al componente
defineOptions({ name: 'AuthLayout' })
</script>
