// layers/shared/composables/useToast.ts
import { useToast } from '#ui'

type ToastType = 'success' | 'error' | 'info' | 'warning'

export function useAppToast() {
  const toast = useToast()

  const notify = (
    type: ToastType,
    message: string,
    description?: string
  ) => {
    toast.add({
      title: message,
      description,
      color:
        type === 'success' ? 'green' :
        type === 'error'   ? 'red' :
        type === 'warning' ? 'yellow' :
        'blue'
    })
  }

  const success = (msg: string, desc?: string) =>
    notify('success', msg, desc)

  const error = (msg: string, desc?: string) =>
    notify('error', msg, desc)

  const info = (msg: string, desc?: string) =>
    notify('info', msg, desc)

  const warning = (msg: string, desc?: string) =>
    notify('warning', msg, desc)

  return {
    notify,
    success,
    error,
    info,
    warning
  }
}
