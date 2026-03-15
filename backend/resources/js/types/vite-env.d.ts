/// <reference types="vite/client" />

import type { ImportGlobFunction } from 'vite';

interface ImportMetaEnv {
    readonly VITE_APP_URL: string;
    readonly VITE_REVERB_APP_KEY: string;
    readonly VITE_REVERB_HOST?: string;
    readonly VITE_REVERB_PORT?: string;
    readonly VITE_REVERB_SCHEME?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: ImportGlobFunction;
}
