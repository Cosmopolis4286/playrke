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
import axios from '@/plugins/axios';  // Tu instancia de axios personalizada
import { getCsrfToken } from '@/plugins/axios';  // Asegúrate de importar el método getCsrfToken

const login = ref('');
const password = ref('');

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
        // Obtener el token CSRF antes de realizar la solicitud
        await getCsrfToken();

        // Realizar la solicitud de login después de obtener el token CSRF
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
