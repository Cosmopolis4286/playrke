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
        <form @submit.prevent="handleSubmit" class="auth__form" id="form-login">
            <!-- Email/Username Input -->
            <AuthInput
                v-model="form.login"
                type="text"
                field-type="username"
                placeholder="Email/Username"
                aria-label="Email o Nombre de Usuario"
                :error="errors.login"
                :validation="validateEmailOrUsername"
                :maxlength="40"
                :autofocus="true"
                help-text="Ingresa tu email o nombre de usuario"
                variant="default"
                size="md"
                @enter="handleSubmit"
            />

            <!-- Password Input -->
            <AuthInput
                v-model="form.password"
                type="password"
                field-type="password"
                placeholder="Contraseña"
                aria-label="Contraseña"
                :show-password-toggle="true"
                :error="errors.password"
                help-text="Tu contraseña debe tener al menos 8 caracteres"
                variant="default"
                size="md"
                @enter="handleSubmit"
            />

            <!-- Submit Button -->
            <div class="auth__form-item">
                <AuthButton
                    type="submit"
                    text="Iniciar Sesión"
                    loading-text="Iniciando sesión..."
                    :loading="processing"
                    :disabled="!isFormValid"
                    variant="primary"
                    size="lg"
                    :full-width="true"
                    left-icon="login"
                    :animate="true"
                    @click="handleSubmit"
                />
            </div>

            <!-- Forgot Password Link -->
            <div class="auth__nav">
                <Link :href="passwordRequestUrl" class="auth__nav-link">
                    <i class="ti ti-key mr-2"></i>
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <!-- Register Link -->
            <div class="auth__nav">
                <div class="flex items-center justify-center space-x-2 text-sm">
                    <span class="auth__nav-text">¿No tienes una cuenta?</span>
                    <Link :href="registerUrl" class="auth__nav-link auth__nav-link--primary">
                        <i class="ti ti-user-plus mr-2"></i>
                        Crear cuenta
                    </Link>
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