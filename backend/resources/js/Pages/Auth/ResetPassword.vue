<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import AuthFormField from '@js/Components/auth/AuthFormField.vue'
  import PasswordStrengthMeter from '@js/Components/auth/PasswordStrengthMeter.vue'
  import { useResetPassword } from '@js/composables/auth/useResetPassword'

  const props = defineProps<{
    token: string
    email: string
  }>()

  const { form, loading, isBusy, submit, markTouched, fieldError } = useResetPassword(props.token, props.email)

  const resetSlides: CarouselSlide[] = [
    {
      icon: 'fa-solid fa-key',
      title: 'Elige tu nueva contraseña',
      desc: 'Usa una contraseña segura que no hayas usado antes en la plataforma.'
    },
    {
      icon: 'fa-solid fa-shield-halved',
      title: 'Tu seguridad, primero',
      desc: 'Este enlace es de un solo uso y expira por tu seguridad.'
    },
    {
      icon: 'fa-solid fa-rotate-right',
      title: 'Vuelve rápido',
      desc: 'Restablece tu contraseña y retoma tus juegos sin perder tu progreso.'
    }
  ]
</script>

<template>
  <GuestLayout title="Restablecer Contraseña" :slides="resetSlides" tagline-icon="fa-shield-halved">
    <template #tagline>Restablece tu contraseña</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <AuthFormField
          :model-value="form.email"
          type="email"
          icon="fa-envelope"
          placeholder="Correo electrónico"
          readonly
          disabled
        />

        <AuthFormField
          v-model="form.password"
          type="password"
          icon="fa-lock"
          placeholder="Nueva contraseña"
          required
          :disabled="isBusy"
          :error="fieldError('password')"
          @input="markTouched('password')"
        />
        <PasswordStrengthMeter v-if="form.password" :password="form.password" />

        <AuthFormField
          v-model="form.password_confirmation"
          type="password"
          icon="fa-lock"
          placeholder="Confirmar contraseña"
          required
          :disabled="isBusy"
          :error="fieldError('password_confirmation')"
          @input="markTouched('password_confirmation')"
        />

        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Restablecer contraseña</span>
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
