/**
 * Plugin del motor de juego de Bingo
 * Maneja la lógica del juego, validación de cartones y detección de ganadores
 */

export default defineNuxtPlugin(() => {
  // Generar cartón de bingo aleatorio
  const generateCard = (id: number) => {
    const carton: number[][] = []

    for (let col = 0; col < 5; col++) {
      const column: number[] = []
      const min = col * 15 + 1
      const max = col * 15 + 15

      const numbers = Array.from({ length: 15 }, (_, i) => min + i)
      const selected = numbers.sort(() => Math.random() - 0.5).slice(0, 5)

      column.push(...selected.sort((a, b) => a - b))
      carton.push(column)
    }

    // Centro libre (FREE)
    carton[2][2] = 0

    return {
      id,
      numbers: carton,
      marked: Array(5).fill(null).map(() => Array(5).fill(false)),
      patterns: []
    }
  }

  // Verificar si hay Bingo
  const checkBingo = (carton: any, pattern: string = 'full') => {
    const { marked } = carton

    switch (pattern) {
      case 'line-horizontal':
        return marked.some((row: boolean[]) => row.every(m => m))

      case 'line-vertical':
        return marked[0].some((_, col: number) =>
          marked.every((row: boolean[]) => row[col])
        )

      case 'diagonal':
        const diagonal1 = marked.every((row: boolean[], i: number) => row[i])
        const diagonal2 = marked.every((row: boolean[], i: number) => row[4 - i])
        return diagonal1 || diagonal2

      case 'full':
        return marked.every((row: boolean[]) => row.every(m => m))

      default:
        return false
    }
  }

  // Marcar número en cartón
  const markNumber = (carton: any, number: number) => {
    for (let col = 0; col < 5; col++) {
      for (let row = 0; row < 5; row++) {
        if (carton.numbers[col][row] === number) {
          carton.marked[col][row] = true
          return true
        }
      }
    }
    return false
  }

  return {
    provide: {
      gameEngine: {
        generateCard,
        checkBingo,
        markNumber
      }
    }
  }
})
