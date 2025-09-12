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
        <div class="login_form_item">
            <a :href="passwordRequestUrl" class="forget_password">¿Olvidaste tu contraseña?</a>
        </div>
    </form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { AuthService } from '@/services/authService'
import { router } from '@inertiajs/vue3'

interface Props {
    register?: boolean
    passwordRequestUrl: string
}

const props = defineProps<Props>()

// Usar reactive en lugar de useForm para mayor control
const form = reactive({
    login: '',
    password: '',
    remember_me: false,
    errors: {} as Record<string, string>
})

const showPassword = ref(false)
const loading = ref(false)

/** Toggle mostrar/ocultar contraseña */
function togglePassword() {
    showPassword.value = !showPassword.value
}

/** Limpiar errores del formulario */
function clearErrors() {
    form.errors = {}
}

/** Mostrar errores del servidor */
function setErrors(errors: Array<{ field: string; message: string }>) {
    form.errors = {}
    errors.forEach(error => {
        form.errors[error.field] = error.message
    })
}

/** Enviar formulario de login */
async function submit() {
    if (!form.login || !form.password) {
        if (!document.querySelector('.notyf__toast')) {
            new Notyf().error({
                message: 'Toda la información es requerida',
                duration: 3450,
                ripple: true,
                position: { x: 'center', y: 'bottom' },
            })
        }
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
            // Login exitoso
            new Notyf().success({
                message: response.message || 'Inicio de sesión exitoso',
                duration: 2000,
                ripple: true,
                position: { x: 'center', y: 'bottom' },
            })

            // Redirigir al dashboard
            router.visit('/dashboard')
        } else {
            // Error en el login
            if (response.errors) {
                setErrors(response.errors)
            }
            
            new Notyf().error({
                message: response.message || 'Error en el inicio de sesión',
                duration: 4000,
                ripple: true,
                position: { x: 'center', y: 'bottom' },
            })
        }
    } catch (error) {
        console.error('Error en login:', error)
        new Notyf().error({
            message: 'Error de conexión. Intenta nuevamente.',
            duration: 4000,
            ripple: true,
            position: { x: 'center', y: 'bottom' },
        })
    } finally {
        loading.value = false
        form.password = '' // Limpiar contraseña
    }
}
</script>
