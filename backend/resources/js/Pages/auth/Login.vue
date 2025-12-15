<template>
    <AuthLayout :title="'Iniciar sesión'" :logo="logoUrl">
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
import axios from 'axios';  // Asegúrate de importar axios

const login = ref('');
const password = ref('');
const logoUrl = ref('/path/to/logo.png');  // Asegúrate de definirla

// Definir las propiedades para el componente
defineProps<{
    register: boolean;
    loginUrl: string;
    passwordRequestUrl: string;
    registerUrl: string;
}>();

// Método de envío del formulario
const submitForm = async () => {
    try {
        const response = await axios.post('/login', {
            email: login.value,
            password: password.value
        });

        // Redirigir después de iniciar sesión
        window.location.href = response.data.redirectUrl || '/dashboard'; // Puedes cambiar '/dashboard' por la ruta a la que deseas redirigir

    } catch (error) {
        console.error('Error al iniciar sesión:', error);
        // Manejo de errores (puedes mostrar un mensaje en la interfaz de usuario)
    }
};
</script>
