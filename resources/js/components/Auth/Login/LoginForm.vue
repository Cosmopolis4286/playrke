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
import { useToast } from 'vue-toastification'
import { AuthService } from '@/services/authService'
import { router } from '@inertiajs/vue3'

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

const toast = useToast()

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
        toast.error('Toda la información es requerida', { timeout: 3500, position: 'bottom-center' })
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
            toast.success(response.message || 'Inicio de sesión exitoso', { timeout: 2000, position: 'bottom-center' })
            router.visit('/dashboard')
        } else {
            if (response.errors) setErrors(response.errors)
            toast.error(response.message || 'Error en el inicio de sesión', { timeout: 4000, position: 'bottom-center' })
        }
    } catch (error) {
        console.error('Error en login:', error)
        toast.error('Error de conexión. Intenta nuevamente.', { timeout: 4000, position: 'bottom-center' })
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
