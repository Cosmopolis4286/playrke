function autoMarked() {
    // Crear una variable para almacenar el HTML nuevo
    const newHtmlTemplate = '<div class="number-box-circle"></div>';

    // Iterar sobre cada `.number-box` en el DOM
    $(".number-box").each(function () {
        const numberCtn = $(this).data('num_ctn');
        // Verificar si `numberCtn` está en el Set directamente
        if (allChipsSet.has(numberCtn)) {
            // Crear el nuevo HTML con el número
            const newHtml = $(newHtmlTemplate).text(numberCtn).prop('outerHTML');

            // Actualizar el contenido HTML solo si ha cambiado
            if ($(this).html() != newHtml) {
                $(this).html(newHtml);
            }
        }
    });
}
