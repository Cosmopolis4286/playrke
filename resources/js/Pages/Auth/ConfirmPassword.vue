<template>
    <AuthLayout :subtitle="'Confirmar Contraseña'">
        <!-- Security Message -->
        <div class="auth__alert auth__alert--info">
            Esta es un área segura de la aplicación. Por favor confirma tu contraseña para continuar.
        </div>

        <form @submit.prevent="handleSubmit" class="auth__form" id="form-confirm-password">
            <!-- Password -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-lock"></i>
                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth__input"
                    placeholder="Contraseña"
                    aria-label="Contraseña"
                    :class="{ 'auth__input--error': errors.password }"
                    required
                    autofocus
                />
                <i class="auth__input-toggle fa-solid"
                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                   @click="togglePassword"></i>
            </div>
            <span v-if="errors.password" class="auth__error">{{ errors.password }}</span>

            <!-- Submit Button -->
            <div class="auth__form-item">
                <button
                    type="submit"
                    class="auth__button"
                    :disabled="processing || !form.password"
                >
                    Confirmar
                    <span v-if="processing" class="auth__loading"></span>
                </button>
            </div>
        </form>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props
const props = defineProps<{
    passwordConfirmUrl: string
}>()

// Reactive form state
const form = useForm({
    password: '',
})

// State
const showPassword = ref(false)
const processing = ref(false)

// Errors object
const errors = ref<{ password?: string }>({})

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value
}

// Submit handler
const handleSubmit = () => {
    errors.value = {}

    if (!form.password) {
        errors.value.password = 'La contraseña es requerida'
        return
    }

    processing.value = true

    form.post(props.passwordConfirmUrl, {
        onFinish: () => {
            processing.value = false
        },
        onError: (err) => {
            errors.value = err
        },
    })
}
</script>