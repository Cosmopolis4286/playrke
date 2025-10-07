export function debounce<T extends (...args: any[]) => void>(func: T, delay: number): T {
    let timeout: number | null = null;
    return function (this: any, ...args: any[]) {
        if (timeout) return;
        timeout = window.setTimeout(() => timeout = null, delay);
        func.apply(this, args);
    } as T;
}

export function animate(duration: number, callback: () => void) {
    const start = performance.now();
    function step(now: number) {
        if (now - start < duration) requestAnimationFrame(step);
        else callback();
    }
    requestAnimationFrame(step);
}

export function preloadImages(urls: string[]) {
    return Promise.all(urls.map(url => new Promise<void>(resolve => {
        const img = new Image();
        img.src = url;
        img.onload = img.onerror = () => resolve();
    })));
}

export function createElement<K extends keyof HTMLElementTagNameMap>(
    tag: K,
    options: Partial<HTMLElementTagNameMap[K]> & { className?: string, styles?: Partial<CSSStyleDeclaration> } = {}
): HTMLElementTagNameMap[K] {
    const el = document.createElement(tag);
    if (options.className) el.className = options.className;
    if (options.styles) Object.assign(el.style, options.styles);
    Object.assign(el, options);
    return el;
}
