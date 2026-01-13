<template>
  <GuestLayout title="Registrarse">
    <template #header-text> Crea tu cuenta </template>

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
        <p v-if="passwordMismatch" class="auth-form__error">Las contraseñas no coinciden</p>

        <!-- Terms -->
        <label class="auth-form__checkbox">
          <input type="checkbox" v-model="form.politicas" required :disabled="isBusy" />
          <span class="auth-form__checkbox-text">
            Acepto los
            <a href="#" class="auth-form__link">Términos y Condiciones</a>
            y la
            <a href="#" class="auth-form__link">Política de Privacidad</a>
          </span>
        </label>

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

  interface Country {
    id: number
    name: string
  }

  defineProps<{ countries: Country[] }>()

  const form = reactive({
    email: '',
    username: '',
    country: '',
    password: '',
    password_confirmation: '',
    politicas: false
  })

  const errors = reactive<Record<string, string>>({})
  const loading = ref(false)
  const showCountries = ref(false)
  const selectedCountry = ref<Country | null>(null)

  const isBusy = computed(() => loading.value)

  const passwordMismatch = computed(
    () =>
      form.password && form.password_confirmation && form.password !== form.password_confirmation
  )

  const toggleCountries = () => {
    showCountries.value = !showCountries.value
  }

  const selectCountry = (country: Country) => {
    selectedCountry.value = country
    form.country = String(country.id)
    showCountries.value = false
  }

  const submit = async () => {
    if (isBusy.value || passwordMismatch.value) return

    Object.keys(errors).forEach(k => delete errors[k])
    loading.value = true

    try {
      await getCsrfCookie()

      await backendClient.post('/api/auth/register', form)

      window.location.replace('/dashboard')
    } catch (error: any) {
      if (error?.response?.status === 422) {
        Object.assign(errors, error.response.data.errors)
      } else {
        errors.email = 'Error inesperado. Intenta nuevamente.'
      }
    } finally {
      loading.value = false
    }
  }
</script>
