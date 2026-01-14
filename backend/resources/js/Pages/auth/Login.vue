<template>
  <GuestLayout title="Iniciar sesión">
    <template #header-text>
      <p v-if="errors.login" class="auth-form__error">
        {{ errors.login }}
      </p>
    </template>
    <template #form-content>
      <form novalidate @submit.prevent="submit">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-user" aria-hidden="true"></i>
          <input
            v-model.trim="form.login"
            type="text"
            class="auth-form__input"
            placeholder="Email or Username"
            aria-label="Email or Username"
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
            placeholder="Password"
            aria-label="Password"
            required
            :disabled="isBusy"
          />
          <button
            type="button"
            class="auth-form__toggle-password"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @click="togglePassword"
          >
            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
          </button>
        </fieldset>
        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Iniciar sesión</span>
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
        <p class="auth-form__helper">
          <Link href="/forgot-password">¿Olvidaste tu contraseña?</Link>
        </p>
      </form>
    </template>
    <template #footer-content>
      <Link href="/register" class="auth-footer__link">
        <span>¿No tienes una cuenta?</span>
        <strong>Crear una</strong>
      </Link>
    </template>
  </GuestLayout>
</template>

<script setup lang="ts">
  import { reactive, ref, computed } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import backendClient, { getCsrfCookie } from '@js/plugins/axios'

  interface LoginPayload {
    login: string
    password: string
  }

  const form = reactive<LoginPayload>({
    login: '',
    password: ''
  })

  const errors = reactive({
    login: ''
  })

  const loading = ref(false)
  const showPassword = ref(false)

  const isBusy = computed(() => loading.value)

  /**
   * Toggle password visibility
   */
  const togglePassword = () => {
    showPassword.value = !showPassword.value
  }

  /**
   * Submit login (Laravel Sanctum session-based)
   */
  const submit = async (): Promise<void> => {
    if (isBusy.value) return

    errors.login = ''

    if (!form.login || !form.password) {
      errors.login = 'Todos los campos son obligatorios'
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()

      await backendClient.post('/api/auth/login', {
        login: form.login,
        password: form.password
      })

      // Redirección dura para sesión
      window.location.replace('/dashboard')
    } catch (error: any) {
      errors.login =
        error?.response?.status === 422
          ? 'Credenciales inválidas'
          : 'Error inesperado. Intenta nuevamente.'
    } finally {
      loading.value = false
    }
  }
</script>
