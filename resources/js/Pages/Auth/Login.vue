<template>
    <AuthLayout>
        <template #form>
            <form @submit.prevent="submit">
                <input v-model="form.email" type="email" placeholder="Email" />
                <input v-model="form.password" type="password" placeholder="Password" />
                <button type="submit">Ingresar</button>
            </form>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/components/layouts/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: ''
})

function submit() {
    form.post('/login')
}
</script>
