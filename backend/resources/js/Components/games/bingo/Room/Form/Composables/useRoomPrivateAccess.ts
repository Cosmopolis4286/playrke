// useRoomPrivateAccess.ts

/** Palabras para el código de acceso (ver generateAccessCode). */
const ACCESS_CODE_WORDS = [
  'BINGO', 'FIESTA', 'RONDA', 'JUEGO', 'SUERTE', 'CARTON', 'PREMIO',
  'ESTRELLA', 'DORADO', 'REAL', 'CLUB', 'SALA'
]

// Sin 0/O ni 1/I/L (mismo alfabeto que BingoPrivateRoomAccess en el backend).
const ACCESS_CODE_SUFFIX_CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'

/**
 * Código legible y con muchas combinaciones (ej. "FIESTA-7K3M"): el código es
 * único entre salas privadas abiertas, así que una palabra + 2 dígitos
 * chocaba seguido. El backend igual valida que no esté en uso.
 */
export function generateAccessCode(): string {
  const word = ACCESS_CODE_WORDS[secureRandomInt(ACCESS_CODE_WORDS.length)]
  let suffix = ''
  for (let i = 0; i < 4; i++) {
    suffix += ACCESS_CODE_SUFFIX_CHARS[secureRandomInt(ACCESS_CODE_SUFFIX_CHARS.length)]
  }
  return `${word.slice(0, 15)}-${suffix}`
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
