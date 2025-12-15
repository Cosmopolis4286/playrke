<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'

defineProps<{ register: boolean }>()

const form = useForm({
    login: '',
    password: '',
})
</script>

<template>

    <Head title="Tu Sitio Para Ganar" />

    <form @submit.prevent="form.post(route('login'))">
        <input v-model="form.login" placeholder="Email/Username" />
        <input v-model="form.password" type="password" />

        <button>
            Iniciar Sesión
        </button>
    </form>
</template>
