function autoMarked() {
    const $numberBoxes = $(".number-box");
    
    $numberBoxes.each(function () {
        const $this = $(this);
        const numberCtn = $this.data('num_ctn');

        if (allChipsSet.has(numberCtn)) {
            // Solo actualiza si el contenido actual es diferente
            if ($this.text() !== String(numberCtn)) {
                $this.html(`<div class="number-box-circle">${numberCtn}</div>`);
            }
        }
    });
}

window.autoMarked = autoMarked;