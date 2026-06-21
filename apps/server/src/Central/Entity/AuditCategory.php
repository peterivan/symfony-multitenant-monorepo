<?php

declare(strict_types=1);

namespace App\Central\Entity;

/**
 * Classes of platform operation that MUST be audited (ADR-002).
 *
 * Audit coverage for every one of these is mandatory and non-skippable.
 */
enum AuditCategory: string
{
    case TenantLifecycleChange = 'tenant_lifecycle_change';
    case CrossTenantOperation = 'cross_tenant_operation';
    case TenantDataAccess = 'tenant_data_access';
    case TenantDataMutation = 'tenant_data_mutation';
}
