import { ref, type Ref } from 'vue'

/**
 * Encapsula la lógica de "el usuario cerró el modal de fin de juego".
 * El flag se persiste en sessionStorage para sobrevivir recargas parciales
 * dentro de la misma sesión (sin que el usuario tenga que volver a cerrarlo).
 */
export function useModalDismiss(key: Ref<string>) {
  const dismissed = ref(false)

  function _read() {
    if (!key.value) return false
    return sessionStorage.getItem(key.value) === '1'
  }

  function init() {
    dismissed.value = _read()
  }

  function dismiss() {
    dismissed.value = true
    if (key.value) sessionStorage.setItem(key.value, '1')
  }

  function reset() {
    dismissed.value = false
    if (key.value) sessionStorage.removeItem(key.value)
  }

  return { dismissed, init, dismiss, reset }
}
