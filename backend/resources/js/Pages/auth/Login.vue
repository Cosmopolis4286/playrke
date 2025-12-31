<template>
    <AuthLayout title="Iniciar sesión">
        <template #logo-text>
            Ingresa a tu cuenta
        </template>

        <template #auth-form>
            <form @submit.prevent="submit">
                <div>
                    <label for="login">Correo electrónico</label>
                    <input id="login" v-model="login" type="text" autocomplete="username" required />
                </div>

                <div>
                    <label for="password">Contraseña</label>
                    <input id="password" v-model="password" type="password" autocomplete="current-password" required />
                </div>

                <button type="submit" :disabled="loading">
                    {{ loading ? 'Ingresando…' : 'Iniciar sesión' }}
                </button>
            </form>
        </template>

        <template #footer-info>
            <p class="text-center">
                ¿No tienes cuenta?
                <NuxtLink to="/auth/register">Regístrate</NuxtLink>
            </p>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import backendClient, { getCsrfCookie } from '@/plugins/axios'

const router = useRouter()

const login = ref('')
const password = ref('')
const loading = ref(false)

/**
 * Envío del formulario de login
 * - Obtiene CSRF (Sanctum)
 * - Ejecuta login por sesión
 * - Redirige al dashboard
 */
const submit = async () => {
    if (loading.value) return

    loading.value = true

    try {
        // Necesario SOLO para login / register
        await getCsrfCookie()

        await backendClient.post('/api/auth/login', {
            login: login.value,
            password: password.value,
        })

        router.push('/dashboard')
    } catch (error: any) {
        console.error('Error de login', error)
        alert('Credenciales inválidas')
    } finally {
        loading.value = false
    }
}
</script>
