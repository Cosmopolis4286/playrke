<script setup lang="ts">
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import AuthDropdown from '@js/Components/Auth/AuthDropdown.vue'
  import { useRegisterForm } from '@js/composables/auth/useRegister'
  import { usePasswordToggle } from '@js/composables/utils/usePasswordToggle'

  interface Country { id: number; name: string }
  const props = defineProps<{ countries: Country[] }>()

  const { form, errors, loading, isBusy, submit, touched, submitted, markTouched } = useRegisterForm()
  const { showPassword, togglePassword } = usePasswordToggle()
</script>

<template>
  <GuestLayout title="Registrarse">
    <template #tagline>Únete a la élite</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit" class="auth-form">

        <!-- Email -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-envelope" aria-hidden="true"></i>
          <input
            v-model.trim="form.email"
            type="email"
            class="auth-form__input"
            placeholder="Correo electrónico"
            aria-label="Correo electrónico"
            required
            :disabled="isBusy"
            @input="markTouched('email')"
          />
        </fieldset>
        <p v-if="errors.email && (submitted || touched.email)" class="auth-form__error">
          {{ errors.email }}
        </p>

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
            @input="markTouched('username')"
          />
        </fieldset>
        <p v-if="errors.username && (submitted || touched.username)" class="auth-form__error">
          {{ errors.username }}
        </p>

        <!-- País -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-earth-americas" aria-hidden="true"></i>
          <AuthDropdown
            v-model="form.country"
            :options="countries.map(c => ({ value: c.id, label: c.name }))"
            placeholder="Selecciona tu país"
            :disabled="isBusy"
            @change="markTouched('country')"
          />
        </fieldset>
        <p v-if="errors.country && (submitted || touched.country)" class="auth-form__error">
          {{ errors.country }}
        </p>

        <!-- Contraseña -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-lock" aria-hidden="true"></i>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-form__input"
            placeholder="Contraseña"
            aria-label="Contraseña"
            required
            :disabled="isBusy"
            @input="markTouched('password')"
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
        <p v-if="errors.password && (submitted || touched.password)" class="auth-form__error">
          {{ errors.password }}
        </p>

        <!-- Confirmar contraseña -->
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-unlock" aria-hidden="true"></i>
          <input
            v-model="form.password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            class="auth-form__input"
            placeholder="Repetir contraseña"
            aria-label="Repetir contraseña"
            required
            :disabled="isBusy"
            @input="markTouched('password_confirmation')"
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
        <p v-if="errors.password_confirmation && (submitted || touched.password_confirmation)" class="auth-form__error">
          {{ errors.password_confirmation }}
        </p>

        <!-- Términos -->
        <div class="auth-form__checkbox">
          <input
            type="checkbox"
            v-model="form.politics"
            required
            :disabled="isBusy"
            @change="markTouched('politics')"
          />
          <span class="auth-form__checkbox-text">
            Acepto los
            <a href="#" class="auth-form__link">Términos y Condiciones</a>
            y la
            <a href="#" class="auth-form__link">Política de Privacidad</a>
          </span>
        </div>
        <p v-if="errors.politics && (submitted || touched.politics)" class="auth-form__error">
          {{ errors.politics }}
        </p>

        <button type="submit" class="auth-form__submit" :disabled="isBusy">
          <span class="auth-form__submit-text">Registrarse</span>
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
