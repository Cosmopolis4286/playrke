import { showToast} from './toastNotify.js';

// Copiar texto (usa el texto que se pasa, no el valor de un input)
export function copyInfo(text: string): void {
    if (!navigator.clipboard) {
        showToast('Tu navegador no soporta copiar al portapapeles', 'error');
        return;
    }

    navigator.clipboard.writeText(text)
        .then(() => showToast('Texto copiado: ' + text, 'success'))
        .catch(() => showToast('Error al copiar el texto', 'error'));
}