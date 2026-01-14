<template>
  <GuestLayout title="Registrarse">
    <template #form-content>
      <form novalidate @submit.prevent="submit" class="auth-form">
        <!-- Email -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-envelope" aria-hidden="true"></i>
          <input
            v-model.trim="form.email"
            type="email"
            class="auth-form__input"
            placeholder="E-mail"
            aria-label="E-mail"
            required
            :disabled="isBusy"
          />
        </fieldset>
        <p v-if="errors.email" class="auth-form__error">{{ errors.email }}</p>

        <!-- Username -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-user" aria-hidden="true"></i>
          <input
            v-model.trim="form.username"
            type="text"
            class="auth-form__input"
            placeholder="Nombre de usuario"
            aria-label="Nombre de usuario"
            required
            :disabled="isBusy"
          />
        </fieldset>
        <p v-if="errors.username" class="auth-form__error">{{ errors.username }}</p>

        <!-- Country dropdown -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-earth-americas" aria-hidden="true"></i>
          <AuthDropdown
            v-model="form.country"
            :options="countries.map(c => ({ value: c.id, label: c.name }))"
            placeholder="Seleccione su país"
            :disabled="isBusy"
          />
        </fieldset>
        <p v-if="errors.country" class="auth-form__error">{{ errors.country }}</p>

        <!-- Password -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-lock z-50" aria-hidden="true"></i>
          <input
            v-model="form.password"
            type="password"
            class="auth-form__input"
            placeholder="Contraseña"
            aria-label="Contraseña"
            required
            :disabled="isBusy"
          />
        </fieldset>
        <p v-if="errors.password" class="auth-form__error">{{ errors.password }}</p>

        <!-- Password confirmation -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-unlock" aria-hidden="true"></i>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="auth-form__input"
            placeholder="Repetir contraseña"
            aria-label="Repetir contraseña"
            required
            :disabled="isBusy"
          />
        </fieldset>
        <p v-if="errors.password_confirmation" class="auth-form__error">
          {{ errors.password_confirmation }}
        </p>

        <!-- Terms -->
        <div class="auth-form__checkbox">
          <input type="checkbox" v-model="form.politics" required :disabled="isBusy" />
          <span class="auth-form__checkbox-text">
            Acepto los
            <a href="#" class="auth-form__link">Términos y Condiciones</a>
            y la
            <a href="#" class="auth-form__link">Política de Privacidad</a>
          </span>
        </div>

        <p v-if="errors.politics" class="auth-form__error">{{ errors.politics }}</p>

        <!-- Submit -->
        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          Registrarse
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
      </form>
    </template>

    <template #footer-content>
      <Link href="/login" class="auth-footer__link">
        <span>¿Ya tienes una cuenta?</span>
        <strong>Inicia sesión</strong>
      </Link>
    </template>
  </GuestLayout>
</template>

<script setup lang="ts">
  import { reactive, ref, computed } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import backendClient, { getCsrfCookie } from '@js/plugins/axios'
  import AuthDropdown from '@js/components/auth/AuthDropdown.vue'
  import { useRegisterValidation } from '@js/composables/user/useRegisterValidation'

  interface Country {
    id: number
    name: string
  }

  /**
   * Props recibidas con la lista de países disponibles para el dropdown.
   */
  defineProps<{ countries: Country[] }>()

  // Estado reactivo del formulario
  const form = reactive({
    email: '',
    username: '',
    country: '',
    password: '',
    password_confirmation: '',
    politics: false
  })

  // Estado para controlar el loading y bloqueo de inputs
  const loading = ref(false)

  // Computed para simplificar condición de formulario bloqueado
  const isBusy = computed(() => loading.value)

  // Uso del composable que contiene la validación del formulario
  const { errors, validate } = useRegisterValidation(form)

  /**
   * Envía el formulario de registro al backend.
   * - Previene envíos si ya está cargando o la validación falla.
   * - Maneja errores de validación desde backend y errores inesperados.
   */
  const submit = async () => {
    if (isBusy.value) return

    // Ejecuta la validación con yup; si no es válido, detiene submit
    const valid = await validate()
    if (!valid) return

    loading.value = true
    try {
      // Obtener CSRF cookie antes de hacer la petición POST
      await getCsrfCookie()

      // Enviar datos al endpoint de registro
      await backendClient.post('/api/auth/register', form)

      // Redirigir al dashboard si todo OK
      window.location.replace('/dashboard')
    } catch (error: any) {
      if (error?.response?.status === 422) {
        // Asigna errores de validación que venga del backend
        Object.assign(errors, error.response.data.errors)
      } else {
        // Mensaje genérico para errores inesperados
        errors.email = 'Error inesperado. Intenta nuevamente.'
      }
    } finally {
      loading.value = false
    }
  }
</script>
