<template>
    <AuthLayout
        :subtitle="'Tu Sitio Para Ganar'"
        :show-particles="true"
        :animate-logo="true"
        container-size="md"
        content-spacing="normal"
        @background-loaded="onBackgroundLoaded"
        @modal-open="onModalOpen"
    >
        <!-- Alert section for status messages -->
        <template #alert>
            <div v-if="statusMessage" class="auth__alert auth__alert--info">
                <i class="fas fa-info-circle mr-2"></i>
                {{ statusMessage }}
            </div>
        </template>

        <!-- Main login form -->
        <form @submit.prevent="handleSubmit" class="space-y-6" id="form-login">
            <!-- Email/Username Input -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-user text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.login"
                        type="text"
                        placeholder="Email/Username"
                        aria-label="Email o Nombre de Usuario"
                        class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        :class="{ 'border-red-400 focus:ring-red-500': errors.login }"
                        maxlength="40"
                        required
                        autofocus
                    />
                </div>
                <div v-if="errors.login" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ errors.login }}</span>
                </div>
            </div>

            <!-- Password Input -->
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                        <i class="fas fa-lock text-gray-400 text-lg"></i>
                    </div>
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Contraseña"
                        aria-label="Contraseña"
                        class="w-full pl-12 pr-12 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm"
                        :class="{ 'border-red-400 focus:ring-red-500': errors.password }"
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
                <div v-if="errors.password" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ errors.password }}</span>
                </div>
            </div>

            <!-- Submit Button -->
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
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Iniciar Sesión</span>
                    </span>
                    <span v-else class="flex items-center space-x-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Iniciando sesión...</span>
                    </span>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-4 pt-4">
                <!-- Forgot Password Link -->
                <div class="text-center">
                    <Link :href="passwordRequestUrl" class="inline-flex items-center space-x-2 text-gray-400 hover:text-white transition-colors duration-200 text-sm group">
                        <i class="fas fa-key text-xs group-hover:scale-110 transition-transform duration-200"></i>
                        <span>¿Olvidaste tu contraseña?</span>
                    </Link>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 text-sm">
                        <span class="text-gray-400">¿No tienes una cuenta?</span>
                        <span class="text-gray-500">|</span>
                        <Link :href="registerUrl" class="text-indigo-400 hover:text-indigo-300 transition-colors duration-200 flex items-center space-x-1 group">
                            <i class="fas fa-user-plus text-xs group-hover:scale-110 transition-transform duration-200"></i>
                            <span>Crear cuenta</span>
                        </Link>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pre-footer navigation -->
        <template #pre-footer>
            <!-- Espacio reservado para otros links si se necesitan -->
        </template>

        <!-- Floating help button -->
        <template #floating>
            <button
                type="button"
                @click="showHelpModal = true"
                class="auth__help-button"
                aria-label="Ayuda"
                title="¿Necesitas ayuda?"
            >
                <i class="ti ti-help-circle"></i>
            </button>
        </template>
    </AuthLayout>

    <!-- Toast Notifications -->
    <AuthToast ref="toastRef" position="bottom-right" />

    <!-- Help Modal -->
    <teleport to="body">
        <transition name="modal">
            <div
                v-if="showHelpModal"
                class="auth__modal-overlay"
                @click="showHelpModal = false"
            >
                <div class="auth__modal auth__modal--help" @click.stop>
                    <div class="auth__modal-header">
                        <h3 class="auth__modal-title">
                            <i class="ti ti-help-circle mr-2"></i>
                            ¿Necesitas ayuda?
                        </h3>
                        <button
                            type="button"
                            @click="showHelpModal = false"
                            class="auth__modal-close"
                        >
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="auth__modal-content">
                        <div class="space-y-4">
                            <div class="help-item">
                                <h4 class="font-semibold text-blue-400">
                                    <i class="ti ti-user mr-2"></i>
                                    ¿No recuerdas tu usuario?
                                </h4>
                                <p class="text-sm text-gray-300">
                                    Puedes iniciar sesión usando tu dirección de email en lugar del nombre de usuario.
                                </p>
                            </div>
                            <div class="help-item">
                                <h4 class="font-semibold text-blue-400">
                                    <i class="ti ti-key mr-2"></i>
                                    ¿Olvidaste tu contraseña?
                                </h4>
                                <p class="text-sm text-gray-300">
                                    Haz clic en "¿Olvidaste tu contraseña?" para recibir instrucciones por email.
                                </p>
                            </div>
                            <div class="help-item">
                                <h4 class="font-semibold text-blue-400">
                                    <i class="ti ti-shield mr-2"></i>
                                    ¿Problemas de seguridad?
                                </h4>
                                <p class="text-sm text-gray-300">
                                    Contáctanos en support@playrke.com si sospechas que tu cuenta fue comprometida.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '../../components/layout/auth/AuthLayout.vue'
import AuthInput from '../../components/auth/AuthInput.vue'
import AuthButton from '../../components/auth/AuthButton.vue'
import AuthToast from '../../components/auth/AuthToast.vue'
import { useIcons } from '../../composables/useIcons'

const { getIcon } = useIcons()

// Props
const props = defineProps<{
    register: boolean
    loginUrl: string
    passwordRequestUrl: string
    registerUrl: string
    statusMessage?: string
}>()

// Reactive form state
const form = useForm({
    login: '',
    password: '',
})

// State
const processing = ref(false)
const showHelpModal = ref(false)
const showPassword = ref(false)
const toastRef = ref<InstanceType<typeof AuthToast>>()

// Computed
const errors = computed(() => form.errors)

const isFormValid = computed(() => {
    return form.login.trim() !== '' && form.password.trim() !== ''
})

// Validation functions
const validateEmailOrUsername = (value: string): boolean | string => {
    if (!value || value.trim() === '') return 'Este campo es requerido'
    if (value.length < 3) return 'Debe tener al menos 3 caracteres'
    if (value.length > 40) return 'Máximo 40 caracteres permitidos'
    return true
}

// Toggle password visibility
const togglePassword = () => {
    showPassword.value = !showPassword.value
}

// Methods
const handleSubmit = async () => {
    if (!isFormValid.value) {
        toastRef.value?.showValidationError('Toda la información es requerida')
        return
    }

    processing.value = true

    form.post(props.loginUrl, {
        onFinish: () => {
            processing.value = false
        },
        onSuccess: () => {
            toastRef.value?.showSuccess('¡Inicio de sesión exitoso! Redirigiendo...', {
                duration: 3000,
                persistent: true
            })
        },
        onError: (errors) => {
            if (errors.login) {
                toastRef.value?.showError(errors.login)
            } else {
                toastRef.value?.showApiError('Error al iniciar sesión. Verifica tus credenciales.', () => {
                    handleSubmit()
                })
            }
        },
    })
}

const onBackgroundLoaded = () => {
    // Background loaded successfully
}

const onModalOpen = (type: string) => {
    console.log(`Modal opened: ${type}`)
}

// Auto-focus management
onMounted(() => {
    // Add subtle entrance animation delay
    setTimeout(() => {
        const firstInput = document.querySelector('input[type="text"]') as HTMLInputElement
        firstInput?.focus()
    }, 600)
})
</script>

<style scoped>
/* Help button */
.auth__help-button {
    @apply w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center;
    @apply hover:bg-blue-700 transition-all duration-300 hover:scale-110;
    @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.auth__help-button:hover {
    box-shadow: 0 8px 25px -5px rgba(59, 130, 246, 0.5);
}

/* Help modal specific styles */
.auth__modal--help {
    @apply max-w-lg;
}

.auth__modal--help .help-item {
    @apply p-4 rounded-lg bg-gray-700/50 border border-gray-600;
}

.auth__modal--help .help-item h4 {
    @apply mb-2;
}

/* Enhanced nav link with icon */
.auth__nav-link {
    @apply inline-flex items-center transition-all duration-200;
}

.auth__nav-link:hover {
    @apply transform translate-x-1;
}

/* Form animations */
.auth__form-item {
    animation: slideInUp 0.6s ease-out forwards;
    opacity: 0;
}

.auth__form-item:nth-child(1) {
    animation-delay: 0.1s;
}

.auth__form-item:nth-child(2) {
    animation-delay: 0.2s;
}

.auth__form-item:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .auth__help-button {
        @apply w-10 h-10 text-sm;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .auth__form-item,
    .auth__nav-link,
    .auth__help-button {
        animation: none;
        transition: none;
    }
}
</style>