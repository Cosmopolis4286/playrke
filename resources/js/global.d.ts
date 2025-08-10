export {}; // Convierte este archivo en módulo para que declare global funcione

declare global {
  interface Window {
    ziggyPreloadPromise?: Promise<any>;
    ziggyReady?: boolean;
    route?: (name: string, params?: any) => string;
  }
}
