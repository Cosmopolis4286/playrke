<!-- Enhanced Auth Layout with advanced slots and interactivity -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-gray-900 to-indigo-900 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <!-- Gradient Overlay -->
      <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 via-purple-600/10 to-blue-800/20"></div>

      <!-- Animated Background Shapes -->
      <div class="absolute top-0 left-0 w-96 h-96 bg-gradient-to-r from-purple-500/30 to-pink-500/30 rounded-full blur-3xl animate-pulse"></div>
      <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>

      <!-- Floating Particles -->
      <div v-if="showParticles" class="absolute inset-0 pointer-events-none">
        <div v-for="i in particleCount" :key="`particle-${i}`"
             class="absolute w-2 h-2 bg-white/20 rounded-full animate-float"
             :style="getParticleStyle(i)">
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
      <!-- Header Slot -->
      <header v-if="$slots.header" class="absolute top-0 left-0 right-0 z-20 p-6">
        <slot name="header" />
      </header>

      <!-- Auth Container -->
      <div class="w-full max-w-md" :class="containerClasses" ref="containerRef">
        <!-- Glass Card Container -->
        <div class="backdrop-blur-xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl p-8 space-y-6">
          <!-- Top Section -->
          <div v-if="$slots.top" class="mb-6">
            <slot name="top" />
          </div>

          <!-- Logo Section -->
          <div class="text-center mb-8">
            <slot name="logo">
              <div class="relative inline-block">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur-lg opacity-30 animate-pulse"></div>
                <img :src="logo" alt="PlayRKE"
                     class="relative h-16 w-auto mx-auto drop-shadow-2xl transition-transform duration-300 hover:scale-105"
                     :class="{ 'cursor-pointer animate-bounce': hasLogoClickListener && animateLogo }"
                     @click="$emit('logo-click')" />
              </div>
            </slot>

            <!-- Progress Indicator -->
            <div v-if="showProgress" class="mt-4 w-full bg-white/10 rounded-full h-1 overflow-hidden">
              <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500 ease-out"
                   :style="{ width: `${progressValue}%` }"></div>
            </div>
          </div>

          <!-- Title & Subtitle Section -->
          <div v-if="title || subtitle || $slots.title" class="text-center mb-8">
            <slot name="title">
              <h1 v-if="title" class="text-3xl font-bold text-white mb-2 tracking-tight">{{ title }}</h1>
              <p v-if="subtitle" class="text-lg text-gray-300 font-light">{{ subtitle }}</p>
            </slot>
          </div>

          <!-- Alert Section -->
          <div v-if="$slots.alert" class="mb-6">
            <slot name="alert" />
          </div>

          <!-- Main Content -->
          <div class="space-y-6" :class="contentClasses">
            <slot />
          </div>

          <!-- Actions Section -->
          <div v-if="$slots.actions" class="mt-6">
            <slot name="actions" />
          </div>

          <!-- Pre-footer Section -->
          <div v-if="$slots['pre-footer']" class="mt-6 pt-6 border-t border-white/10">
            <slot name="pre-footer" />
          </div>
        </div>

        <!-- Footer -->
        <footer class="mt-8 pt-6 border-t border-white/10">
          <slot name="footer">
            <!-- Legal Links -->
            <div class="flex justify-center space-x-6 mb-4">
              <button type="button" @click="openInfoModal('terms')"
                      class="text-gray-400 hover:text-white transition-colors duration-200 text-sm flex items-center space-x-1">
                <i class="fas fa-file-contract"></i>
                <span>Términos</span>
              </button>
              <button type="button" @click="openInfoModal('about')"
                      class="text-gray-400 hover:text-white transition-colors duration-200 text-sm flex items-center space-x-1">
                <i class="fas fa-users"></i>
                <span>Quiénes Somos</span>
              </button>
              <button type="button" @click="openInfoModal('privacy')"
                      class="text-gray-400 hover:text-white transition-colors duration-200 text-sm flex items-center space-x-1">
                <i class="fas fa-shield-alt"></i>
                <span>Privacidad</span>
              </button>
            </div>

            <!-- Social and Copyright -->
            <div class="text-center space-y-4">
              <div class="flex justify-center space-x-4">
                <a :href="social.facebook" target="_blank" rel="noopener noreferrer"
                   class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-gray-300 hover:text-white transition-all duration-200 hover:scale-110">
                  <i class="fab fa-facebook"></i>
                </a>
                <a :href="social.instagram" target="_blank" rel="noopener noreferrer"
                   class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-gray-300 hover:text-white transition-all duration-200 hover:scale-110">
                  <i class="fab fa-instagram"></i>
                </a>
                <a :href="social.telegram" target="_blank" rel="noopener noreferrer"
                   class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-gray-300 hover:text-white transition-all duration-200 hover:scale-110">
                  <i class="fab fa-telegram"></i>
                </a>
              </div>
              <p class="text-gray-400 text-sm flex items-center justify-center space-x-1">
                <i class="fas fa-copyright"></i>
                <span>PlayRKE © {{ year }}</span>
              </p>
            </div>
          </slot>
        </footer>

        <!-- Bottom Section -->
        <div v-if="$slots.bottom" class="mt-6">
          <slot name="bottom" />
        </div>
      </div>

      <!-- Floating Elements -->
      <div v-if="$slots.floating" class="fixed bottom-6 right-6 z-50">
        <slot name="floating" />
      </div>
    </div>

    <!-- Modal for info content -->
    <teleport to="body">
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95">
        <div v-if="showInfoModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             @click="closeInfoModal"
             @keydown.escape="closeInfoModal">
          <div class="relative max-w-2xl w-full max-h-[90vh] bg-slate-800/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl overflow-hidden"
               @click.stop
               role="dialog"
               :aria-labelledby="modalId">
            <div class="flex items-center justify-between p-6 border-b border-white/10">
              <h3 :id="modalId" class="text-xl font-semibold text-white">{{ modalTitle }}</h3>
              <button type="button"
                      @click="closeInfoModal"
                      class="p-2 rounded-full hover:bg-white/10 text-gray-400 hover:text-white transition-colors duration-200"
                      aria-label="Cerrar">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="p-6 text-gray-300 leading-relaxed overflow-y-auto"
                 style="max-height: calc(90vh - 140px);"
                 v-html="modalContent"></div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

// Removed CSS import as we're now using Tailwind

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
/* Container size variants */
.max-w-sm { max-width: 360px; }
.max-w-md { max-width: 420px; }
.max-w-lg { max-width: 480px; }
.max-w-xl { max-width: 560px; }

/* Content spacing variants */
.space-y-4 > * + * { margin-top: 1rem; }
.space-y-6 > * + * { margin-top: 1.5rem; }
.space-y-8 > * + * { margin-top: 2rem; }

/* Floating particles animation */
.animate-float {
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(100vh) rotate(0deg);
    opacity: 0;
  }
  10%, 90% {
    opacity: 1;
  }
  50% {
    transform: translateY(-10vh) rotate(180deg);
  }
}

/* Entrance animations */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Glass morphism effect */
.backdrop-blur-xl {
  backdrop-filter: blur(24px);
}

/* Glow effects */
.glow-box {
  box-shadow:
    0 0 20px rgba(99, 102, 241, 0.3),
    0 0 40px rgba(99, 102, 241, 0.2),
    0 0 80px rgba(99, 102, 241, 0.1);
}

/* Hover effects */
.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

/* Custom animations for different particle delays */
.animate-float:nth-child(1) { animation-delay: 0s; }
.animate-float:nth-child(2) { animation-delay: 1s; }
.animate-float:nth-child(3) { animation-delay: 2s; }
.animate-float:nth-child(4) { animation-delay: 3s; }
.animate-float:nth-child(5) { animation-delay: 4s; }
.animate-float:nth-child(6) { animation-delay: 5s; }
.animate-float:nth-child(7) { animation-delay: 0.5s; }
.animate-float:nth-child(8) { animation-delay: 1.5s; }
.animate-float:nth-child(9) { animation-delay: 2.5s; }
.animate-float:nth-child(10) { animation-delay: 3.5s; }
.animate-float:nth-child(11) { animation-delay: 4.5s; }
.animate-float:nth-child(12) { animation-delay: 5.5s; }
.animate-float:nth-child(13) { animation-delay: 0.8s; }
.animate-float:nth-child(14) { animation-delay: 1.8s; }
.animate-float:nth-child(15) { animation-delay: 2.8s; }

/* Enhanced modal backdrop */
.modal-backdrop {
  backdrop-filter: blur(8px) saturate(1.2);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .backdrop-blur-xl {
    backdrop-filter: blur(16px);
  }
}

/* Reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
  .animate-float,
  .animate-pulse,
  .animate-bounce {
    animation: none;
  }

  .transition-all,
  .transition-colors,
  .transition-transform {
    transition: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .backdrop-blur-xl {
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: none;
  }
}
</style>