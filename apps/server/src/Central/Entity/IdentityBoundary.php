<?php

declare(strict_types=1);

namespace App\Central\Entity;

/**
 * The identity boundary an acting identity belongs to (ADR-006).
 *
 * Platform-operator and tenant-user identities are separate boundaries that are
 * never merged. This enum lets audit attribution record which boundary acted,
 * so an operator and a tenant user who happen to share an attribute (e.g. email)
 * remain distinguishable in the trail.
 */
enum IdentityBoundary: string
{
    case PlatformOperator = 'platform_operator';
    case TenantUser = 'tenant_user';
}
