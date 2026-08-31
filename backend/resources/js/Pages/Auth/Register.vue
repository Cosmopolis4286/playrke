<script setup lang="ts">
  import { ref, computed, watch, onMounted, nextTick } from 'vue'
  import GuestLayout from '@js/Layouts/GuestLayout.vue'
  import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import AuthDropdown from '@js/Components/auth/AuthDropdown.vue'
  import AuthFormField from '@js/Components/auth/AuthFormField.vue'
  import PasswordStrengthMeter from '@js/Components/auth/PasswordStrengthMeter.vue'
  import { useRegisterForm } from '@js/composables/auth/useRegister'
  import { usePhoneField } from '@js/composables/form/usePhoneField'
  import { useTurnstile } from '@js/composables/common/useTurnstile'
  import type { Country } from '@js/types/country'

  const props = defineProps<{ countries: Country[] }>()

  // Precarga de teléfono/país cuando se llega desde el CTA de "regístrate"
  // del banner de jugador externo (ver Pages/Games/Bingo/Game/Index.vue) —
  // permite que el registro se auto-vincule al historial de ExternalPlayer
  // (ver RegisteredUserController::linkExternalPlayerHistory()).
  const initialParams = new URLSearchParams(window.location.search)
  const prefillPhone = initialParams.get('phone') ?? ''
  const prefillCountry = initialParams.get('country') ?? ''

  const { form, errors, loading, isBusy, submit: submitForm, touched, submitted, markTouched, selectedIso2 } =
    useRegisterForm(props.countries, {
      phone: prefillPhone,
      country: prefillCountry,
    })
  const { siteKey, token: turnstileToken, render: renderTurnstile } = useTurnstile()

  const turnstileContainer = ref<HTMLElement | null>(null)
  const submit = () => submitForm(turnstileToken.value || undefined)

  // Teléfono — mismo composable reutilizable que el resto de formularios
  // (ver AdminPlayerForm.vue, AdminCustomerForm.vue, etc.)
  const phoneRef = ref(form.phone)
  watch(() => form.phone, v => { if (v !== phoneRef.value) phoneRef.value = v })
  watch(phoneRef, v => { if (v !== form.phone) form.phone = v })

  const { phoneMask, phonePlaceholder, phoneDialPrefix } =
    usePhoneField(selectedIso2, phoneRef, v => { phoneRef.value = v })

  watch(selectedIso2, (iso, prevIso) => {
    if (iso && iso !== prevIso) {
      nextTick(() => {
        if (!phoneRef.value?.trim()) phoneRef.value = phoneDialPrefix.value
      })
    }
  })

  onMounted(async () => {
    await nextTick()
    if (turnstileContainer.value) renderTurnstile(turnstileContainer.value)
  })

  const registerSlides: CarouselSlide[] = [
    {
      icon: 'fa-solid fa-user-plus',
      title: 'Crea tu cuenta gratis',
      desc: 'Regístrate en segundos y accede a cientos de salas de juego en tiempo real.'
    },
    {
      icon: 'fa-solid fa-gift',
      title: 'Bono de bienvenida',
      desc: 'Nuevos jugadores reciben créditos exclusivos para comenzar a ganar desde el primer día.'
    },
    {
      icon: 'fa-solid fa-earth-americas',
      title: 'Comunidad global',
      desc: 'Juega con personas de todo el mundo y compite en tablas de clasificación internacionales.'
    },
    {
      icon: 'fa-solid fa-lock',
      title: 'Tus datos, protegidos',
      desc: 'Registro seguro con verificación de identidad y cifrado de extremo a extremo.'
    }
  ]
</script>

<template>
  <GuestLayout title="Registrarse" :slides="registerSlides">
    <template #tagline>Únete a la élite</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit" class="auth-form">

        <!-- Email -->
        <AuthFormField
          v-model="form.email"
          type="email"
          icon="fa-envelope"
          placeholder="Correo electrónico"
          maxlength="80"
          required
          :disabled="isBusy"
          :error="(submitted || touched.email) ? errors.email : null"
          @input="markTouched('email')"
        />

        <!-- Username -->
        <AuthFormField
          v-model="form.username"
          icon="fa-user"
          placeholder="Nombre de usuario"
          maxlength="30"
          required
          :disabled="isBusy"
          :error="(submitted || touched.username) ? errors.username : null"
          @input="markTouched('username')"
        />

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

        <!-- Teléfono (opcional) — usePhoneField: máscara y patrón según país -->
        <AuthFormField
          v-model="phoneRef"
          type="tel"
          icon="fa-phone"
          :placeholder="phonePlaceholder"
          :mask="phoneMask ?? undefined"
          :disabled="isBusy"
          :error="(submitted || touched.phone) ? errors.phone : null"
          @input="markTouched('phone')"
        />

        <!-- Contraseña -->
        <AuthFormField
          v-model="form.password"
          type="password"
          icon="fa-lock"
          placeholder="Contraseña"
          required
          :disabled="isBusy"
          :error="(submitted || touched.password) ? errors.password : null"
          @input="markTouched('password')"
        />
        <PasswordStrengthMeter v-if="form.password" :password="form.password" />

        <!-- Confirmar contraseña -->
        <AuthFormField
          v-model="form.password_confirmation"
          type="password"
          icon="fa-unlock"
          placeholder="Repetir contraseña"
          required
          :disabled="isBusy"
          :error="(submitted || touched.password_confirmation) ? errors.password_confirmation : null"
          @input="markTouched('password_confirmation')"
        />

        <!-- Términos -->
        <div class="auth-form__checkbox">
          <input
            id="terms"
            type="checkbox"
            v-model="form.terms"
            required
            :disabled="isBusy"
            @change="markTouched('terms')"
          />
          <label for="terms" class="auth-form__checkbox-text">
            Acepto los
            <a href="/info/terms" target="_blank" class="auth-form__link">Términos y Condiciones</a>
            y la
            <a href="/info/privacy" target="_blank" class="auth-form__link">Política de Privacidad</a>
          </label>
        </div>
        <p v-if="errors.terms && (submitted || touched.terms)" class="auth-form__error">
          {{ errors.terms }}
        </p>

        <div v-if="siteKey" ref="turnstileContainer" class="auth-form__turnstile"></div>

        <button type="submit" class="auth-form__submit" :disabled="isBusy || (!!siteKey && !turnstileToken)">
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
