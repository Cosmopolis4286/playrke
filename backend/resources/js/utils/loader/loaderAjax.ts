import $ from 'jquery';

// Loader — mejorado para trabajar con jQuery y más flexibilidad
export function ajaxStart(): void {
    // Mostrar todos los loaders con clase .loader o .loader-sm
    $('.loader, .loader-sm').removeClass('hidden');

    // Deshabilitar todos los botones que NO tengan data-ignore-loader="true"
    $('button:not([data-ignore-loader])').each((_, btn) => {
        const button = btn as HTMLButtonElement;
        if (!button.disabled) {
            button.dataset.wasDisabledByLoader = 'false';
            button.disabled = true;
        } else {
            // Marcar que ya estaba deshabilitado antes
            button.dataset.wasDisabledByLoader = 'true';
        }
    });

    // Opcional: emitir evento para otros listeners
    $(document).trigger('ajaxStart');
}

export function ajaxStop(): void {
    // Ocultar loaders
    $('.loader, .loader-sm').addClass('hidden');

    // Volver a habilitar solo los botones que se deshabilitaron por ajaxStart()
    $('button:not([data-ignore-loader])').each((_, btn) => {
        const button = btn as HTMLButtonElement;
        if (button.dataset.wasDisabledByLoader === 'false') {
            button.disabled = false;
        }
        delete button.dataset.wasDisabledByLoader;
    });

    // Emitir evento para otros scripts
    $(document).trigger('ajaxStop');
}
