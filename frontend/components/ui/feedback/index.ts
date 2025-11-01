/**
 * @fileoverview Exports centralizados para componentes de feedback
 * @description Componentes para mostrar feedback, validación y estados de carga
 */

export { default as FormValidation } from './FormValidation.vue'
export { default as LoadingSpinner } from './LoadingSpinner.vue'
export { default as Tooltip } from './Tooltip.vue'

// Re-export from other locations
export { default as PasswordStrengthIndicator } from '~/components/shared/forms/PasswordStrengthIndicator.vue'
