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
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

interface Props {
    register?: boolean
    passwordRequestUrl: string
}

const props = defineProps<Props>()

const form = useForm({
    login: '',
    password: '',
})

const showPassword = ref(false)
const loading = ref(false)

/** Toggle mostrar/ocultar contraseña */
function togglePassword() {
    showPassword.value = !showPassword.value
}

/** Enviar formulario de login */
function submit() {
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
    form.post('/login', {
        onFinish: () => {
            loading.value = false
            form.reset('password')
        },
    })
}
</script>
