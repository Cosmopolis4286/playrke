<template>
    <AuthLayout :subtitle="'Restablecer Contraseña'">
        <form @submit.prevent="handleSubmit" class="auth__form" id="form-reset-password">
            <!-- Hidden token -->
            <input type="hidden" v-model="form.token" />

            <!-- Email -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-envelope"></i>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="Email"
                    aria-label="Email"
                    class="auth__input"
                    :class="{ 'auth__input--error': errors.email }"
                    required
                    readonly
                />
            </div>
            <span v-if="errors.email" class="auth__error">{{ errors.email }}</span>

            <!-- Password -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-lock"></i>
                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth__input"
                    placeholder="Nueva Contraseña"
                    aria-label="Nueva Contraseña"
                    :class="{ 'auth__input--error': errors.password }"
                    required
                />
            </div>
            <span v-if="errors.password" class="auth__error">{{ errors.password }}</span>

            <!-- Confirm Password -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-unlock"></i>
                <i class="auth__input-toggle fa-solid"
                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                   @click="togglePassword"></i>
                <input
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth__input"
                    placeholder="Confirmar Nueva Contraseña"
                    aria-label="Confirmar Nueva Contraseña"
                    :class="{ 'auth__input--error': errors.password_confirmation }"
                    required
                />
            </div>
            <span v-if="errors.password_confirmation" class="auth__error">{{ errors.password_confirmation }}</span>

            <!-- Submit Button -->
            <div class="auth__form-item">
                <button
                    type="submit"
                    class="auth__button"
                    :disabled="processing || !isFormValid"
                >
                    Restablecer Contraseña
                    <span v-if="processing" class="auth__loading"></span>
                </button>
            </div>
        </form>

        <template #pre-footer>
            <div class="auth__nav">
                <a :href="loginUrl" class="flex items-center justify-center space-x-2">
                    <span class="auth__nav-text">¿Recordaste tu contraseña?</span>
                    <span class="auth__nav-separator">|</span>
                    <span class="auth__nav-link">Inicia Sesión</span>
                </a>
            </div>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props
const props = defineProps<{
    passwordStoreUrl: string
    loginUrl: string
    token: string
    email: string
}>()

// Reactive form state
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

// State
const showPassword = ref(false)
const processing = ref(false)

// Errors object
const errors = ref<{
    email?: string
    password?: string
    password_confirmation?: string
}>({})

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value
}

// Form validation
const isFormValid = computed(() => {
    return form.email &&
           form.password &&
           form.password_confirmation &&
           form.password === form.password_confirmation &&
           form.password.length >= 8
})

// Submit handler
const handleSubmit = () => {
    errors.value = {}

    if (!isFormValid.value) {
        if (form.password !== form.password_confirmation) {
            errors.value.password_confirmation = 'Las contraseñas no coinciden'
        }
        if (form.password.length < 8) {
            errors.value.password = 'La contraseña debe tener al menos 8 caracteres'
        }
        return
    }

    processing.value = true

    form.post(props.passwordStoreUrl, {
        onFinish: () => {
            processing.value = false
        },
        onError: (err) => {
            errors.value = err
        },
    })
}
</script>