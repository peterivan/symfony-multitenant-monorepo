<?php

declare(strict_types=1);

namespace App\BackOffice\Audit;

use App\Central\Entity\AuditCategory;

/**
 * Describes the platform operation being audited (ADR-002).
 *
 * Required for every audited operation: the category, a stable operation name, and
 * the platform-operator attribution are mandatory inputs. There is deliberately no
 * "skip audit" flag — an audited operation cannot be performed without supplying
 * this context.
 */
final class AuditContext
{
    /**
     * @param array<string, scalar|array<array-key, scalar|null>|null> $scope operation-specific
     *                                                                        details
     */
    public function __construct(
        public readonly AuditCategory $category,
        public readonly string $operation,
        public readonly ?string $operatorReference,
        public readonly ?string $tenantSlug = null,
        public readonly array $scope = [],
    ) {}
}
