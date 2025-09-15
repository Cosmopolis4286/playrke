<template>
    <AuthLayout :subtitle="'Registro'" :register="false">
        <form @submit.prevent="handleSubmit" class="space-y-6" id="form-register">
            <!-- Email -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-envelope text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="E-mail"
                        aria-label="E-mail"
                        class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        :class="{ 'border-red-400 focus:ring-red-500': errors.email || emailError }"
                        @input="validateEmail"
                        required
                    />
                </div>
                <div v-if="errors.email || emailError" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ errors.email || 'Ingrese un E-mail válido.' }}</span>
                </div>
            </div>

            <!-- Username -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-user text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.username"
                        type="text"
                        placeholder="Nombre de Usuario"
                        aria-label="Nombre de Usuario"
                        class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        :class="{ 'border-red-400 focus:ring-red-500': errors.username || usernameError }"
                        @input="validateUsername"
                        maxlength="40"
                        required
                    />
                </div>
                <div v-if="errors.username || usernameError" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ errors.username || 'Su nombre de usuario debe tener de 4-16 caracteres' }}</span>
                </div>
            </div>

            <!-- Country Dropdown -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-earth-americas text-gray-400 text-lg"></i>
                    </div>
                    <button
                        type="button"
                        class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white text-left focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm flex items-center justify-between"
                        @click="toggleCountryDropdown"
                    >
                        <span :class="selectedCountryName ? 'text-white' : 'text-gray-400'">
                            {{ selectedCountryName || 'Seleccione su país' }}
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'transform rotate-180': showCountryDropdown }"></i>
                    </button>
                    <input type="hidden" name="country" v-model="form.country" required>

                    <!-- Dropdown List -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-2">
                        <div
                            v-show="showCountryDropdown"
                            class="absolute top-full left-0 right-0 mt-2 bg-slate-800/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl z-50 max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="country in countries"
                                :key="country.id"
                                class="px-4 py-3 text-white hover:bg-white/10 cursor-pointer transition-colors duration-150 border-b border-white/5 last:border-b-0 flex items-center space-x-3"
                                @click="selectCountry(country)"
                            >
                                <i class="fas fa-map-marker-alt text-gray-400 text-xs"></i>
                                <span>{{ country.name }}</span>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Password -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-lock text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full pl-12 pr-12 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        placeholder="Contraseña"
                        aria-label="Contraseña"
                        :class="{ 'border-red-400 focus:ring-red-500': errors.password || passwordError }"
                        @input="validatePassword"
                        required
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition-colors duration-200"
                        @click="togglePassword"
                    >
                        <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <div v-if="errors.password || passwordError" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ errors.password || 'Su contraseña tiene que ser mínimo de 8 caracteres.' }}</span>
                </div>
            </div>

            <!-- Password Confirmation -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-unlock text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        placeholder="Repetir Contraseña"
                        aria-label="Repetir Contraseña"
                        :class="{ 'border-red-400 focus:ring-red-500': passwordConfirmationError }"
                        @input="validatePasswordConfirmation"
                        required
                    />
                </div>
                <div v-if="passwordConfirmationError" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Las contraseñas no son iguales.</span>
                </div>
            </div>

            <!-- Terms and Privacy -->
            <div class="flex items-start space-x-3 p-4 bg-white/5 border border-white/10 rounded-xl backdrop-blur-sm">
                <div class="flex items-center pt-1">
                    <input
                        type="checkbox"
                        name="politicas"
                        id="politicas"
                        v-model="form.politicas"
                        class="w-4 h-4 bg-white/10 border border-white/20 rounded focus:ring-indigo-500 focus:ring-2 text-indigo-600 transition-colors duration-200"
                        required
                    />
                </div>
                <label for="politicas" class="text-sm text-gray-300 leading-relaxed cursor-pointer">
                    <span>Acepto los</span>
                    <a href="#" @click.prevent="$emit('open-info', 'terms')"
                       class="text-indigo-400 hover:text-indigo-300 transition-colors duration-200 underline decoration-indigo-400/50 hover:decoration-indigo-300">
                        Términos y Condiciones
                    </a>
                    <span>y autorizo mis datos e información de acuerdo a la</span>
                    <a href="#" @click.prevent="$emit('open-info', 'privacy')"
                       class="text-indigo-400 hover:text-indigo-300 transition-colors duration-200 underline decoration-indigo-400/50 hover:decoration-indigo-300">
                        Política de Privacidad
                    </a>
                    <span>de PlayRKE</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-[1.02] hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center space-x-2"
                    :class="{
                        'hover:from-indigo-700 hover:to-purple-700': !processing && isFormValid,
                        'opacity-50 cursor-not-allowed': processing || !isFormValid
                    }"
                    :disabled="processing || !isFormValid"
                >
                    <span v-if="!processing" class="flex items-center space-x-2">
                        <i class="fas fa-user-plus"></i>
                        <span>Registrarse</span>
                    </span>
                    <span v-else class="flex items-center space-x-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Registrando...</span>
                    </span>
                </button>
            </div>
        </form>

        <template #pre-footer>
            <div class="text-center">
                <a :href="loginUrl" class="inline-flex items-center justify-center space-x-2 text-gray-300 hover:text-white transition-colors duration-200 group">
                    <span class="text-sm">¿Ya tienes una cuenta?</span>
                    <span class="text-gray-500">|</span>
                    <span class="text-sm text-indigo-400 group-hover:text-indigo-300 flex items-center space-x-1">
                        <i class="fas fa-sign-in-alt text-xs"></i>
                        <span>Inicia Sesión</span>
                    </span>
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
    if (!target.closest('.auth__dropdown')) {
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