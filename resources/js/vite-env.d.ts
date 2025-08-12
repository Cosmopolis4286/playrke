/// <reference types="vite/client" />

import type { ImportGlobFunction } from 'vite';

interface ImportMetaEnv {
    readonly VITE_APP_URL: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: ImportGlobFunction;
}
