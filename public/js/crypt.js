function generateRandomIV() {
    return CryptoJS.lib.WordArray.random(16); // Genera un IV aleatorio de 16 bytes
}

function encryptJson(jsonObject, secretKeyBase64) {
    const text = JSON.stringify(jsonObject); // Convierte el objeto JSON a una cadena
    const iv = generateRandomIV();
    const key = CryptoJS.enc.Base64.parse(secretKeyBase64); // Convierte la clave base64 a binario
    const encrypted = CryptoJS.AES.encrypt(text, key, { iv: iv });
    const ivHex = iv.toString(CryptoJS.enc.Hex);
    const encryptedText = encrypted.toString();
    return `${ivHex}:${encryptedText}`;
}

function decryptJson(encryptedText, secretKeyBase64) {
    const [ivHex, encryptedData] = encryptedText.split(':');
    const iv = CryptoJS.enc.Hex.parse(ivHex);
    const key = CryptoJS.enc.Base64.parse(secretKeyBase64); // Convierte la clave base64 a binario
    const decrypted = CryptoJS.AES.decrypt(encryptedData, key, { iv: iv });
    const decryptedText = decrypted.toString(CryptoJS.enc.Utf8);
    return JSON.parse(decryptedText); // Convierte la cadena JSON a un objeto
}
