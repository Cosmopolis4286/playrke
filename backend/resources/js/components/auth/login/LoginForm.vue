<template>
    <form @submit.prevent="submit" id="form-login">
        <!-- Email/Username -->
        <div class="login_form_item">
            <i class="e_mc fa-solid fa-user"></i>
            <input v-model="form.login" type="text" placeholder="Email/Username"
                aria-label="Email o Nombre de Usuario" />
        </div>
        <p v-if="form.errors.login" class="er_nw">{{ form.errors.login }}</p>

        <!-- Password -->
        <div class="login_form_item">
            <i class="e_mc fa-solid fa-lock"></i>
            <input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="Contraseña"
                aria-label="Contraseña" class="pass" />
            <i class="eye_mc fa-solid" :class="showPassword ? 'fa-eye' : 'fa-eye-slash'" @click="togglePassword"
                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"></i>
        </div>

        <!-- Botón login -->
        <div class="login_form_item">
            <button type="submit" id="Ingresar">
                Iniciar Sesión
                <span v-if="loading" id="loader-btn" class="spinner-border spinner-border-sm"></span>
            </button>
        </div>

        <!-- Olvidé contraseña -->
        <div class="login_form_item" v-if="passwordRequestUrl">
            <a :href="passwordRequestUrl" class="forget_password">¿Olvidaste tu contraseña?</a>
        </div>

        <!-- Registro -->
        <div class="login_form_item" v-if="register">
            <a href="/register">¿No tienes una cuenta? Regístrate</a>
        </div>
    </form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/utils/toastNotify'
import { AuthService } from '@/services/authService'

interface Props {
    register?: boolean
    passwordRequestUrl?: string
}

const props = defineProps<Props>()
const { register = false, passwordRequestUrl = '' } = props

const form = reactive({
    login: '',
    password: '',
    remember_me: false,
    errors: {} as Record<string, string>
})

const showPassword = ref(false)
const loading = ref(false)

function togglePassword() {
    showPassword.value = !showPassword.value
}

function clearErrors() {
    form.errors = {}
}

function setErrors(errors: Array<{ field: string; message: string }>) {
    form.errors = {}
    errors.forEach(error => {
        form.errors[error.field] = error.message
    })
}

async function submit() {
    if (!form.login || !form.password) {
        showToast('Toda la información es requerida', 'error')
        return
    }

    loading.value = true
    clearErrors()

    try {
        const response = await AuthService.login({
            login: form.login,
            password: form.password,
            remember_me: form.remember_me
        })

        if (response.success) {
            showToast(response.message || 'Inicio de sesión exitoso', 'success')
            router.visit('/dashboard')
        } else {
            if (response.errors) setErrors(response.errors)
            showToast(response.message || 'Error en el inicio de sesión', 'error')
        }
    } catch (error) {
        console.error('Error en login:', error)
        showToast('Error de conexión. Intenta nuevamente.', 'error')
    } finally {
        loading.value = false
        form.password = ''
    }
}
</script>

<style scoped>
.login_form_item {
    margin-bottom: 1rem;
}

.er_nw {
    color: red;
    font-size: 0.875rem;
}

.pass {
    padding-right: 2rem;
}

.eye_mc {
    position: absolute;
    right: 0.5rem;
    top: 0.75rem;
    cursor: pointer;
}
</style>
