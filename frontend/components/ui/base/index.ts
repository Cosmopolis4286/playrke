/**
 * @fileoverview Exports optimizados para componentes UI base
 * @description Componentes básicos con tree-shaking y lazy loading
 */

import { defineAsyncComponent } from 'vue'

// Componentes base con lazy loading y tree-shaking
export const Button = defineAsyncComponent(() => import('./Button.vue'))
export const Card = defineAsyncComponent(() => import('./Card.vue'))
export const Input = defineAsyncComponent(() => import('./Input.vue'))
export const Badge = defineAsyncComponent(() => import('./Badge.vue'))
export const Select = defineAsyncComponent(() => import('./Select.vue'))
export const StatCard = defineAsyncComponent(() => import('./StatCard.vue'))

// Componentes de modal con lazy loading
export const Modal = defineAsyncComponent(() => import('./Modal.vue'))
export const ModalContent = defineAsyncComponent(() => import('./ModalContent.vue'))
export const ModalHeader = defineAsyncComponent(() => import('./ModalHeader.vue'))
export const ModalFooter = defineAsyncComponent(() => import('./ModalFooter.vue'))
export const ModalElement = defineAsyncComponent(() => import('./ModalElement.vue'))
export const ModalProgress = defineAsyncComponent(() => import('./ModalProgress.vue'))

// Export por defecto para importación de todos los componentes (usar con precaución)
export default {
  Button,
  Card,
  Input,
  Badge,
  Select,
  StatCard,
  Modal,
  ModalContent,
  ModalHeader,
  ModalFooter,
  ModalElement,
  ModalProgress,
}
