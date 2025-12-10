import { NuxtApp } from '#app'

declare module '#app' {
  interface NuxtApp {
    $toast: {
      success(msg: string, title?: string, opts?: any): void
      error(msg: string, title?: string, opts?: any): void
      info(msg: string, title?: string, opts?: any): void
      warning(msg: string, title?: string, opts?: any): void
      custom(options: any): void
    }
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $toast: NuxtApp['$toast']
  }
}

export {}
