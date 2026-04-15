<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { useForgotPassword } from '@js/composables/auth/useForgotPassword'

  const { form, loading, isBusy, submit } = useForgotPassword()
</script>

<template>
  <GuestLayout title="Recuperar Contraseña">
    <template #tagline>Recupera tu acceso</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-envelope" aria-hidden="true"></i>
          <input
            v-model.trim="form.email"
            type="email"
            class="auth-form__input"
            placeholder="Correo electrónico"
            aria-label="Correo electrónico"
            maxlength="60"
            required
            :disabled="isBusy"
          />
        </fieldset>

        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Enviar link de recuperación</span>
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
      </form>
    </template>

    <template #footer-content>
      <Link href="/login" class="auth-footer__link">
        <span>¿Recuerdas tu contraseña?</span>
        <strong>Iniciar Sesión</strong>
      </Link>
    </template>
  </GuestLayout>
</template>
