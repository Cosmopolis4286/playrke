<template>
    <AuthLayout :title="'Iniciar sesión'" :logo="logoUrl">
        <!-- Contenido para el slot "logo-text" -->
        <template #logo-text>
            Ingresa a tu cuenta
        </template>

        <!-- Formulario de inicio de sesión -->
        <template #auth-form>
            <form>
                <div>
                    <label for="email">Correo Electrónico</label>
                    <input v-model="email" id="email" type="email" required />
                </div>

                <div>
                    <label for="password">Contraseña</label>
                    <input v-model="password" id="password" type="password" required />
                </div>

                <button type="submit">Iniciar sesión</button>
            </form>
        </template>

        <!-- Contenido para el slot "footer-info" -->
        <template #footer-info>
            <p class="text-center">¿No tienes cuenta?</p>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { ref } from 'vue';

const email = ref('');
const password = ref('');
const logoUrl = ref('/path/to/logo.png');  // Asegúrate de definirla

defineProps<{
    register: boolean;
    loginUrl: string;
    passwordRequestUrl: string;
    registerUrl: string;
}>();
</script>
