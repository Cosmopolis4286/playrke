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
                <a href="/register" class="text-primary">Regístrate</a>
            </p>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import backendClient, { getCsrfCookie } from '@/plugins/axios'

const login = ref('')
const password = ref('')
const loading = ref(false)
let isSubmitting = false // Flag adicional para prevenir dobles llamadas

// Log para detectar múltiples montajes del componente
onMounted(() => {
    console.log('🔵 Componente Login montado')
})

/**
 * Envío del formulario de login
 * - Obtiene CSRF (Sanctum)
 * - Ejecuta login por sesión
 * - Redirige al dashboard
 */
const submit = async () => {
    // Doble protección contra envíos duplicados
    if (loading.value || isSubmitting) {
        console.warn('⚠️ Login ya en proceso, ignorando intento duplicado')
        return
    }

    loading.value = true
    isSubmitting = true

    try {
        // Necesario SOLO para login / register
        await getCsrfCookie()

        const response = await backendClient.post('/api/auth/login', {
            login: login.value,
            password: password.value,
        })

        console.log('✅ Login exitoso:', response.status)

        // Redirección completa del navegador para inicializar la sesión correctamente
        window.location.href = '/dashboard'
    } catch (error: any) {
        console.error('❌ Error de login:', error)
        alert('Credenciales inválidas')
    } finally {
        loading.value = false
        isSubmitting = false
    }
}
</script>
