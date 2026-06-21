import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import type { BackendSession, ResolvedTenantContext } from '@/shared/types/backend';

/**
 * Raised when tenant-scoped work is attempted without a backend-resolved tenant.
 * The frontend fails closed rather than guessing a tenant.
 */
export class TenantContextUnavailableError extends Error {
    constructor() {
        super('No backend-resolved tenant context is available; tenant-scoped state is withheld.');
        this.name = 'TenantContextUnavailableError';
    }
}

type TenantStatus = 'unresolved' | 'resolved';

/**
 * Frontend tenant context (ADR-001/003/004). It MIRRORS the tenant context the
 * backend resolved and nothing more:
 *
 * - never invents a tenant (no backend tenant -> unresolved),
 * - never switches silently (the only setter takes a backend-resolved session),
 * - never persists context that could outlive/contradict the backend,
 * - fails closed when context is unknown, ambiguous, or stale.
 */
export const useTenantContextStore = defineStore('tenantContext', () => {
    const status = ref<TenantStatus>('unresolved');
    const tenant = ref<ResolvedTenantContext | null>(null);

    const activeTenant = computed<ResolvedTenantContext | null>(() =>
        status.value === 'resolved' ? tenant.value : null,
    );
    const slug = computed<string | null>(() => activeTenant.value?.slug ?? null);
    /** True only when a tenant is resolved; gates all tenant-scoped data/actions. */
    const tenantScopedReady = computed<boolean>(() => status.value === 'resolved' && tenant.value !== null);

    /**
     * The ONLY way to set tenant context: adopt whatever the backend resolved.
     * If the backend resolved no tenant, we go to `unresolved` (no invention).
     */
    function applyBackendResolved(session: BackendSession): void {
        if (session.tenant === null) {
            failClosed();
            return;
        }
        tenant.value = session.tenant;
        status.value = 'resolved';
    }

    /**
     * Reconcile a (possibly persisted/stale) candidate against backend truth.
     * Backend always wins; a mismatched candidate is discarded. Returns true when
     * stale context was discarded.
     */
    function reconcileWith(backendTenant: ResolvedTenantContext | null): boolean {
        const wasStale = tenant.value !== null && backendTenant?.slug !== tenant.value.slug;
        if (backendTenant === null) {
            failClosed();
        } else {
            tenant.value = backendTenant;
            status.value = 'resolved';
        }
        return wasStale;
    }

    /** Treat tenant context as absent (unknown/ambiguous/stale) and withhold it. */
    function failClosed(): void {
        tenant.value = null;
        status.value = 'unresolved';
    }

    /** Fail-closed accessor for code paths that require a tenant. */
    function requireSlug(): string {
        if (!tenantScopedReady.value || slug.value === null) {
            throw new TenantContextUnavailableError();
        }
        return slug.value;
    }

    return {
        status,
        tenant,
        activeTenant,
        slug,
        tenantScopedReady,
        applyBackendResolved,
        reconcileWith,
        failClosed,
        requireSlug,
    };
});
