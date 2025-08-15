/**
 * Opciones configurables para FetchClient.
 */
interface FetchClientOptions {
    retries?: number;       // Máximo número de reintentos ante errores temporales
    timeout?: number;       // Tiempo máximo de espera de la respuesta (ms)
    cacheGet?: boolean;     // Activar caching en memoria para peticiones GET
}

/**
 * Tipo de función para interceptar y modificar la request antes de enviarla.
 */
type RequestInterceptor = (input: RequestInfo, init?: RequestInit) => Promise<[RequestInfo, RequestInit?]>;

/**
 * Tipo de función para interceptar y modificar la respuesta antes de devolverla.
 */
type ResponseInterceptor = (response: Response) => Promise<Response>;

/**
 * Clase que envuelve fetch con características avanzadas:
 * - Retry exponencial en errores temporales
 * - Timeout configurable
 * - Cache de GETs en memoria
 * - CSRF token automático
 * - Interceptores de request y response
 */
export class FetchClient {
    private requestInterceptors: RequestInterceptor[] = [];
    private responseInterceptors: ResponseInterceptor[] = [];
    private retries: number;
    private timeout: number;
    private csrfToken: string | null;
    private cache: Map<string, any>;
    private cacheGet: boolean;

    constructor(options: FetchClientOptions = {}) {
        this.retries = options.retries ?? 3;                  // Por defecto 3 reintentos
        this.timeout = options.timeout ?? 10000;             // Timeout por defecto 10s
        // Buscar token CSRF desde meta tag
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
        this.cache = new Map();                               // Cache en memoria
        this.cacheGet = options.cacheGet ?? false;           // Cache GET desactivado por defecto
    }

    /**
     * Añade un interceptor de request.
     */
    addRequestInterceptor(fn: RequestInterceptor) {
        this.requestInterceptors.push(fn);
    }

    /**
     * Añade un interceptor de response.
     */
    addResponseInterceptor(fn: ResponseInterceptor) {
        this.responseInterceptors.push(fn);
    }

    /**
     * Ejecuta todos los interceptores de request en orden.
     */
    private async runRequestInterceptors(input: RequestInfo, init?: RequestInit) {
        let currentInput = input;
        let currentInit = init;
        for (const interceptor of this.requestInterceptors) {
            [currentInput, currentInit] = await interceptor(currentInput, currentInit);
        }
        return [currentInput, currentInit] as [RequestInfo, RequestInit | undefined];
    }

    /**
     * Ejecuta todos los interceptores de response en orden.
     */
    private async runResponseInterceptors(response: Response) {
        let current = response;
        for (const interceptor of this.responseInterceptors) {
            current = await interceptor(current);
        }
        return current;
    }

    /**
     * Ejecuta fetch con soporte de timeout usando AbortController.
     */
    private async fetchWithTimeout(url: string, init: RequestInit) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        init.signal = controller.signal;
        try {
            return await fetch(url, init);
        } finally {
            clearTimeout(timeoutId);
        }
    }

    /**
     * Pequeña función de delay para reintentos.
     */
    private delay(ms: number) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Método principal para realizar cualquier petición HTTP.
     * Aplica headers, CSRF, interceptores, retries y cache.
     */
    async request(url: string, init: RequestInit = {}): Promise<any> {
        // Agregar headers comunes
        init.headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
            ...init.headers,
        };

        const isGet = (init.method ?? 'GET').toUpperCase() === 'GET';
        const cacheKey = isGet ? url : '';

        // Retornar cache si existe
        if (this.cacheGet && isGet && this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        // Ejecutar interceptores de request
        let [finalUrl, finalInit] = await this.runRequestInterceptors(url, init);

        for (let attempt = 0; attempt <= this.retries; attempt++) {
            try {
                // Fetch con timeout
                const response = await this.fetchWithTimeout(finalUrl, finalInit);
                // Ejecutar interceptores de response
                const finalResponse = await this.runResponseInterceptors(response);

                if (!finalResponse.ok) {
                    // Reintentar solo en errores temporales
                    if ([429, 500, 502, 503, 504].includes(finalResponse.status) && attempt < this.retries) {
                        await this.delay(2 ** attempt * 500); // Retry exponencial
                        continue;
                    }
                    const errorText = await finalResponse.text();
                    throw new Error(`HTTP ${finalResponse.status}: ${errorText}`);
                }

                // Parsear JSON si aplica, o retornar texto plano
                const contentType = finalResponse.headers.get('Content-Type');
                let result;
                if (contentType && contentType.includes('application/json')) {
                    result = await finalResponse.json();
                } else {
                    result = await finalResponse.text();
                }

                // Guardar en cache si corresponde
                if (this.cacheGet && isGet) this.cache.set(cacheKey, result);
                return result;

            } catch (err) {
                // Si fue el último intento, lanzar error
                if (attempt === this.retries) throw err;
                // Retry exponencial
                await this.delay(2 ** attempt * 500);
            }
        }
    }

    // Métodos helper para los principales verbos HTTP
    get(url: string, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'GET' });
    }

    post(url: string, data?: any, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'POST', body: JSON.stringify(data) });
    }

    put(url: string, data?: any, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'PUT', body: JSON.stringify(data) });
    }

    delete(url: string, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'DELETE' });
    }
}

/**
 * Instancia global opcional para usar en toda la aplicación
 */
export const fetchClient = new FetchClient({ retries: 3, timeout: 10000, cacheGet: true });
