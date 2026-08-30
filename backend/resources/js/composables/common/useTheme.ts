import { ref } from 'vue'

/**
 * Puente reactivo Vue hacia el sistema de tema ya existente en el navbar
 * Blade (resources/views/components/template/navbar.blade.php) — mismo
 * localStorage key y mismo atributo `data-theme` en <html>, para que
 * cambiar el tema desde cualquier lado (navbar o un componente Vue) quede
 * sincronizado sin duplicar el mecanismo ni desincronizar el estado.
 *
 * No reimplementa la aplicación del tema: solo lee/escribe el mismo
 * localStorage + atributo que el script del navbar ya inicializa en cada
 * carga de página, así que ambos siempre coinciden.
 */
const STORAGE_KEY = 'playrke-theme'

function readTheme(): 'light' | 'dark' {
    return localStorage.getItem(STORAGE_KEY) === 'light' ? 'light' : 'dark'
}

const theme = ref<'light' | 'dark'>(readTheme())

function applyTheme(next: 'light' | 'dark'): void {
    if (next === 'light') {
        document.documentElement.setAttribute('data-theme', 'light')
    } else {
        document.documentElement.removeAttribute('data-theme')
    }
    localStorage.setItem(STORAGE_KEY, next)
    theme.value = next
}

// Sincroniza si el tema cambia en otra pestaña del navegador (el toggle del
// navbar es JS vanilla, no Vue — este listener es lo único que mantiene
// theme.value al día si el cambio no vino de este mismo composable).
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (e) => {
        if (e.key === STORAGE_KEY) theme.value = readTheme()
    })
}

export function useTheme() {
    function toggleTheme(): void {
        applyTheme(theme.value === 'light' ? 'dark' : 'light')
    }

    function setTheme(next: 'light' | 'dark'): void {
        applyTheme(next)
    }

    return { theme, toggleTheme, setTheme }
}
