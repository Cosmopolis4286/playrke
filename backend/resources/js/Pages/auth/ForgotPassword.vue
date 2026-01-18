<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { useLogin } from '@js/composables/auth/useLogin'
  import { usePasswordToggle } from '@js/composables/utils/usePasswordToggle'

  const { form, errors, loading, isBusy, submit } = useLogin()
  const { showPassword, togglePassword } = usePasswordToggle()
</script>

<template>
  <GuestLayout title="Reestablecer Contraseña">
    <template #header-text>
      <div class="auth-form__field">
        <div class="alert alert-success mb-0" role="alert">Hemos enviado el link de reseteo</div>
      </div>
    </template>
    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-envelope" aria-hidden="true"></i>
          <input
            v-model.trim="form.login"
            type="email"
            class="auth-form__input"
            placeholder="E-mail"
            aria-label="E-mail"
            maxlength="40"
            required
            :disabled="isBusy"
          />
        </fieldset>
        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Enviar link de reseteo</span>
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
      </form>
    </template>
    <template #footer-content>
      <Link href="/login" class="auth-footer__link">
        <span>¿Quieres volver a intentar?</span>
        <strong>Iniciar Sesión</strong>
      </Link>
    </template>
  </GuestLayout>
</template>
