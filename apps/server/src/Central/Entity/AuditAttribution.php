<?php

declare(strict_types=1);

namespace App\Central\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Attribution for an audited operation: which identity acted, in which boundary,
 * and in which tenant/operation scope (ADR-002, ADR-006).
 *
 * ADR-006 requires that audit records distinguish four facts when they apply:
 * the identity boundary (platform-operator vs tenant-user), the identity
 * reference within that boundary, the selected tenant context, and the
 * platform-operation scope. The same email existing as both an operator and a
 * tenant user must never collapse into one attribution.
 *
 * Embedded into {@see PlatformAuditEntry} with bare column names. The original
 * positional shape (`operatorReference`, `tenantSlug`) is preserved so existing
 * call sites keep working; the named constructors populate the
 * boundary-distinguishing fields consistently.
 */
#[ORM\Embeddable]
final class AuditAttribution
{
    public function __construct(
        #[ORM\Column(length: 255, nullable: true)]
        public readonly ?string $operatorReference,
        #[ORM\Column(length: 63, nullable: true)]
        public readonly ?string $tenantSlug,
        #[ORM\Column(enumType: IdentityBoundary::class, nullable: true)]
        public readonly ?IdentityBoundary $identityBoundary = null,
        #[ORM\Column(length: 255, nullable: true)]
        public readonly ?string $identityReference = null,
        #[ORM\Column(length: 191, nullable: true)]
        public readonly ?string $platformOperationScope = null,
    ) {}

    /**
     * A platform operator acting in the platform/Back Office boundary (ADR-002).
     * `tenantSlug` is set only when the platform operation targets a single tenant.
     */
    public static function forPlatformOperator(
        string $operatorReference,
        ?string $platformOperationScope = null,
        ?string $tenantSlug = null,
    ): self {
        return new self(
            operatorReference: $operatorReference,
            tenantSlug: $tenantSlug,
            identityBoundary: IdentityBoundary::PlatformOperator,
            identityReference: $operatorReference,
            platformOperationScope: $platformOperationScope,
        );
    }

    /**
     * A tenant user acting inside a selected tenant context (ADR-003, ADR-006).
     * Carries no operator reference and no platform-operation scope.
     */
    public static function forTenantUser(string $userReference, string $tenantSlug): self
    {
        return new self(
            operatorReference: null,
            tenantSlug: $tenantSlug,
            identityBoundary: IdentityBoundary::TenantUser,
            identityReference: $userReference,
            platformOperationScope: null,
        );
    }
}
