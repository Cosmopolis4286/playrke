/// <reference types="nuxt" />
/// <reference types="vue" />

import type { ToastInstance } from './toast'

declare module '#app' {
  interface NuxtApp {
    $toast: ToastInstance
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $toast: ToastInstance
  }
}

export {}
