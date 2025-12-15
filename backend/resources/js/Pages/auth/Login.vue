<template>
    <AuthLayout :title="'Iniciar sesión'">
        <!-- Contenido para el slot "logo-text" -->
        <template #logo-text>
            Ingresa a tu cuenta
        </template>

        <!-- Formulario de inicio de sesión -->
        <template #auth-form>
            <form @submit.prevent="submitForm">
                <div>
                    <label for="login">Correo Electrónico</label>
                    <input v-model="login" id="login" type="text" required />
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
import { apiClient, getCsrfToken } from '@/plugins/axios'; // Usa apiClient y getCsrfToken importados

const login = ref('');
const password = ref('');

// Props del componente
defineProps<{
    register: boolean;
    loginUrl: string;
    passwordRequestUrl: string;
    registerUrl: string;
}>();

const submitForm = async () => {
    try {
        // Obtener token CSRF antes de login
        await getCsrfToken();

        // Llamada al endpoint de login usando apiClient configurado
        const response = await apiClient.post('/auth/login', {
            login: login.value,
            password: password.value,
        });

        // Redirigir al dashboard o url que venga en la respuesta
        window.location.href = response.data.redirectUrl || '/dashboard';

    } catch (error) {
        console.error('Error al iniciar sesión:', error);
        // Aquí puedes manejar el error para mostrar mensaje en UI
    }
};
</script>
