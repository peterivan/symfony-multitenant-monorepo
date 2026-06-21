import type { ApiClient } from './client';
import type { BackendSession, ResolvedTenantContext } from '@/shared/types/backend';

/**
 * Reads backend-owned session state. The endpoint paths are configurable rather
 * than hardcoded as a contract here (ADR-004 leaves endpoint contracts out of
 * scope); the point is that this state is FETCHED from the backend, never
 * synthesized on the client.
 */
export interface SessionApiOptions {
    readonly sessionPath?: string;
    readonly tenantSwitchPath?: string;
}

const DEFAULT_SESSION_PATH = '/api/session';
const DEFAULT_TENANT_SWITCH_PATH = '/api/tenant/switch';

export class SessionApi {
    private readonly sessionPath: string;
    private readonly tenantSwitchPath: string;

    constructor(
        private readonly client: ApiClient,
        options: SessionApiOptions = {},
    ) {
        this.sessionPath = options.sessionPath ?? DEFAULT_SESSION_PATH;
        this.tenantSwitchPath = options.tenantSwitchPath ?? DEFAULT_TENANT_SWITCH_PATH;
    }

    /** Fetch the current backend-resolved session snapshot. */
    fetchSession(): Promise<BackendSession> {
        return this.client.getJson<BackendSession>(this.sessionPath);
    }

    /**
     * Ask the BACKEND to switch tenant. The frontend never switches locally; it
     * returns the backend's freshly resolved session so the caller updates state
     * only from the backend response.
     */
    requestTenantSwitch(slug: string): Promise<BackendSession> {
        return this.client.postJson<BackendSession>(this.tenantSwitchPath, { slug });
    }
}

/** Narrow a backend payload's tenant field to a resolved context or null (no invent). */
export function readResolvedTenant(session: BackendSession): ResolvedTenantContext | null {
    return session.tenant ?? null;
}
