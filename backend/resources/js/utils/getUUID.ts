/**
 * Genera un UUID versión 4 (random).
 * 
 * @param isUpperCase - Si es true, retorna el UUID en mayúsculas; si es false, en minúsculas. Por defecto es true.
 * @returns UUID en formato string.
 */
export function generateUUID(isUpperCase = true): string {
    const uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = Math.floor(Math.random() * 16);
        // 'x' se reemplaza por un dígito hexadecimal aleatorio
        // 'y' se reemplaza por un dígito entre 8 y b (para cumplir con el estándar UUID v4)
        const value = char === 'x' ? random : (random & 0x3) | 0x8;
        return value.toString(16);
    });

    return isUpperCase ? uuid.toUpperCase() : uuid.toLowerCase();
}