<template>
    <AuthLayout :subtitle="'Registro'" :register="false">
        <form @submit.prevent="handleSubmit" class="auth__form" id="form-register">
            <!-- Email -->
            <div class="auth__form-item" :class="{ 'auth__form-item--error': errors.email }">
                <i class="auth__input-icon fa-solid fa-envelope"></i>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="E-mail"
                    aria-label="E-mail"
                    class="auth__input"
                    :class="{ 'auth__input--error': errors.email }"
                    @input="validateEmail"
                    required
                />
            </div>
            <span v-if="errors.email" class="auth__error">{{ errors.email }}</span>
            <p v-if="emailError" class="auth__error">Ingrese un E-mail válido.</p>

            <!-- Username -->
            <div class="auth__form-item" :class="{ 'auth__form-item--error': errors.username }">
                <i class="auth__input-icon fa-solid fa-user"></i>
                <input
                    v-model="form.username"
                    type="text"
                    placeholder="Nombre de Usuario"
                    aria-label="Nombre de Usuario"
                    class="auth__input"
                    :class="{ 'auth__input--error': errors.username }"
                    @input="validateUsername"
                    maxlength="40"
                    required
                />
            </div>
            <span v-if="errors.username" class="auth__error">{{ errors.username }}</span>
            <p v-if="usernameError" class="auth__error">Su nombre de usuario debe tener de 4-16 caracteres</p>

            <!-- Country Dropdown -->
            <div class="auth__form-item">
                <div class="auth__dropdown">
                    <i class="auth__input-icon fa-solid fa-earth-americas"></i>
                    <div
                        class="auth__dropdown-trigger"
                        @click="toggleCountryDropdown"
                    >
                        <span>{{ selectedCountryName || 'Seleccione su país' }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <input type="hidden" name="country" v-model="form.country" required>
                    <div
                        v-show="showCountryDropdown"
                        class="auth__dropdown-list"
                    >
                        <div
                            v-for="country in countries"
                            :key="country.id"
                            class="auth__dropdown-item"
                            @click="selectCountry(country)"
                        >
                            {{ country.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="auth__form-item" :class="{ 'auth__form-item--error': errors.password }">
                <i class="auth__input-icon fa-solid fa-lock"></i>
                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth__input"
                    placeholder="Contraseña"
                    aria-label="Contraseña"
                    :class="{ 'auth__input--error': errors.password }"
                    @input="validatePassword"
                    required
                />
            </div>
            <span v-if="errors.password" class="auth__error">{{ errors.password }}</span>
            <p v-if="passwordError" class="auth__error">Su contraseña tiene que ser mínimo de 8 caracteres.</p>

            <!-- Password Confirmation -->
            <div class="auth__form-item">
                <i class="auth__input-icon fa-solid fa-unlock"></i>
                <i class="auth__input-toggle fa-solid"
                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                   @click="togglePassword"></i>
                <input
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    class="auth__input"
                    placeholder="Repetir Contraseña"
                    aria-label="Repetir Contraseña"
                    @input="validatePasswordConfirmation"
                    required
                />
            </div>
            <p v-if="passwordConfirmationError" class="auth__error">Las contraseñas no son iguales.</p>

            <!-- Terms and Privacy -->
            <div class="auth__checkbox">
                <input
                    type="checkbox"
                    name="politicas"
                    id="politicas"
                    v-model="form.politicas"
                    class="auth__checkbox-input"
                    required
                />
                <label for="politicas" class="auth__checkbox-label">
                    <div>
                        Acepto los
                        <a href="#" @click.prevent="$emit('open-info', 'terms')">
                            Términos y Condiciones
                        </a>
                        y autorizo mis datos e información de acuerdo a la
                        <a href="#" @click.prevent="$emit('open-info', 'privacy')">
                            Política de Privacidad
                        </a>
                        de PlayRKE
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="auth__form-item">
                <button
                    type="submit"
                    class="auth__button"
                    :disabled="processing || !isFormValid"
                >
                    Registrarse
                    <span v-if="processing" class="auth__loading"></span>
                </button>
            </div>
        </form>

        <template #pre-footer>
            <div class="auth__nav">
                <a :href="loginUrl" class="flex items-center justify-center space-x-2">
                    <span class="auth__nav-text">¿Ya tienes una cuenta?</span>
                    <span class="auth__nav-separator">|</span>
                    <span class="auth__nav-link">Inicia Sesión</span>
                </a>
            </div>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import { useForm } from '@inertiajs/vue3'

// Props
const props = defineProps<{
    registerUrl: string
    loginUrl: string
    countries: Array<{ id: number; name: string }>
}>()

// Reactive form state
const form = useForm({
    email: '',
    username: '',
    country: '',
    password: '',
    password_confirmation: '',
    politicas: false,
})

// State
const showPassword = ref(false)
const processing = ref(false)
const showCountryDropdown = ref(false)
const selectedCountryName = ref('')

// Validation states
const emailError = ref(false)
const usernameError = ref(false)
const passwordError = ref(false)
const passwordConfirmationError = ref(false)

// Errors object
const errors = ref<{
    email?: string
    username?: string
    password?: string
}>({})

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value
}

// Country dropdown handlers
const toggleCountryDropdown = () => {
    showCountryDropdown.value = !showCountryDropdown.value
}

const selectCountry = (country: { id: number; name: string }) => {
    form.country = country.id.toString()
    selectedCountryName.value = country.name
    showCountryDropdown.value = false
}

// Click outside handler for country dropdown
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement
    if (!target.closest('#dropdown_list') && !target.closest('#dropdown_select')) {
        showCountryDropdown.value = false
    }
}

// Validation functions
const validateEmail = () => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    emailError.value = form.email.length > 0 && !emailRegex.test(form.email)
}

const validateUsername = () => {
    usernameError.value = form.username.length > 0 && (form.username.length < 4 || form.username.length > 16)
}

const validatePassword = () => {
    passwordError.value = form.password.length > 0 && form.password.length < 8
}

const validatePasswordConfirmation = () => {
    passwordConfirmationError.value = form.password_confirmation.length > 0 && form.password !== form.password_confirmation
}

// Form validation
const isFormValid = computed(() => {
    return form.email &&
           form.username &&
           form.country &&
           form.password &&
           form.password_confirmation &&
           form.politicas &&
           !emailError.value &&
           !usernameError.value &&
           !passwordError.value &&
           !passwordConfirmationError.value &&
           form.password === form.password_confirmation
})

// Submit handler
const handleSubmit = () => {
    errors.value = {}

    // Client-side validation
    validateEmail()
    validateUsername()
    validatePassword()
    validatePasswordConfirmation()

    if (!isFormValid.value) {
        return
    }

    processing.value = true

    form.post(props.registerUrl, {
        onFinish: () => {
            processing.value = false
        },
        onError: (err) => {
            errors.value = err
        },
    })
}

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>