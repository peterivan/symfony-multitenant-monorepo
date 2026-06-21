<?php

declare(strict_types=1);

namespace App\BackOffice\Operation;

use App\BackOffice\Audit\AuditContext;
use App\BackOffice\Audit\PlatformOperationAuditor;
use App\Central\Entity\AuditOutcome;
use App\Tenant\TenantExecutionScope;

/**
 * Runs a Back Office operation with mandatory, non-skippable audit (ADR-002).
 *
 * Every audited operation class — tenant lifecycle changes, cross-tenant
 * operations, tenant-owned data access/mutation — is performed THROUGH this runner,
 * which is the only sanctioned path. The {@see AuditContext} is a required argument
 * and there is no flag to disable, defer, or skip recording: the auditor is invoked
 * on both the success and the failure path, so the operation cannot be considered
 * complete without an audit entry.
 */
final class AuditedPlatformOperation
{
    public function __construct(
        private readonly PlatformOperationAuditor $auditor,
        private readonly TenantExecutionScope $tenantExecutionScope,
    ) {}

    /**
     * Run a central-context operation (e.g. tenant registry / lifecycle change)
     * with mandatory audit.
     *
     * @template T
     *
     * @param callable():T $operation
     *
     * @return T
     */
    public function run(AuditContext $context, callable $operation): mixed
    {
        try {
            $result = $operation();
        } catch (\Throwable $exception) {
            $this->auditor->record($context, AuditOutcome::Failed);

            throw $exception;
        }

        $this->auditor->record($context, AuditOutcome::Succeeded);

        return $result;
    }

    /**
     * Run an operation that touches a specific tenant's database within a bounded,
     * explicitly established tenant context, with mandatory audit. Tenant context is
     * established only here (explicit intent) and disposed afterwards — including on
     * failure — by {@see TenantExecutionScope}.
     *
     * @template T
     *
     * @param callable():T $operation
     *
     * @return T
     */
    public function runForTenant(AuditContext $context, string $tenantSlug, callable $operation): mixed
    {
        return $this->tenantExecutionScope->run($tenantSlug, fn(): mixed => $this->run($context, $operation));
    }
}
