import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import { createPinia } from 'pinia'

// CSS Imports
import './assets/styles/design-system.css'
import './assets/animations/transitions.css'

// Global Components
import Button from './components/ui/Button.vue'
import Card from './components/ui/Card.vue'
import Input from './components/ui/Input.vue'
import Modal from './components/ui/Modal.vue'

// Layouts
import AppLayout from './layouts/AppLayout.vue'

createInertiaApp({
  title: (title) => `${title} - PlayRKE`,
  
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    
    // First try to resolve from frontend/pages
    let page = pages[`./pages/${name}.vue`]
    
    // If not found, try Laravel's resources/js/Pages (fallback for existing pages)
    if (!page) {
      return resolvePageComponent(`../js/Pages/${name}.vue`, import.meta.glob('../js/Pages/**/*.vue'))
    }
    
    // Set default layout
    if (!page.default.layout) {
      page.default.layout = AppLayout
    }
    
    return page
  },
  
  setup({ el, App, props, plugin }) {
    const pinia = createPinia()
    
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(pinia)
    
    // Register global components
    app.component('Button', Button)
    app.component('Card', Card)
    app.component('Input', Input)
    app.component('Modal', Modal)
    app.component('AppLayout', AppLayout)
    
    // Global properties
    app.config.globalProperties.$formatNumber = (num) => {
      return new Intl.NumberFormat('es-CO').format(num)
    }
    
    app.config.globalProperties.$formatCurrency = (num) => {
      return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
      }).format(num)
    }
    
    // Error handling
    app.config.errorHandler = (err, instance, info) => {
      console.error('Vue Error:', err, info)
    }
    
    app.mount(el)
  },
  
  progress: {
    color: '#3B82F6',
    showSpinner: true,
  },
})

// Service Worker registration (optional)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        console.log('SW registered: ', registration)
      })
      .catch((registrationError) => {
        console.log('SW registration failed: ', registrationError)
      })
  })
}