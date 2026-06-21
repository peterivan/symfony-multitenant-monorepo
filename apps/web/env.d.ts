/// <reference types="vite/client" />

interface ImportMetaEnv {
    /** Base URL of the backend that owns auth, capabilities, routes, and tenant context. */
    readonly VITE_BACKEND_BASE_URL?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
