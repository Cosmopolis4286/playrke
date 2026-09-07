const STORAGE_KEY = 'playrke:last-login'

/**
 * Recuerda el último email/usuario usado en login (nunca el password) para
 * reducir fricción de re-login. Guardado solo en localStorage del navegador,
 * sin backend ni endpoint — "remember" del checkbox sigue siendo una
 * decisión explícita del usuario, este composable no la infiere.
 */
export function useRememberedLogin() {
  function get(): string {
    try {
      return localStorage.getItem(STORAGE_KEY) ?? ''
    } catch {
      return ''
    }
  }

  function set(login: string): void {
    try {
      if (login) localStorage.setItem(STORAGE_KEY, login)
    } catch {
      // Silencioso: localStorage puede fallar (modo privado, cuota, etc.)
    }
  }

  function clear(): void {
    try {
      localStorage.removeItem(STORAGE_KEY)
    } catch {
      // Silencioso, mismo criterio que set().
    }
  }

  return { get, set, clear }
}
