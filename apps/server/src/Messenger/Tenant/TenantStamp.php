<?php

declare(strict_types=1);

namespace App\Messenger\Tenant;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Explicit tenant routing metadata carried by a dispatched message (ADR-001).
 *
 * This stamp is the ONLY way a consumer re-establishes tenant context for an
 * async execution unit. It must be added explicitly at dispatch; tenant context
 * is never implicitly inherited from the dispatcher into the message.
 */
final class TenantStamp implements StampInterface
{
    public function __construct(
        public readonly string $slug,
    ) {}
}
