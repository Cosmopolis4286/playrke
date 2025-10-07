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
        this.retries = options.retries ?? 3;
        this.timeout = options.timeout ?? 10000;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
        this.cache = new Map();
        this.cacheGet = options.cacheGet ?? false;
    }

    addRequestInterceptor(fn: RequestInterceptor) {
        this.requestInterceptors.push(fn);
    }

    addResponseInterceptor(fn: ResponseInterceptor) {
        this.responseInterceptors.push(fn);
    }

    private async runRequestInterceptors(input: RequestInfo, init?: RequestInit) {
        let currentInput = input;
        let currentInit = init;
        for (const interceptor of this.requestInterceptors) {
            [currentInput, currentInit] = await interceptor(currentInput, currentInit);
        }
        return [currentInput, currentInit] as [RequestInfo, RequestInit | undefined];
    }

    private async runResponseInterceptors(response: Response) {
        let current = response;
        for (const interceptor of this.responseInterceptors) {
            current = await interceptor(current);
        }
        return current;
    }

    /**
     * Ejecuta fetch con soporte de timeout usando AbortController.
     * Ahora acepta RequestInfo (string | Request)
     */
    private async fetchWithTimeout(input: RequestInfo, init: RequestInit = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        init.signal = controller.signal;
        try {
            return await fetch(input, init);
        } finally {
            clearTimeout(timeoutId);
        }
    }

    private delay(ms: number) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async request(input: RequestInfo, init: RequestInit = {}): Promise<any> {
        init.headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
            ...init.headers,
        };

        const method = (init.method ?? 'GET').toUpperCase();
        const isGet = method === 'GET';
        const cacheKey = isGet && typeof input === 'string' ? input : '';

        if (this.cacheGet && isGet && cacheKey && this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        // Interceptores
        let [finalInput, finalInit] = await this.runRequestInterceptors(input, init);

        for (let attempt = 0; attempt <= this.retries; attempt++) {
            try {
                const response = await this.fetchWithTimeout(finalInput, finalInit);
                const finalResponse = await this.runResponseInterceptors(response);

                if (!finalResponse.ok) {
                    if ([429, 500, 502, 503, 504].includes(finalResponse.status) && attempt < this.retries) {
                        await this.delay(2 ** attempt * 500);
                        continue;
                    }
                    const errorText = await finalResponse.text();
                    throw new Error(`HTTP ${finalResponse.status}: ${errorText}`);
                }

                const contentType = finalResponse.headers.get('Content-Type');
                const result = contentType?.includes('application/json')
                    ? await finalResponse.json()
                    : await finalResponse.text();

                if (this.cacheGet && isGet && cacheKey) this.cache.set(cacheKey, result);

                return result;

            } catch (err) {
                if (attempt === this.retries) throw err;
                await this.delay(2 ** attempt * 500);
            }
        }
    }

    get(url: RequestInfo, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'GET' });
    }

    post(url: RequestInfo, data?: any, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'POST', body: JSON.stringify(data) });
    }

    put(url: RequestInfo, data?: any, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'PUT', body: JSON.stringify(data) });
    }

    delete(url: RequestInfo, init: RequestInit = {}) {
        return this.request(url, { ...init, method: 'DELETE' });
    }
}

// Instancia global
export const fetchClient = new FetchClient({ retries: 3, timeout: 10000, cacheGet: true });
