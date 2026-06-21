<?php

declare(strict_types=1);

namespace App\Messenger\Tenant;

use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Exception\MissingTenantRoutingMetadataException;
use App\Tenant\TenantContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Manages tenant context across the async boundary (ADR-001).
 *
 * On the consuming side (a message received from a transport) the active tenant
 * context is stripped so nothing is inherited from the dispatcher. A
 * {@see TenantScopedMessage} is then re-established ONLY from its explicit
 * {@see TenantStamp}; without one, handling fails closed. Context is always
 * disposed after handling, including on failure, so it never leaks into the next
 * consumed message.
 *
 * On the dispatching side this middleware is a no-op: it never captures ambient
 * tenant context into a message implicitly.
 */
final class TenantContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantEntityManagerProvider $entityManagerProvider,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Only act when a worker is consuming a message off a transport; on dispatch
        // we deliberately do nothing (no implicit inheritance).
        if (null === $envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        // Start the execution unit with no inherited tenant context.
        $this->dispose();

        $message = $envelope->getMessage();

        if ($message instanceof TenantScopedMessage) {
            $stamp = $envelope->last(TenantStamp::class);

            if (!$stamp instanceof TenantStamp) {
                throw new MissingTenantRoutingMetadataException($message::class);
            }

            $this->tenantContext->setTenantSlug($stamp->slug);
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->dispose();
        }
    }

    private function dispose(): void
    {
        $this->tenantContext->reset();
        $this->entityManagerProvider->release();
    }
}
