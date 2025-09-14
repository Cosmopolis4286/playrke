<!-- Enhanced Auth Layout with advanced slots and interactivity -->
<template>
  <div class="auth" :class="authClasses">
    <!-- Background Layer -->
    <slot name="background">
      <div class="auth__background-layer">
        <img class="auth__background" :src="bgImg" alt="Background" @load="onBackgroundLoad"
          @error="onBackgroundError" />
        <!-- Animated particles/shapes -->
        <div v-if="showParticles" class="auth__particles">
          <div v-for="i in particleCount" :key="`particle-${i}`" class="auth__particle" :style="getParticleStyle(i)">
          </div>
        </div>
      </div>
    </slot>

    <main class="auth__main">
      <!-- Header Slot -->
      <header v-if="$slots.header" class="auth__header">
        <slot name="header" />
      </header>

      <!-- Container -->
      <div class="auth__container" :class="containerClasses" ref="containerRef">
        <!-- Top Section -->
        <div v-if="$slots.top" class="auth__top">
          <slot name="top" />
        </div>

        <!-- Logo Section -->
        <div class="auth__logo-section">
          <slot name="logo">
            <div class="auth__logo" :class="{ 'auth__logo--animate': animateLogo }">
              <img :src="logo" alt="PlayRKE" @click="$emit('logo-click')"
                :class="{ 'cursor-pointer': hasLogoClickListener }" />
            </div>
          </slot>

          <!-- Progress Indicator -->
          <div v-if="showProgress" class="auth__progress">
            <div class="auth__progress-bar" :style="{ width: `${progressValue}%` }"></div>
          </div>
        </div>

        <!-- Title & Subtitle Section -->
        <div v-if="title || subtitle || $slots.title" class="auth__title-section">
          <slot name="title">
            <h1 v-if="title" class="auth__title">{{ title }}</h1>
            <p v-if="subtitle" class="auth__subtitle">{{ subtitle }}</p>
          </slot>
        </div>

        <!-- Alert Section -->
        <div v-if="$slots.alert" class="auth__alert-section">
          <slot name="alert" />
        </div>

        <!-- Main Content -->
        <div class="auth__content" :class="contentClasses">
          <slot />
        </div>

        <!-- Actions Section -->
        <div v-if="$slots.actions" class="auth__actions">
          <slot name="actions" />
        </div>

        <!-- Pre-footer Section -->
        <div v-if="$slots['pre-footer']" class="auth__pre-footer">
          <slot name="pre-footer" />
        </div>

        <!-- Footer -->
        <footer class="auth__footer">
          <slot name="footer">
            <!-- Legal Links -->
            <div class="auth__footer-links">
              <button type="button" @click="openInfoModal('terms')" class="auth__footer-link">
                <i class="fas fa-file-contract mr-1"></i>
                Términos
              </button>
              <button type="button" @click="openInfoModal('about')" class="auth__footer-link">
                <i class="fas fa-users mr-1"></i>
                Quiénes Somos
              </button>
              <button type="button" @click="openInfoModal('privacy')" class="auth__footer-link">
                <i class="fas fa-shield-alt mr-1"></i>
                Privacidad
              </button>
            </div>

            <!-- Social and Copyright -->
            <div class="auth__social">
              <span class="auth__copyright">
                <i class="fas fa-copyright mr-1"></i>
                PlayRKE © {{ year }}
              </span>
              <div class="auth__social-links">
                <a class="auth__social-link" :href="social.facebook" target="_blank" rel="noopener noreferrer"
                  :aria-label="'Seguir en Facebook'">
                  <i class="fab fa-facebook"></i>
                </a>
                <a class="auth__social-link" :href="social.instagram" target="_blank" rel="noopener noreferrer"
                  :aria-label="'Seguir en Instagram'">
                  <i class="fab fa-instagram"></i>
                </a>
                <a class="auth__social-link" :href="social.telegram" target="_blank" rel="noopener noreferrer"
                  :aria-label="'Unirse al canal de Telegram'">
                  <i class="fab fa-telegram"></i>
                </a>
              </div>
            </div>
          </slot>
        </footer>

        <!-- Bottom Section -->
        <div v-if="$slots.bottom" class="auth__bottom">
          <slot name="bottom" />
        </div>
      </div>

      <!-- Floating Elements -->
      <div v-if="$slots.floating" class="auth__floating">
        <slot name="floating" />
      </div>
    </main>

    <!-- Modal for info content -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="showInfoModal" class="auth__modal-overlay" @click="closeInfoModal" @keydown.escape="closeInfoModal">
          <div class="auth__modal" @click.stop role="dialog" :aria-labelledby="modalId">
            <div class="auth__modal-header">
              <h3 :id="modalId" class="auth__modal-title">{{ modalTitle }}</h3>
              <button type="button" @click="closeInfoModal" class="auth__modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="auth__modal-content" v-html="modalContent"></div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

// Import CSS
import '../../../assets/css/auth.css'

// Types
interface SocialProps {
  facebook: string
  instagram: string
  telegram: string
}

interface Props {
  logo?: string
  bgImg?: string
  title?: string
  subtitle?: string
  social?: Partial<SocialProps>
  showParticles?: boolean
  animateLogo?: boolean
  showProgress?: boolean
  progressValue?: number
  containerSize?: 'sm' | 'md' | 'lg' | 'xl'
  contentSpacing?: 'tight' | 'normal' | 'relaxed'
}

// Props
const props = withDefaults(defineProps<Props>(), {
  logo: `${window.location.origin}/public/img/logo.png`,
  bgImg: `${window.location.origin}/public/img/arc.jpg`,
  title: '',
  subtitle: '',
  social: () => ({
    facebook: 'https://www.facebook.com/my.play.rke',
    instagram: 'https://www.instagram.com/my.play.rke',
    telegram: 'https://t.me/+rV3KWBuuKOtjYzkx'
  }),
  showParticles: true,
  animateLogo: true,
  showProgress: false,
  progressValue: 0,
  containerSize: 'md',
  contentSpacing: 'normal',
})

// Emits
const emit = defineEmits<{
  'logo-click': []
  'background-loaded': []
  'background-error': []
  'modal-open': [type: string]
  'modal-close': [type: string]
}>()

// State
const containerRef = ref<HTMLElement>()
const showInfoModal = ref(false)
const currentModalType = ref('')
const particleCount = 15
const modalId = computed(() => `modal-${Math.random().toString(36).substr(2, 9)}`)

// Computed
const year = computed(() => new Date().getFullYear())

const social: SocialProps = {
  facebook: props.social?.facebook ?? 'https://www.facebook.com/my.play.rke',
  instagram: props.social?.instagram ?? 'https://www.instagram.com/my.play.rke',
  telegram: props.social?.telegram ?? 'https://t.me/+rV3KWBuuKOtjYzkx'
}

const authClasses = computed(() => {
  const classes = []
  if (props.showParticles) classes.push('auth--with-particles')
  return classes
})

const containerClasses = computed(() => {
  const classes = []
  classes.push(`auth__container--${props.containerSize}`)
  return classes
})

const contentClasses = computed(() => {
  const classes = []
  classes.push(`auth__content--${props.contentSpacing}`)
  return classes
})

const hasLogoClickListener = computed(() => {
  return !!emit
})

const modalTitle = computed(() => {
  const titles = {
    terms: 'Términos y Condiciones',
    about: 'Quiénes Somos',
    privacy: 'Política de Privacidad'
  }
  return titles[currentModalType.value as keyof typeof titles] || ''
})

const modalContent = computed(() => {
  const contents = {
    terms: `
      <div class="space-y-4">
        <p>Bienvenido a PlayRKE. Al utilizar nuestros servicios, aceptas estos términos.</p>
        <h4 class="font-semibold">1. Uso del Servicio</h4>
        <p>Nuestros servicios están destinados a usuarios mayores de 18 años.</p>
        <h4 class="font-semibold">2. Responsabilidades</h4>
        <p>Los usuarios son responsables de mantener la confidencialidad de sus cuentas.</p>
      </div>
    `,
    about: `
      <div class="space-y-4">
        <p>PlayRKE es una plataforma innovadora que conecta el gaming con oportunidades reales.</p>
        <h4 class="font-semibold">Nuestra Misión</h4>
        <p>Transformar el tiempo de juego en oportunidades tangibles para nuestros usuarios.</p>
        <h4 class="font-semibold">Nuestro Equipo</h4>
        <p>Contamos con un equipo apasionado por la tecnología y los juegos.</p>
      </div>
    `,
    privacy: `
      <div class="space-y-4">
        <p>Tu privacidad es importante para nosotros. Esta política explica cómo recopilamos y usamos tus datos.</p>
        <h4 class="font-semibold">Información que Recopilamos</h4>
        <p>Recopilamos información que nos proporcionas directamente y datos de uso del servicio.</p>
        <h4 class="font-semibold">Cómo Usamos tu Información</h4>
        <p>Utilizamos tu información para proporcionar y mejorar nuestros servicios.</p>
      </div>
    `
  }
  return contents[currentModalType.value as keyof typeof contents] || ''
})

// Methods
const getParticleStyle = (index: number) => {
  const size = Math.random() * 4 + 2
  const left = Math.random() * 100
  const animationDuration = (Math.random() * 3 + 2) + 's'
  const animationDelay = (Math.random() * 2) + 's'

  return {
    width: `${size}px`,
    height: `${size}px`,
    left: `${left}%`,
    animationDuration,
    animationDelay,
  }
}

const openInfoModal = (type: string) => {
  currentModalType.value = type
  showInfoModal.value = true
  emit('modal-open', type)

  // Prevent body scroll
  document.body.style.overflow = 'hidden'

  // Focus trap
  nextTick(() => {
    const modal = document.querySelector('.auth__modal')
    const focusableElements = modal?.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    const firstElement = focusableElements?.[0] as HTMLElement
    firstElement?.focus()
  })
}

const closeInfoModal = () => {
  const type = currentModalType.value
  showInfoModal.value = false
  currentModalType.value = ''
  emit('modal-close', type)

  // Restore body scroll
  document.body.style.overflow = ''
}

const onBackgroundLoad = () => {
  emit('background-loaded')
}

const onBackgroundError = () => {
  emit('background-error')
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && showInfoModal.value) {
    closeInfoModal()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)

  // Add entrance animation to container
  if (containerRef.value) {
    containerRef.value.classList.add('auth__container--entering')
    setTimeout(() => {
      containerRef.value?.classList.remove('auth__container--entering')
      containerRef.value?.classList.add('auth__container--entered')
    }, 100)
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  // Restore body scroll if modal was open
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Enhanced layout styles */

/* Container size variants */
.auth__container--sm {
  max-width: 360px;
}

.auth__container--md {
  max-width: 420px;
}

.auth__container--lg {
  max-width: 480px;
}

.auth__container--xl {
  max-width: 560px;
}

/* Content spacing variants */
.auth__content--tight {
  @apply space-y-4;
}

.auth__content--normal {
  @apply space-y-6;
}

.auth__content--relaxed {
  @apply space-y-8;
}

/* Enhanced sections */
.auth__title-section {
  @apply text-center mb-8;
}

.auth__title {
  @apply text-2xl font-bold mb-2;
  color: var(--color-text-primary);
}

.auth__alert-section {
  @apply mb-6;
}

.auth__actions {
  @apply mt-6;
}

.auth__pre-footer {
  @apply mt-6 mb-4;
}

.auth__bottom {
  @apply mt-4;
}

/* Logo animations */
.auth__logo--animate img {
  animation: logoFloat 3s ease-in-out infinite;
}

@keyframes logoFloat {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-5px);
  }
}

/* Progress bar */
.auth__progress {
  @apply w-full h-1 bg-gray-600 rounded-full mt-4 overflow-hidden;
}

.auth__progress-bar {
  @apply h-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-500 ease-out;
}

/* Particles */
.auth__particles {
  @apply absolute inset-0 pointer-events-none overflow-hidden;
}

.auth__particle {
  @apply absolute bg-blue-400/20 rounded-full;
  animation: float infinite linear;
}

@keyframes float {
  from {
    transform: translateY(100vh) rotate(0deg);
  }

  to {
    transform: translateY(-10vh) rotate(360deg);
  }
}

/* Container entrance animation */
.auth__container--entering {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.auth__container--entered {
  opacity: 1;
  transform: translateY(0) scale(1);
  transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Enhanced footer */
.auth__social-links {
  @apply flex space-x-3;
}

.auth__social-link {
  @apply w-8 h-8 flex items-center justify-center text-sm rounded-full transition-all;
  color: var(--color-text-muted);
  background: rgba(255, 255, 255, 0.05);
}

.auth__social-link:hover {
  color: var(--color-text-primary);
  background: var(--color-primary);
  transform: translateY(-2px) scale(1.1);
}

/* Modal styles */
.auth__modal-overlay {
  @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4;
  backdrop-filter: blur(8px);
}

.auth__modal {
  @apply bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden;
  max-height: 90vh;
  border: 1px solid var(--color-border-secondary);
}

.auth__modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-600;
}

.auth__modal-title {
  @apply text-lg font-semibold text-white;
}

.auth__modal-close {
  @apply p-2 rounded-full hover:bg-gray-700 transition-colors;
  color: var(--color-text-muted);
}

.auth__modal-close:hover {
  color: var(--color-text-primary);
}

.auth__modal-content {
  @apply p-6 text-gray-300 text-sm leading-relaxed overflow-y-auto;
  max-height: 24rem;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease-out;
}

.modal-enter-from {
  opacity: 0;
  transform: scale(0.8);
}

.modal-leave-to {
  opacity: 0;
  transform: scale(1.1);
}

/* Floating section */
.auth__floating {
  @apply fixed top-4 right-4 z-40;
}

/* Header section */
.auth__header {
  @apply w-full max-w-4xl mx-auto mb-8;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .auth__container {
    @apply mx-4;
    max-width: calc(100vw - 2rem);
  }

  .auth__modal {
    @apply mx-4;
    max-width: calc(100vw - 2rem);
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {

  .auth__footer-link,
  .auth__social-link {
    @apply border border-current;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {

  .auth__logo--animate img,
  .auth__particle,
  .auth__container--entering,
  .auth__container--entered {
    animation: none;
    transition: none;
  }
}
</style>