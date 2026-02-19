/**
 * handle-bingo-patterns.js
 *
 * Lógica del cartón visual de patrones BINGO para el blade modal-bingo-patterns.
 *
 * Mapeo celda visual → número (1-75) que espera el backend:
 *   B1-B5 → 1-5     I1-I5 → 16-20    N1,N2,N4,N5 → 31,32,34,35
 *   G1-G5 → 46-50   O1-O5 → 61-65
 *
 * La casilla libre (N3 → número 33) no es seleccionable.
 */

;(function () {
  'use strict'

  /* ------------------------------------------------------------------ */
  /* Mapeo letra → número base del rango de esa columna                 */
  /* ------------------------------------------------------------------ */
  const COL_BASE = { B: 1, I: 16, N: 31, G: 46, O: 61 }
  const FREE_CELL_NUM = 33  // N3

  /**
   * Convierte una etiqueta de celda (e.g. "B3") al número real (1-75).
   * @param {string} label
   * @returns {number}
   */
  function labelToNumber(label) {
    const letter = label.charAt(0).toUpperCase()
    const row    = parseInt(label.slice(1), 10)
    return (COL_BASE[letter] ?? 0) + (row - 1)
  }

  /* ------------------------------------------------------------------ */
  /* Estado                                                               */
  /* ------------------------------------------------------------------ */
  /** @type {Set<number>} Números seleccionados (1-75) */
  let selectedBoxes = new Set()

  /* ------------------------------------------------------------------ */
  /* Helpers de UI                                                        */
  /* ------------------------------------------------------------------ */

  function updateHiddenInput() {
    const sorted = [...selectedBoxes].sort((a, b) => a - b)
    $('#bingo-boxes').val(sorted.join(';'))
  }

  /* ------------------------------------------------------------------ */
  /* Funciones globales requeridas por el blade                           */
  /* ------------------------------------------------------------------ */

  /**
   * Limpia toda la selección del cartón.
   */
  window.cleanBoxes = function cleanBoxes() {
    selectedBoxes.clear()
    $('.ctn-patterns').removeClass('number-box-circle')
    updateHiddenInput()
  }

  /**
   * Restaura la selección a partir de un string de números "1;16;31;...".
   * Llamada al cargar datos para edición.
   * @param {string} boxesStr
   */
  window.checkBoxes = function checkBoxes(boxesStr) {
    window.cleanBoxes()
    if (!boxesStr) return

    const nums = new Set(
      String(boxesStr)
        .split(';')
        .map(s => parseInt(s.trim(), 10))
        .filter(n => !isNaN(n) && n >= 1 && n <= 75)
    )

    $('.ctn-patterns').each(function () {
      const label = $(this).text().trim()
      const num   = labelToNumber(label)
      if (nums.has(num)) {
        $(this).addClass('number-box-circle')
        selectedBoxes.add(num)
      }
    })

    updateHiddenInput()
  }

  /* ------------------------------------------------------------------ */
  /* Click en celdas del cartón                                           */
  /* ------------------------------------------------------------------ */

  $(document).on('click', '.ctn-patterns', function () {
    const $cell = $(this)
    const label = $cell.text().trim()
    const num   = labelToNumber(label)

    if (num === FREE_CELL_NUM) return  // casilla libre, no se toca

    $cell.toggleClass('number-box-circle')

    if ($cell.hasClass('number-box-circle')) {
      selectedBoxes.add(num)
    } else {
      selectedBoxes.delete(num)
    }

    updateHiddenInput()
  })

  /* ------------------------------------------------------------------ */
  /* Botón limpiar                                                        */
  /* ------------------------------------------------------------------ */

  $(document).on('click', '#btn-clean', function () {
    window.cleanBoxes()
  })

  /* ------------------------------------------------------------------ */
  /* Botón confirmar / guardar                                            */
  /* ------------------------------------------------------------------ */

  $(document).on('click', '#btn-confirm', function () {
    const patternName = $('#name-pattern').val()?.trim()

    if (!patternName) {
      showToast('Es obligatorio un nombre de jugada', 'info')
      return
    }

    if (selectedBoxes.size < 3) {
      showToast('Es necesario un mínimo de 3 casillas seleccionadas', 'info')
      return
    }

    updateHiddenInput()

    // `name` es la variable global inyectada por el blade (moduleId)
    const moduleId = (typeof name !== 'undefined' && name) ? name : 'bingo-patterns'
    $(`#form-${moduleId}`).submit()
  })
})()
