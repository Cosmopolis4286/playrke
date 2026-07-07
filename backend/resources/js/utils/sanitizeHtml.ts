const BLOCKED_TAGS = 'script,style,iframe,object,embed,form'

/**
 * Sanitiza HTML de entrada eliminando tags peligrosos, event handlers
 * y hrefs javascript:. Usa DOMParser nativo, sin dependencias.
 *
 * Uso apropiado: contenido generado por admins antes de v-html.
 * NO usar para contenido de usuarios no confiables en producción —
 * usar una librería dedicada como DOMPurify en ese caso.
 */
export function sanitizeHtml(html: string): string | null {
  const doc = new DOMParser().parseFromString(html, 'text/html')

  doc.querySelectorAll(BLOCKED_TAGS).forEach(el => el.remove())

  doc.querySelectorAll('*').forEach(el => {
    for (const attr of Array.from(el.attributes)) {
      if (
        attr.name.startsWith('on') ||
        (attr.name === 'href' && attr.value.trimStart().toLowerCase().startsWith('javascript'))
      ) {
        el.removeAttribute(attr.name)
      }
    }
  })

  return doc.body.innerHTML || null
}
