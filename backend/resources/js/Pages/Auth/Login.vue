<script setup lang="ts">
  import { ref, computed, watch, nextTick } from 'vue'
  import { usePage } from '@inertiajs/vue3'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { useLogin } from '@js/composables/auth/useLogin'
  import { useTurnstile } from '@js/composables/common/useTurnstile'
  import AuthFormField from '@js/Components/auth/AuthFormField.vue'
  import DemoCredentials from '@js/Components/auth/DemoCredentials.vue'
  import LoadingOverlay from '@js/Components/games/Trivia/Shared/LoadingOverlay.vue'

  const props = defineProps<{ register: Boolean }>()

  const { form, errors, loading, isBusy, requiresTurnstile, submit } = useLogin()
  const { siteKey, token: turnstileToken, render: renderTurnstile, reset: resetTurnstile } = useTurnstile()

  const page = usePage<{ app?: { env?: string } }>()
  const isDev = computed(() => ['local', 'testing'].includes(page.props.app?.env ?? ''))

  const turnstileContainer = ref<HTMLElement | null>(null)

  watch(requiresTurnstile, async (needed) => {
    if (needed) {
      await nextTick()
      if (turnstileContainer.value) renderTurnstile(turnstileContainer.value)
    }
  })

  const onSubmit = () => submit(turnstileToken.value || undefined).finally(() => {
    if (requiresTurnstile.value) resetTurnstile()
  })

  const fillFromDemo = ({ login, password }: { login: string; password: string }) => {
    form.login    = login
    form.password = password
  }
</script>

<template>
  <GuestLayout title="Iniciar sesión">
    <template #tagline>Bienvenido de vuelta</template>

    <template #form-content>
      <form novalidate @submit.prevent="onSubmit">
        <AuthFormField
          v-model="form.login"
          icon="fa-user"
          placeholder="Email o Usuario"
          maxlength="40"
          required
          :disabled="isBusy"
          :error="errors.login"
        />

        <AuthFormField
          v-model="form.password"
          type="password"
          icon="fa-lock"
          placeholder="Contraseña"
          required
          :disabled="isBusy"
          :error="errors.password"
        />

        <div class="auth-form__checkbox">
          <input id="remember" v-model="form.remember" type="checkbox" :disabled="isBusy" />
          <label for="remember" class="auth-form__checkbox-text">Recordarme</label>
        </div>

        <div v-if="requiresTurnstile && siteKey" ref="turnstileContainer" class="auth-form__turnstile"></div>

        <button type="submit" class="auth-form__submit" :disabled="isBusy || (requiresTurnstile && !turnstileToken)">
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

  <DemoCredentials v-if="isDev" @fill-credentials="fillFromDemo" />

  <Teleport to="body">
    <LoadingOverlay v-if="loading" message="Iniciando sesión..." />
  </Teleport>
</template>
