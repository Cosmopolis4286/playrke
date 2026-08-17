<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { useResetPassword } from '@js/composables/auth/useResetPassword'
  import { usePasswordToggle } from '@js/composables/utils/usePasswordToggle'

  const props = defineProps<{
    token: string
    email: string
  }>()

  const { form, loading, isBusy, submit } = useResetPassword(props.token, props.email)
  const { showPassword, togglePassword } = usePasswordToggle()
  const { showPassword: showConfirm, togglePassword: toggleConfirm } = usePasswordToggle()

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
  <GuestLayout title="Restablecer Contraseña" :slides="resetSlides">
    <template #tagline>Restablece tu contraseña</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-envelope" aria-hidden="true"></i>
          <input
            :value="form.email"
            type="email"
            class="auth-form__input"
            placeholder="Correo electrónico"
            aria-label="Correo electrónico"
            readonly
            disabled
          />
        </fieldset>

        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-lock" aria-hidden="true"></i>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-form__input"
            placeholder="Nueva contraseña"
            aria-label="Nueva contraseña"
            required
            :disabled="isBusy"
          />
          <button
            type="button"
            class="auth-form__toggle-password"
            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            @click="togglePassword"
          >
            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
          </button>
        </fieldset>

        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-lock" aria-hidden="true"></i>
          <input
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            class="auth-form__input"
            placeholder="Confirmar contraseña"
            aria-label="Confirmar contraseña"
            required
            :disabled="isBusy"
          />
          <button
            type="button"
            class="auth-form__toggle-password"
            :aria-label="showConfirm ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            @click="toggleConfirm"
          >
            <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
          </button>
        </fieldset>

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
