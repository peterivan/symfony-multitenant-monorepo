<?php

declare(strict_types=1);

namespace App\Central\Entity;

/**
 * Attribution for an audited platform operation (ADR-002): who acted and on which
 * tenant.
 *
 * The operator reference is nullable only until operator authentication lands
 * (ADR-007); the tenant slug is null for central-only or non-single-tenant
 * operations.
 */
final class AuditAttribution
{
    public function __construct(
        public readonly ?string $operatorReference,
        public readonly ?string $tenantSlug,
    ) {}
}
