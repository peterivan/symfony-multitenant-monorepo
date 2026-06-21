<?php

declare(strict_types=1);

namespace App\Http\Boundary;

/**
 * Explicit classification of an HTTP route's boundary (ADR-003 / ADR-002).
 *
 * Every application route is exactly one of these. A route is classified through
 * the {@see RouteBoundary::DEFAULT_KEY} route default so the classification is
 * inspectable in route configuration and cannot be inferred from path strings.
 */
enum RouteBoundary: string
{
    /**
     * Route default key carrying the boundary classification, e.g.
     * `#[Route('/app', defaults: [RouteBoundary::DEFAULT_KEY => 'tenant_facing'])]`.
     */
    public const string DEFAULT_KEY = '_boundary';

    case TenantFacing = 'tenant_facing';
    case CentralPlatform = 'central_platform';
    case BackOffice = 'back_office';

    public function isTenantFacing(): bool
    {
        return self::TenantFacing === $this;
    }

    public function isBackOffice(): bool
    {
        return self::BackOffice === $this;
    }
}
