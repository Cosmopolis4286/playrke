import { reactive } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  type: ToastType
  message: string
  duration: number
}

interface ToastState {
  items: Toast[]
}

// Singleton compartido entre todos los composables
const state = reactive<ToastState>({ items: [] })
const timers = new Map<string, ReturnType<typeof setTimeout>>()

function add(type: ToastType, message: string, duration = 4000): void {
  const id = `t-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`
  state.items.push({ id, type, message, duration })

  if (duration > 0) {
    timers.set(id, setTimeout(() => remove(id), duration))
  }
}

function remove(id: string): void {
  const idx = state.items.findIndex(t => t.id === id)
  if (idx !== -1) state.items.splice(idx, 1)
  const t = timers.get(id)
  if (t) { clearTimeout(t); timers.delete(id) }
}

export function useToast() {
  return {
    toasts: state.items,
    success: (msg: string, duration?: number) => add('success', msg, duration),
    error:   (msg: string, duration?: number) => add('error',   msg, duration ?? 6000),
    warning: (msg: string, duration?: number) => add('warning', msg, duration),
    info:    (msg: string, duration?: number) => add('info',    msg, duration),
    remove,
  }
}
