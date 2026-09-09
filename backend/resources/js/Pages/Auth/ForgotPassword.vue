<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import AuthFormField from '@js/Components/auth/AuthFormField.vue'
  import { useForgotPassword } from '@js/composables/auth/useForgotPassword'

  const { form, errors, loading, isBusy, submit } = useForgotPassword()

  const forgotSlides: CarouselSlide[] = [
    {
      icon: 'fa-solid fa-key',
      title: 'Recupera tu acceso',
      desc: 'Te enviaremos un enlace seguro para restablecer tu contraseña en segundos.'
    },
    {
      icon: 'fa-solid fa-shield-halved',
      title: 'Tu seguridad, primero',
      desc: 'El enlace de recuperación expira en 60 minutos para proteger tu cuenta.'
    },
    {
      icon: 'fa-solid fa-envelope-open-text',
      title: 'Revisa tu correo',
      desc: 'Si no ves el mensaje, revisa la carpeta de spam o correo no deseado.'
    },
    {
      icon: 'fa-solid fa-rotate-right',
      title: 'Vuelve rápido',
      desc: 'Restablece tu contraseña y retoma tus juegos sin perder tu progreso.'
    }
  ]
</script>

<template>
  <GuestLayout title="Recuperar Contraseña" :slides="forgotSlides" tagline-icon="fa-key">
    <template #tagline>Recupera tu acceso</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <AuthFormField
          v-model="form.email"
          type="email"
          icon="fa-envelope"
          placeholder="Correo electrónico"
          maxlength="60"
          required
          :disabled="isBusy"
          :error="errors.email"
        />

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
