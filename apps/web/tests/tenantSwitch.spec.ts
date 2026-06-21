import { describe, expect, it, vi } from 'vitest';
import { createActor, waitFor } from 'xstate';

import { tenantSwitchMachine } from '@/shared/machines/tenantSwitch';
import type { BackendSession } from '@/shared/types/backend';

function backendSession(slug: string): BackendSession {
    return {
        auth: { authenticated: true, surface: 'tenant_facing', identityRef: null },
        capabilities: [],
        exposedRoutes: [],
        tenant: { slug, displayName: slug },
    };
}

describe('tenant switch workflow', () => {
    it('carries the backend-resolved session out on success', async () => {
        const requestSwitch = vi.fn().mockResolvedValue(backendSession('globex'));
        const actor = createActor(tenantSwitchMachine, { input: { requestSwitch } }).start();

        actor.send({ type: 'SWITCH', slug: 'globex' });
        await waitFor(actor, (state) => state.matches('resolved'));

        expect(requestSwitch).toHaveBeenCalledWith('globex');
        expect(actor.getSnapshot().context.resolvedSession?.tenant?.slug).toBe('globex');
    });

    it('fails closed without resolving a session when the backend rejects', async () => {
        const requestSwitch = vi.fn().mockRejectedValue(new Error('denied'));
        const actor = createActor(tenantSwitchMachine, { input: { requestSwitch } }).start();

        actor.send({ type: 'SWITCH', slug: 'globex' });
        await waitFor(actor, (state) => state.matches('failed'));

        expect(actor.getSnapshot().context.resolvedSession).toBeNull();
    });
});
