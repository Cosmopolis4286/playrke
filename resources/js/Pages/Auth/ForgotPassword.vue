<template>
    <AuthLayout :subtitle="'Reestablecer Contraseña'">
        <!-- Success Message -->
        <div v-if="status" class="auth__alert auth__alert--success">
            Hemos enviado el link de reseteo
        </div>

        <form @submit.prevent="handleSubmit" class="auth__form" id="form-forgot-password">
            <!-- Email -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-envelope"></i>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="Ingresa tu correo asociado"
                    aria-label="Email"
                    class="auth__input"
                    :class="{ 'auth__input--error': errors.email }"
                    required
                />
            </div>
            <span v-if="errors.email" class="auth__error">{{ errors.email }}</span>

            <!-- Submit Button -->
            <div class="auth__form-item">
                <button
                    type="submit"
                    class="auth__button"
                    :disabled="processing || !form.email"
                >
                    Enviar link de reseteo
                    <span v-if="processing" class="auth__loading"></span>
                </button>
            </div>
        </form>

        <template #pre-footer>
            <div class="auth__nav">
                <a :href="loginUrl" class="flex items-center justify-center space-x-2">
                    <span class="auth__nav-text">¿Quieres volver a intentar?</span>
                    <span class="auth__nav-separator">|</span>
                    <span class="auth__nav-link">Inicia Sesión</span>
                </a>
            </div>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props
const props = defineProps<{
    passwordEmailUrl: string
    loginUrl: string
    status?: string
}>()

// Reactive form state
const form = useForm({
    email: '',
})

// State
const processing = ref(false)

// Errors object
const errors = ref<{ email?: string }>({})

// Check if there's a status message from server
const status = ref(props.status || '')

// Submit handler
const handleSubmit = () => {
    errors.value = {}

    if (!form.email) {
        errors.value.email = 'Ingresa tu correo asociado'
        return
    }

    processing.value = true

    form.post(props.passwordEmailUrl, {
        onFinish: () => {
            processing.value = false
        },
        onSuccess: () => {
            status.value = 'Hemos enviado el link de reseteo'
        },
        onError: (err) => {
            errors.value = err
        },
    })
}
</script>