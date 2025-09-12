import crypto from 'crypto';

/**
 * Utilidades de encriptación - Migradas desde frontend crypt.ts
 */

/**
 * Genera un vector de inicialización (IV) aleatorio de 16 bytes
 * @returns Buffer con IV aleatorio
 */
export function generateRandomIV(): Buffer {
  return crypto.randomBytes(16);
}

/**
 * Encripta un objeto JSON usando AES-256-CBC
 * @param jsonObject - Objeto a encriptar
 * @param secretKey - Clave secreta (debe ser de 32 bytes para AES-256)
 * @returns Cadena en formato `${ivHex}:${encryptedText}`
 */
export function encryptJson(jsonObject: unknown, secretKey: string): string {
  const text = JSON.stringify(jsonObject);
  const iv = generateRandomIV();
  
  // Asegurar que la clave tenga 32 bytes para AES-256
  const key = Buffer.from(secretKey.padEnd(32, '0').slice(0, 32));
  
  const cipher = crypto.createCipher('aes-256-cbc', key);
  cipher.setAutoPadding(true);
  
  let encrypted = cipher.update(text, 'utf8', 'base64');
  encrypted += cipher.final('base64');
  
  return `${iv.toString('hex')}:${encrypted}`;
}

/**
 * Desencripta un texto en formato `${ivHex}:${encryptedText}` a objeto JSON
 * @param encryptedText - Texto cifrado con IV concatenado
 * @param secretKey - Clave secreta
 * @returns Objeto JSON original
 */
export function decryptJson(encryptedText: string, secretKey: string): any {
  const [ivHex, encryptedData] = encryptedText.split(':');
  const iv = Buffer.from(ivHex, 'hex');
  
  // Asegurar que la clave tenga 32 bytes para AES-256
  const key = Buffer.from(secretKey.padEnd(32, '0').slice(0, 32));
  
  const decipher = crypto.createDecipher('aes-256-cbc', key);
  let decrypted = decipher.update(encryptedData, 'base64', 'utf8');
  decrypted += decipher.final('utf8');
  
  return JSON.parse(decrypted);
}

/**
 * Hash una cadena usando SHA-256
 * @param text - Texto a hashear
 * @returns Hash SHA-256 en hexadecimal
 */
export function sha256Hash(text: string): string {
  return crypto.createHash('sha256').update(text).digest('hex');
}

/**
 * Genera una cadena aleatoria segura
 * @param length - Longitud de la cadena
 * @returns Cadena aleatoria en hexadecimal
 */
export function generateSecureRandom(length: number = 32): string {
  return crypto.randomBytes(Math.ceil(length / 2)).toString('hex').slice(0, length);
}

/**
 * Verifica un hash HMAC-SHA256
 * @param data - Datos originales
 * @param signature - Firma a verificar
 * @param secret - Clave secreta
 * @returns true si la firma es válida
 */
export function verifyHMAC(data: string, signature: string, secret: string): boolean {
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(data)
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature, 'hex'),
    Buffer.from(expectedSignature, 'hex')
  );
}