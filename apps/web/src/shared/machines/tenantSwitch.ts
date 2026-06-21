import { assign, fromPromise, setup } from 'xstate';

import type { BackendSession } from '@/shared/types/backend';

/**
 * Explicit tenant-switch workflow (a legitimate XState use per ADR-004: discrete
 * states, an async invocation, and branching success/failure outcomes).
 *
 * The machine NEVER switches tenant locally. It asks the backend to switch and
 * carries the backend-resolved session out on success so the caller updates the
 * tenant-context store only from that backend response — no silent client switch.
 */
export interface TenantSwitchInput {
    /** Asks the backend to switch; resolves with the backend-resolved session. */
    readonly requestSwitch: (slug: string) => Promise<BackendSession>;
}

export interface TenantSwitchContext {
    readonly requestSwitch: (slug: string) => Promise<BackendSession>;
    requestedSlug: string | null;
    resolvedSession: BackendSession | null;
    error: string | null;
}

export type TenantSwitchEvent = { type: 'SWITCH'; slug: string } | { type: 'RESET' };

export const tenantSwitchMachine = setup({
    types: {
        input: {} as TenantSwitchInput,
        context: {} as TenantSwitchContext,
        events: {} as TenantSwitchEvent,
    },
    actors: {
        requestSwitch: fromPromise<BackendSession, { requestSwitch: TenantSwitchInput['requestSwitch']; slug: string }>(
            ({ input }) => input.requestSwitch(input.slug),
        ),
    },
}).createMachine({
    id: 'tenantSwitch',
    initial: 'idle',
    context: ({ input }) => ({
        requestSwitch: input.requestSwitch,
        requestedSlug: null,
        resolvedSession: null,
        error: null,
    }),
    states: {
        idle: {
            on: {
                SWITCH: {
                    target: 'switching',
                    actions: assign({
                        requestedSlug: ({ event }) => event.slug,
                        error: null,
                        resolvedSession: null,
                    }),
                },
            },
        },
        switching: {
            invoke: {
                src: 'requestSwitch',
                input: ({ context }) => ({
                    requestSwitch: context.requestSwitch,
                    slug: context.requestedSlug ?? '',
                }),
                onDone: {
                    target: 'resolved',
                    actions: assign({ resolvedSession: ({ event }) => event.output }),
                },
                onError: {
                    // Fail closed: no local switch occurs on failure.
                    target: 'failed',
                    actions: assign({ error: ({ event }) => String(event.error) }),
                },
            },
        },
        resolved: {
            on: { RESET: 'idle', SWITCH: { target: 'switching', actions: assign({ requestedSlug: ({ event }) => event.slug }) } },
        },
        failed: {
            on: { RESET: 'idle', SWITCH: { target: 'switching', actions: assign({ requestedSlug: ({ event }) => event.slug }) } },
        },
    },
});
