<template>
    <AuthLayout :subtitle="'Confirmar Email'">
        <!-- Verification Message -->
        <div class="auth__alert auth__alert--info">
            ¡Gracias por registrarte! Antes de comenzar, debes verificar tu dirección
            de correo electrónico haciendo clic en el enlace recibido. Revisa la bandeja
            de entrada y la carpeta de spam. Si no recibiste el correo electrónico, con gusto te enviaremos otro.
        </div>

        <!-- Success Status -->
        <div v-if="status === 'verification-link-sent'" class="auth__alert auth__alert--success">
            Se ha enviado un nuevo enlace de verificación al E-mail que proporcionó durante el registro
        </div>

        <!-- Resend Form -->
        <form @submit.prevent="handleResendVerification" class="auth__form">
            <div class="auth__form-item">
                <button
                    type="submit"
                    class="auth__button"
                    :disabled="processing"
                >
                    Reenviar correo de verificación
                    <span v-if="processing" class="auth__loading"></span>
                </button>
            </div>
        </form>

        <!-- Logout Option -->
        <div class="auth__nav">
            <form @submit.prevent="handleLogout" class="inline">
                <button
                    type="submit"
                    class="auth__button auth__button--secondary"
                    :disabled="processing"
                >
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props
const props = defineProps<{
    verificationSendUrl: string
    logoutUrl: string
    status?: string
}>()

// State
const processing = ref(false)
const status = ref(props.status || '')

// Forms
const verificationForm = useForm({})
const logoutForm = useForm({})

// Handle resend verification
const handleResendVerification = () => {
    processing.value = true

    verificationForm.post(props.verificationSendUrl, {
        onFinish: () => {
            processing.value = false
        },
        onSuccess: () => {
            status.value = 'verification-link-sent'
        },
    })
}

// Handle logout
const handleLogout = () => {
    processing.value = true

    logoutForm.post(props.logoutUrl, {
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>