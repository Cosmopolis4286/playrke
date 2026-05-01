<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { useLogin } from '@js/composables/auth/useLogin'
  import { usePasswordToggle } from '@js/composables/utils/usePasswordToggle'
  import DemoCredentials from '@js/Components/Auth/DemoCredentials.vue'
  import LoadingOverlay from '@js/Components/Games/Trivia/Shared/LoadingOverlay.vue'

  const props = defineProps<{ register: Boolean }>()

  const { form, loading, isBusy, submit } = useLogin()
  const { showPassword, togglePassword } = usePasswordToggle()

  const fillFromDemo = ({ login, password }: { login: string; password: string }) => {
    form.login    = login
    form.password = password
  }
</script>

<template>
  <GuestLayout title="Iniciar sesión">
    <template #tagline>Bienvenido de vuelta</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-user" aria-hidden="true"></i>
          <input
            v-model.trim="form.login"
            type="text"
            class="auth-form__input"
            placeholder="Email o Usuario"
            aria-label="Email o Usuario"
            maxlength="40"
            required
            :disabled="isBusy"
          />
        </fieldset>

        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-lock"></i>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-form__input"
            placeholder="Contraseña"
            aria-label="Contraseña"
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

        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Iniciar Sesión</span>
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>

        <p class="auth-form__helper">
          <Link href="/forgot-password">¿Olvidaste tu contraseña?</Link>
        </p>
      </form>
    </template>

    <template v-if="register" #footer-content>
      <Link href="/register" class="auth-footer__link">
        <span>¿No tienes una cuenta?</span>
        <strong>Crear una</strong>
      </Link>
    </template>
  </GuestLayout>

  <DemoCredentials @fill-credentials="fillFromDemo" />

  <Teleport to="body">
    <LoadingOverlay v-if="loading" message="Iniciando sesión..." />
  </Teleport>
</template>
