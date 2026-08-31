<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { useForm } from '@inertiajs/vue3'
  import AuthFormField from '@js/Components/auth/AuthFormField.vue'

  const props = defineProps<{
    passwordConfirmUrl: string
  }>()

  const form = useForm({ password: '' })

  const submit = () => {
    form.post(props.passwordConfirmUrl, {
      onFinish: () => form.reset('password'),
    })
  }
</script>

<template>
  <GuestLayout title="Confirma tu contraseña">
    <template #tagline>Zona segura</template>
    <template #header-text>
      <p style="font-size: 0.82em; color: var(--gray-1); margin: 0;">
        Esta es un área protegida. Confirma tu contraseña antes de continuar.
      </p>
    </template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <AuthFormField
          v-model="form.password"
          type="password"
          icon="fa-lock"
          placeholder="Contraseña"
          required
          :disabled="form.processing"
          :error="form.errors.password"
        />

        <button type="submit" class="auth-form__submit" :disabled="form.processing">
          <span class="auth-form__submit-text">Confirmar</span>
          <span v-if="form.processing" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
      </form>
    </template>
  </GuestLayout>
</template>
