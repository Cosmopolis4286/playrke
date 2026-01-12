<template>
  <GuestLayout title="Iniciar sesión">
    <template #logo-text> Ingresa a tu cuenta </template>

    <template #auth-form>
      <form @submit.prevent="submit" id="form-login">
        <div>
          <label for="login">Correo electrónico</label>
          <input
            id="login"
            v-model.trim="form.login"
            type="text"
            autocomplete="username"
            :disabled="isBusy"
            required
          />
        </div>

        <div>
          <label for="password">Contraseña</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            :disabled="isBusy"
            required
          />
        </div>

        <button type="submit" :disabled="isBusy">
          {{ isBusy ? 'Ingresando…' : 'Iniciar sesión' }}
        </button>
      </form>
    </template>

    <template #footer-content>
      <p class="text-center">
        ¿No tienes cuenta?
        <a href="/register" class="text-primary">Regístrate</a>
      </p>
    </template>
  </GuestLayout>
</template>

<script setup lang="ts">
  import { reactive, ref, computed } from 'vue'
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

  const loading = ref(false)

  // Estado único para bloquear submit + inputs
  const isBusy = computed(() => loading.value)

  /**
   * Login por sesión (Sanctum)
   */
  const submit = async (): Promise<void> => {
    if (isBusy.value) return

    loading.value = true

    try {
      // Requerido solo para auth basada en sesión
      await getCsrfCookie()

      await backendClient.post('/api/auth/login', {
        login: form.login,
        password: form.password
      })

      // Redirección dura para inicializar sesión correctamente
      window.location.replace('/dashboard')
    } catch (error: any) {
      console.error('Error de login:', error)

      const message =
        error?.response?.status === 422
          ? 'Credenciales inválidas'
          : 'Error inesperado. Intenta nuevamente.'

      alert(message)
    } finally {
      loading.value = false
    }
  }
</script>
