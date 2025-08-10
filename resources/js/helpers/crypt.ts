import CryptoJS from 'crypto-js';

/**
 * Genera un vector de inicialización (IV) aleatorio de 16 bytes (128 bits).
 * @returns {CryptoJS.lib.WordArray} Vector de inicialización aleatorio.
 */
export function generateRandomIV(): CryptoJS.lib.WordArray {
    return CryptoJS.lib.WordArray.random(16);
}

/**
 * Encripta un objeto JSON usando AES con clave y IV.
 * @param jsonObject - Objeto JSON a encriptar.
 * @param secretKeyBase64 - Clave secreta en base64 para cifrado AES.
 * @returns {string} Cadena en formato `${ivHex}:${encryptedText}`.
 */
export function encryptJson(jsonObject: unknown, secretKeyBase64: string): string {
    const text = JSON.stringify(jsonObject);
    const iv = generateRandomIV();
    const key = CryptoJS.enc.Base64.parse(secretKeyBase64);

    const encrypted = CryptoJS.AES.encrypt(text, key, { iv });
    const ivHex = iv.toString(CryptoJS.enc.Hex);
    const encryptedText = encrypted.toString();

    return `${ivHex}:${encryptedText}`;
}

/**
 * Desencripta un texto cifrado en formato `${ivHex}:${encryptedText}` a objeto JSON.
 * @param encryptedText - Texto cifrado con IV concatenado (hex:base64).
 * @param secretKeyBase64 - Clave secreta en base64 para descifrado AES.
 * @returns {any} Objeto JSON original.
 */
export function decryptJson(encryptedText: string, secretKeyBase64: string): any {
    const [ivHex, encryptedData] = encryptedText.split(':');
    const iv = CryptoJS.enc.Hex.parse(ivHex);
    const key = CryptoJS.enc.Base64.parse(secretKeyBase64);

    const decrypted = CryptoJS.AES.decrypt(encryptedData, key, { iv });
    const decryptedText = decrypted.toString(CryptoJS.enc.Utf8);

    return JSON.parse(decryptedText);
}
