import { ref } from 'vue'

/**
 * Composable para manejar el estado de mostrar/ocultar contraseña.
 * @returns {object} showPassword y función togglePassword
 */
export function usePasswordToggle() {
  const showPassword = ref(false)

  /**
   * Alterna el estado de visibilidad de la contraseña.
   */
  const togglePassword = () => {
    showPassword.value = !showPassword.value
  }

  return {
    showPassword,
    togglePassword
  }
}
