<template>
    <AuthLayout :subtitle="'Tu Sitio Para Ganar'" :register="register">
        <form @submit.prevent="handleSubmit" class="w-full" id="form-login">
            <!-- Email/Username -->
            <div class="login_form_item">
                <i class="e_mc fa-solid fa-user"></i>
                <input v-model="login" type="text" placeholder="Email/Username" aria-label="Email o Nombre de Usuario"
                    :class="{ 'is-invalid': errors.login }" />
            </div>
            <span v-if="errors.login" class="er_nw">{{ errors.login }}</span>

            <!-- Password -->
            <div class="login_form_item relative">
                <i class="e_mc fa-solid fa-lock"></i>
                <input v-model="password" :type="showPassword ? 'text' : 'password'" class="pass"
                    placeholder="Contraseña" aria-label="Contraseña" />
                <i class="eye_mc fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                    @click="togglePassword"></i>
            </div>

            <!-- Submit -->
            <div class="login_form_item">
                <button type="submit" id="Ingresar">
                    Iniciar Sesión
                    <span v-if="loading" id="loader-btn" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>

            <!-- Forget password -->
            <div class="login_form_item">
                <inertia-link href="{{ route('password.request') }}" class="forget_password">
                    ¿Olvidaste tu contraseña?
                </inertia-link>
            </div>
        </form>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props del layout
const props = defineProps<{
    register: boolean
}>()

// Form state
const login = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const errors = ref<{ login?: string }>({})

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value
}

// Handle form submit
const handleSubmit = () => {
    errors.value = {}

    if (!login.value || !password.value) {
        errors.value.login = 'Toda la información es requerida'
        return
    }

    loading.value = true

    // Enviar formulario usando Inertia
    // Esto reemplaza el form blade con acción post
    // $inertia.post(route('login'), {
    //     login: login.value,
    //     password: password.value,
    // }).finally(() => {
    //     loading.value = false
    // })
}
</script>