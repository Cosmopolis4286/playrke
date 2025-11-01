<template>
  <div
    v-if="password"
    :class="containerClasses"
  >
    <!-- Strength Bar -->
    <div class="relative">
      <div class="flex gap-1 mb-2">
        <div
          v-for="i in 4"
          :key="i"
          :class="[
            'h-1 rounded-full transition-all duration-300 flex-1',
            getBarColor(i),
          ]"
        />
      </div>

      <!-- Strength Label -->
      <div class="flex items-center justify-between">
        <span :class="strengthLabelClasses">
          {{ strengthText }}
        </span>
        <span class="text-xs text-gray-500">
          {{ score }}/4
        </span>
      </div>
    </div>

    <!-- Requirements Checklist -->
    <div
      v-if="showRequirements"
      class="mt-3 space-y-2"
    >
      <div
        v-for="(requirement, index) in requirements"
        :key="index"
        class="flex items-center gap-2 text-xs"
      >
        <Icon
          :name="requirement.met ? 'check-circle' : 'x-circle'"
          :class="[
            'w-4 h-4',
            requirement.met ? 'text-green-600' : 'text-gray-400',
          ]"
        />
        <span :class="requirement.met ? 'text-green-700' : 'text-gray-600'">
          {{ requirement.text }}
        </span>
      </div>
    </div>

    <!-- Tips for improvement -->
    <div
      v-if="showTips && improvementTips.length > 0"
      class="mt-3"
    >
      <p class="text-xs text-gray-600 mb-1">
        💡 Para mejorar:
      </p>
      <ul class="text-xs text-gray-500 space-y-1">
        <li
          v-for="tip in improvementTips"
          :key="tip"
          class="flex items-start gap-1"
        >
          <span class="text-primary-500">•</span>
          {{ tip }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview PasswordStrengthIndicator - Indicador visual de fortaleza de contraseña
 * @description Componente que evalúa y muestra la fortaleza de contraseñas con feedback visual
 * @author Lotto38 Team
 * @version 1.0.0
 * @since 2025-09-17
 */

import { computed } from 'vue'

interface Props {
  /** Contraseña a evaluar */
  password: string
  /** Mostrar lista de requisitos */
  showRequirements?: boolean
  /** Mostrar consejos de mejora */
  showTips?: boolean
  /** Tamaño del indicador */
  size?: 'sm' | 'md' | 'lg'
  /** Variante visual */
  variant?: 'default' | 'compact' | 'detailed'
}

const props = withDefaults(defineProps<Props>(), {
  showRequirements: true,
  showTips: true,
  size: 'md',
  variant: 'default',
})

// Cálculo de fortaleza de contraseña
const passwordAnalysis = computed(() => {
  if (!props.password) {
    return {
      score: 0,
      hasLength: false,
      hasLowercase: false,
      hasUppercase: false,
      hasNumber: false,
      hasSpecial: false,
      entropy: 0,
    }
  }

  const password = props.password

  return {
    score: 0, // Se calculará después
    hasLength: password.length >= 8,
    hasLowercase: /[a-z]/.test(password),
    hasUppercase: /[A-Z]/.test(password),
    hasNumber: /\d/.test(password),
    hasSpecial: /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(password),
    entropy: calculateEntropy(password),
  }
})

// Puntuación de fortaleza (0-4)
const score = computed(() => {
  const analysis = passwordAnalysis.value
  let score = 0

  // Criterios básicos
  if (analysis.hasLength) { score++ }
  if (analysis.hasLowercase && analysis.hasUppercase) { score++ }
  if (analysis.hasNumber) { score++ }
  if (analysis.hasSpecial) { score++ }

  // Bonus por longitud extra
  if (props.password.length >= 12) { score = Math.min(4, score + 0.5) }

  // Penalty por patrones comunes
  if (hasCommonPatterns(props.password)) { score = Math.max(0, score - 1) }

  return Math.round(score)
})

// Texto descriptivo de fortaleza
const strengthText = computed(() => {
  switch (score.value) {
    case 0: return 'Muy débil'
    case 1: return 'Débil'
    case 2: return 'Regular'
    case 3: return 'Fuerte'
    case 4: return 'Muy fuerte'
    default: return 'Sin evaluar'
  }
})

// Color de la barra de progreso
const getBarColor = (barIndex: number) => {
  if (barIndex > score.value) { return 'bg-gray-200' }

  switch (score.value) {
    case 1: return 'bg-red-500'
    case 2: return 'bg-orange-500'
    case 3: return 'bg-yellow-500'
    case 4: return 'bg-green-500'
    default: return 'bg-gray-200'
  }
}

// Lista de requisitos
const requirements = computed(() => {
  const analysis = passwordAnalysis.value

  return [
    {
      text: '8+ caracteres',
      met: analysis.hasLength,
    },
    {
      text: 'Mayúscula (A-Z)',
      met: analysis.hasUppercase,
    },
    {
      text: 'Minúscula (a-z)',
      met: analysis.hasLowercase,
    },
    {
      text: 'Número (0-9)',
      met: analysis.hasNumber,
    },
    {
      text: 'Carácter especial (!@#$)',
      met: analysis.hasSpecial,
    },
  ]
})

// Consejos de mejora
const improvementTips = computed(() => {
  const analysis = passwordAnalysis.value
  const tips: string[] = []

  if (!analysis.hasLength) {
    tips.push('Usa al menos 8 caracteres')
  }
  if (!analysis.hasUppercase) {
    tips.push('Incluye letras mayúsculas')
  }
  if (!analysis.hasLowercase) {
    tips.push('Incluye letras minúsculas')
  }
  if (!analysis.hasNumber) {
    tips.push('Añade números')
  }
  if (!analysis.hasSpecial) {
    tips.push('Usa símbolos especiales (!@#$%)')
  }
  if (props.password.length < 12 && score.value >= 2) {
    tips.push('Hazla más larga para mayor seguridad')
  }

  return tips.slice(0, 3) // Máximo 3 consejos
})

// Estilos computados
const containerClasses = computed(() => [
  'password-strength-indicator',
  {
    'text-sm': props.size === 'sm',
    'text-base': props.size === 'md',
    'text-lg': props.size === 'lg',
  },
])

const strengthLabelClasses = computed(() => [
  'text-xs font-medium',
  {
    'text-red-600': score.value <= 1,
    'text-orange-600': score.value === 2,
    'text-yellow-600': score.value === 3,
    'text-green-600': score.value === 4,
    'text-gray-600': score.value === 0,
  },
])

// Utilidades
function calculateEntropy(password: string): number {
  const charset = new Set(password.split(''))
  return Math.log2(charset.size) * password.length
}

function hasCommonPatterns(password: string): boolean {
  const commonPatterns = [
    /123456/,
    /abcdef/,
    /qwerty/,
    /password/i,
    /(\w)\1{2,}/, // Repetición de caracteres
  ]

  return commonPatterns.some(pattern => pattern.test(password))
}
</script>

<style scoped>
.password-strength-indicator {
  @apply transition-all duration-300;
}

/* Animaciones para las barras */
.h-1 {
  transition: background-color 0.3s ease, transform 0.2s ease;
}

.h-1:hover {
  transform: scaleY(1.2);
}
</style>
