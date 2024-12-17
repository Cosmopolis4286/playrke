import $ from 'jquery'; // Importa jQuery
import { ajaxStart } from '../rke-functions'; // Importa tu función

test('ajaxStart debería mostrar el loader y deshabilitar botones', () => {
    // Configuración del DOM
    document.body.innerHTML = `
        <div id="loader" style="display: none;"></div>
        <button class="btn"></button>
    `;
    
    // Ejecutar la función
    ajaxStart();

    // Verificaciones
    expect(document.querySelector('#loader').style.display).toBe('block');
    expect(document.querySelector('.btn').disabled).toBe(true);
});
