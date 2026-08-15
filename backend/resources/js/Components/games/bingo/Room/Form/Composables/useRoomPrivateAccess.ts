// useRoomPrivateAccess.ts

/**
 * Genera un código de acceso legible para compartir (ej. "FAMILIA2026" en el
 * placeholder) — combina 2 palabras cortas + un número de 2 dígitos, fácil de
 * dictar/recordar. Longitud típica 8-14 caracteres, dentro del rango
 * min:4/max:20 validado en BingoRoomRequest.php.
 */
const ACCESS_CODE_WORDS = [
  'BINGO', 'FIESTA', 'RONDA', 'JUEGO', 'SUERTE', 'CARTON', 'PREMIO',
  'ESTRELLA', 'DORADO', 'REAL', 'CLUB', 'SALA',
]

export function generateAccessCode(): string {
  const word = ACCESS_CODE_WORDS[secureRandomInt(ACCESS_CODE_WORDS.length)]
  const number = secureRandomInt(90) + 10 // 2 dígitos, 10-99
  return `${word}${number}`
}

/**
 * Genera una contraseña aleatoria segura para el acceso a la sala — mezcla de
 * mayúsculas, minúsculas y dígitos, sin caracteres ambiguos (0/O, 1/l/I) para
 * que sea legible al compartirla verbalmente o por chat. 10 caracteres,
 * dentro del rango min:6/max:100 validado en BingoRoomRequest.php.
 */
const PASSWORD_CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'
const PASSWORD_LENGTH = 10

export function generateRoomPassword(): string {
  let out = ''
  for (let i = 0; i < PASSWORD_LENGTH; i++) {
    out += PASSWORD_CHARS[secureRandomInt(PASSWORD_CHARS.length)]
  }
  return out
}

/** Entero aleatorio en [0, max) usando crypto.getRandomValues (no Math.random) — estos valores son credenciales de acceso reales, no solo IDs de UI. */
function secureRandomInt(max: number): number {
  const buf = new Uint32Array(1)
  crypto.getRandomValues(buf)
  return buf[0] % max
}
