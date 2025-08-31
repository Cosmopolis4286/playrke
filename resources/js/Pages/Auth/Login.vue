<template>
    <AuthLayout>
        <template #pre-footer>
            <div v-if="register" class="login_footer_ck">
                <a href="/register">
                    <span class="pre_tx_link">¿No tienes una cuenta?</span>
                    <span class="sep_r"></span>
                    <span class="user_tx_link">Regístrate</span>
                </a>
            </div>
        </template>

        <LoginForm :register="register" :passwordRequestUrl="passwordRequestUrl" />
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/Components/Layouts/AuthLayout.vue'
import LoginForm from '@/Components/Auth/Login/LoginForm.vue'

interface Props {
    register: boolean
    passwordRequestUrl: string
}

const props = defineProps<Props>()
const register = props.register
const passwordRequestUrl = props.passwordRequestUrl
</script>
