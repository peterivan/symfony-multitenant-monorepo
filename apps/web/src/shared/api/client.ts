/**
 * Minimal shared API client. It is a transport that talks to the backend; it does
 * NOT make auth, authorization, route-exposure, or tenant-resolution decisions —
 * those are read from the backend response (ADR-004 backend-as-source-of-truth).
 */

export class ApiError extends Error {
    constructor(
        message: string,
        readonly status: number | null,
        options?: ErrorOptions,
    ) {
        super(message, options);
        this.name = 'ApiError';
    }
}

export interface ApiClientOptions {
    /** Backend base URL; defaults to same-origin so cookies/session ride along. */
    readonly baseUrl?: string;
    /** Injectable fetch (eases testing). Defaults to global fetch. */
    readonly fetch?: typeof globalThis.fetch;
}

export class ApiClient {
    private readonly baseUrl: string;
    private readonly doFetch: typeof globalThis.fetch;

    constructor(options: ApiClientOptions = {}) {
        this.baseUrl = options.baseUrl ?? import.meta.env.VITE_BACKEND_BASE_URL ?? '';
        this.doFetch = options.fetch ?? globalThis.fetch.bind(globalThis);
    }

    async getJson<T>(path: string): Promise<T> {
        return this.request<T>('GET', path);
    }

    async postJson<T>(path: string, body?: unknown): Promise<T> {
        return this.request<T>('POST', path, body);
    }

    private async request<T>(method: string, path: string, body?: unknown): Promise<T> {
        let response: Response;
        try {
            response = await this.doFetch(`${this.baseUrl}${path}`, {
                method,
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
                },
                ...(body === undefined ? {} : { body: JSON.stringify(body) }),
            });
        } catch (cause) {
            throw new ApiError(`Network request to ${path} failed`, null, { cause });
        }

        if (!response.ok) {
            throw new ApiError(`Request to ${path} failed with ${response.status}`, response.status);
        }

        return (await response.json()) as T;
    }
}
