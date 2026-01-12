<template>
  <GuestLayout title="Registrarse">
    <template #logo-text> Crea tu cuenta </template>

    <template #auth-form>
      <form @submit.prevent="submit" id="form-register">
        <!-- Email -->
        <div id="grupo__email">
          <div class="login_form_item">
            <label for="email" class="sr-only">E-mail</label>
            <i class="e_mc fa-solid fa-envelope"></i>
            <input
              id="email"
              type="email"
              v-model.trim="form.email"
              placeholder="E-mail"
              required
              :disabled="isBusy"
            />
          </div>
          <p class="formulario__input-error" v-if="errors.email">
            {{ errors.email }}
          </p>
        </div>

        <!-- Username -->
        <div id="grupo__username">
          <div class="login_form_item">
            <label for="username" class="sr-only">Nombre de Usuario</label>
            <i class="e_mc fa-solid fa-user"></i>
            <input
              id="username"
              type="text"
              v-model.trim="form.username"
              placeholder="Nombre de Usuario"
              required
              :disabled="isBusy"
            />
          </div>
          <p class="formulario__input-error" v-if="errors.username">
            {{ errors.username }}
          </p>
        </div>

        <!-- Country -->
        <div class="login_form_item">
          <label class="sr-only">País</label>
          <i class="e_mc fa-solid fa-earth-americas"></i>
          <i class="eye_down fa-solid fa-chevron-down"></i>

          <div id="dropdown_select" @click="toggleCountries">
            {{ selectedCountry?.name || 'Seleccione su país' }}
          </div>

          <input type="hidden" :value="form.country" required />

          <div id="dropdown_list" v-show="showCountries">
            <div
              v-for="country in countries"
              :key="country.id"
              class="dropdown_list_item"
              @mousedown.prevent="selectCountry(country)"
            >
              {{ country.name }}
            </div>
          </div>
        </div>

        <!-- Password -->
        <div id="grupo__password">
          <div class="login_form_item">
            <label for="password" class="sr-only">Contraseña</label>
            <i class="e_mc fa-solid fa-lock"></i>
            <input
              id="password"
              type="password"
              v-model="form.password"
              placeholder="Contraseña"
              required
              :disabled="isBusy"
            />
          </div>
          <p class="formulario__input-error" v-if="errors.password">
            {{ errors.password }}
          </p>
        </div>

        <!-- Password confirmation -->
        <div class="login_form_item">
          <label for="password2" class="sr-only">Repetir Contraseña</label>
          <i class="e_mc fa-solid fa-unlock"></i>
          <input
            id="password2"
            type="password"
            v-model="form.password_confirmation"
            placeholder="Repetir Contraseña"
            required
            :disabled="isBusy"
          />
        </div>

        <p class="er_nw" v-if="passwordMismatch">Las contraseñas no son iguales.</p>

        <!-- Terms -->
        <div class="login_form_item_check">
          <input
            type="checkbox"
            id="politicas"
            v-model="form.politicas"
            required
            :disabled="isBusy"
          />
          <label for="politicas">
            <div id="acep_term">
              Acepto los
              <a href="#" class="terms">Términos y Condiciones</a>
              y la
              <a href="#" class="priv">Política de Privacidad</a>
            </div>
          </label>
        </div>

        <!-- Submit -->
        <div class="login_form_item">
          <button type="submit" class="btn btn-primary" :disabled="isBusy">
            Registrarse
            <span v-if="isBusy" class="spinner-border spinner-border-sm" role="status" />
          </button>
        </div>
      </form>
    </template>

    <template #footer-content>
      <div class="login_footer_ck">
        <a href="/login">
          <span class="pre_tx_link">¿Ya tienes una cuenta?</span>
          <span class="sep_r"></span>
          <span class="user_tx_link">Inicia sesión</span>
        </a>
      </div>
    </template>
  </GuestLayout>
</template>
<script setup lang="ts">
  import { reactive, ref, computed } from 'vue'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import backendClient, { getCsrfCookie } from '@js/plugins/axios'

  interface Country {
    id: number
    name: string
  }

  const props = defineProps<{
    countries: Country[]
  }>()

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

    loading.value = true
    Object.keys(errors).forEach(k => delete errors[k])

    try {
      await getCsrfCookie()

      await backendClient.post('/api/auth/register', form)

      window.location.replace('/dashboard')
    } catch (error: any) {
      if (error?.response?.status === 422) {
        Object.assign(errors, error.response.data.errors)
        return
      }

      alert('Error inesperado al registrarse')
    } finally {
      loading.value = false
    }
  }
</script>
