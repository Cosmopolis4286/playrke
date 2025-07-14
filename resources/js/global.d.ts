export { }; // Esto hace que el archivo sea un módulo y evite conflictos globales

declare global {
    interface Window {
        route: (name: string, params?: any) => string;
    }
}
