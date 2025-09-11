import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Composable para animaciones y efectos premium
 */

// Intersection Observer para scroll animations
export function useScrollReveal(threshold = 0.1) {
  const elements = ref<HTMLElement[]>([])
  const observer = ref<IntersectionObserver | null>(null)

  const addElement = (el: HTMLElement) => {
    if (!elements.value.includes(el)) {
      elements.value.push(el)
      observer.value?.observe(el)
    }
  }

  const removeElement = (el: HTMLElement) => {
    const index = elements.value.indexOf(el)
    if (index > -1) {
      elements.value.splice(index, 1)
      observer.value?.unobserve(el)
    }
  }

  onMounted(() => {
    observer.value = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed')
          }
        })
      },
      { threshold }
    )
  })

  onUnmounted(() => {
    observer.value?.disconnect()
  })

  return {
    addElement,
    removeElement
  }
}

// Parallax effect
export function useParallax(speed = 0.5) {
  const element = ref<HTMLElement>()
  
  const updateParallax = () => {
    if (!element.value) return
    
    const rect = element.value.getBoundingClientRect()
    const scrolled = window.pageYOffset
    const parallax = scrolled * speed
    
    element.value.style.transform = `translateY(${parallax}px)`
  }

  onMounted(() => {
    window.addEventListener('scroll', updateParallax)
    updateParallax()
  })

  onUnmounted(() => {
    window.removeEventListener('scroll', updateParallax)
  })

  return {
    element
  }
}

// Mouse tracking effect
export function useMouseTracker() {
  const x = ref(0)
  const y = ref(0)
  const element = ref<HTMLElement>()

  const updateMouse = (event: MouseEvent) => {
    if (!element.value) return
    
    const rect = element.value.getBoundingClientRect()
    x.value = ((event.clientX - rect.left) / rect.width) * 100
    y.value = ((event.clientY - rect.top) / rect.height) * 100
  }

  onMounted(() => {
    if (element.value) {
      element.value.addEventListener('mousemove', updateMouse)
    }
  })

  onUnmounted(() => {
    if (element.value) {
      element.value.removeEventListener('mousemove', updateMouse)
    }
  })

  return {
    x,
    y,
    element
  }
}

// Typing animation
export function useTypewriter(text: string, speed = 100) {
  const displayText = ref('')
  const isComplete = ref(false)
  const currentIndex = ref(0)

  const startTyping = () => {
    if (currentIndex.value < text.length) {
      displayText.value += text[currentIndex.value]
      currentIndex.value++
      setTimeout(startTyping, speed)
    } else {
      isComplete.value = true
    }
  }

  const resetTyping = () => {
    displayText.value = ''
    currentIndex.value = 0
    isComplete.value = false
  }

  return {
    displayText,
    isComplete,
    startTyping,
    resetTyping
  }
}

// Counter animation
export function useCountUp(target: number, duration = 2000) {
  const count = ref(0)
  const isAnimating = ref(false)

  const startCount = () => {
    if (isAnimating.value) return
    
    isAnimating.value = true
    const startTime = Date.now()
    
    const animate = () => {
      const elapsed = Date.now() - startTime
      const progress = Math.min(elapsed / duration, 1)
      
      // Easing function
      const eased = 1 - Math.pow(1 - progress, 3)
      count.value = Math.round(target * eased)
      
      if (progress < 1) {
        requestAnimationFrame(animate)
      } else {
        isAnimating.value = false
      }
    }
    
    requestAnimationFrame(animate)
  }

  return {
    count,
    isAnimating,
    startCount
  }
}

// Ripple effect for buttons
export function useRipple() {
  const createRipple = (event: MouseEvent, element: HTMLElement) => {
    const ripple = document.createElement('span')
    const rect = element.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)
    const x = event.clientX - rect.left - size / 2
    const y = event.clientY - rect.top - size / 2
    
    ripple.style.cssText = `
      position: absolute;
      width: ${size}px;
      height: ${size}px;
      left: ${x}px;
      top: ${y}px;
      background: rgba(255, 255, 255, 0.6);
      border-radius: 50%;
      transform: scale(0);
      pointer-events: none;
      animation: ripple-animation 0.6s linear;
    `
    
    element.appendChild(ripple)
    
    ripple.addEventListener('animationend', () => {
      ripple.remove()
    })
  }

  return {
    createRipple
  }
}

// Floating animation
export function useFloating(amplitude = 10, duration = 3000) {
  const element = ref<HTMLElement>()
  let animationId: number
  
  const animate = (timestamp: number) => {
    if (!element.value) return
    
    const y = Math.sin(timestamp / duration) * amplitude
    element.value.style.transform = `translateY(${y}px)`
    
    animationId = requestAnimationFrame(animate)
  }

  onMounted(() => {
    animationId = requestAnimationFrame(animate)
  })

  onUnmounted(() => {
    cancelAnimationFrame(animationId)
  })

  return {
    element
  }
}

// Loading states
export function useLoadingStates() {
  const isLoading = ref(false)
  const loadingText = ref('')
  const progress = ref(0)

  const setLoading = (loading: boolean, text = '') => {
    isLoading.value = loading
    loadingText.value = text
    if (!loading) {
      progress.value = 0
    }
  }

  const setProgress = (value: number) => {
    progress.value = Math.min(100, Math.max(0, value))
  }

  return {
    isLoading,
    loadingText,
    progress,
    setLoading,
    setProgress
  }
}

// Stagger animations for lists
export function useStagger(delay = 100) {
  const elements = ref<HTMLElement[]>([])
  
  const addElement = (el: HTMLElement) => {
    if (!elements.value.includes(el)) {
      elements.value.push(el)
      el.style.animationDelay = `${elements.value.length * delay}ms`
    }
  }

  const triggerStagger = (animationClass = 'animate-slide-up') => {
    elements.value.forEach((el, index) => {
      setTimeout(() => {
        el.classList.add(animationClass)
      }, index * delay)
    })
  }

  return {
    elements,
    addElement,
    triggerStagger
  }
}

// CSS Animation utilities
export const animationClasses = {
  // Entrances
  fadeIn: 'animate-fade-in',
  slideUp: 'animate-slide-up',
  slideDown: 'animate-slide-down',
  slideLeft: 'animate-slide-left',
  slideRight: 'animate-slide-right',
  scaleIn: 'animate-scale-in',
  zoomIn: 'animate-zoom-in',
  flipIn: 'animate-flip-in',
  bounceIn: 'animate-bounce-in',
  
  // Continuous
  float: 'animate-float',
  pulse: 'animate-pulse',
  bounce: 'animate-bounce',
  spin: 'animate-spin',
  ping: 'animate-ping',
  
  // Hover effects
  hoverLift: 'hover-lift',
  hoverGlow: 'hover-glow',
  hoverScale: 'hover-scale',
  hoverRotate: 'hover-rotate'
}

// CSS injection for custom animations
const customAnimationsCSS = `
  @keyframes slide-up {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  @keyframes slide-down {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  @keyframes slide-left {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
  }
  
  @keyframes slide-right {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
  }
  
  @keyframes bounce-in {
    0% { opacity: 0; transform: scale3d(0.3, 0.3, 0.3); }
    20% { transform: scale3d(1.1, 1.1, 1.1); }
    40% { transform: scale3d(0.9, 0.9, 0.9); }
    60% { opacity: 1; transform: scale3d(1.03, 1.03, 1.03); }
    80% { transform: scale3d(0.97, 0.97, 0.97); }
    100% { opacity: 1; transform: scale3d(1, 1, 1); }
  }
  
  @keyframes ripple-animation {
    to { transform: scale(4); opacity: 0; }
  }
  
  .animate-slide-up { animation: slide-up 0.6s ease-out; }
  .animate-slide-down { animation: slide-down 0.6s ease-out; }
  .animate-slide-left { animation: slide-left 0.6s ease-out; }
  .animate-slide-right { animation: slide-right 0.6s ease-out; }
  .animate-bounce-in { animation: bounce-in 0.8s ease-out; }
`

// Inject custom animations CSS
if (typeof document !== 'undefined') {
  const style = document.createElement('style')
  style.textContent = customAnimationsCSS
  document.head.appendChild(style)
}