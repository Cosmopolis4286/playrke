<script setup lang="ts">
  import { computed } from 'vue'
  import { usePage, useForm, router } from '@inertiajs/vue3'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'

  const props = defineProps<{
    verificationSendUrl: string
    logoutUrl: string
  }>()

  const page = usePage<{ flash?: { status?: string } }>()
  const justSent = computed(() => page.props.flash?.status === 'verification-link-sent')

  const form = useForm({})

  const resend = () => {
    form.post(props.verificationSendUrl)
  }

  const logout = () => {
    router.post(props.logoutUrl)
  }
</script>

<template>
  <GuestLayout title="Verifica tu correo" tagline-icon="fa-envelope-circle-check">
    <template #tagline>Un último paso</template>

    <template #form-content>
      <div class="auth-form__helper" style="margin-bottom: 16px; font-size: 0.82em; color: var(--gray-1);">
        Te enviamos un enlace de verificación a tu correo. Haz clic en él para activar tu cuenta.
        Si no lo ves, revisa la carpeta de spam.
      </div>

      <p v-if="justSent" class="auth-form__helper" style="color: #34d399;">
        Enlace de verificación reenviado correctamente.
      </p>

      <button type="button" class="auth-form__submit" :disabled="form.processing" @click="resend">
        <span class="auth-form__submit-text">Reenviar enlace de verificación</span>
        <span v-if="form.processing" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
      </button>

      <p class="auth-form__helper">
        <a href="#" @click.prevent="logout">Cerrar sesión</a>
      </p>
    </template>
  </GuestLayout>
</template>
