<?php

declare(strict_types=1);

namespace App\BackOffice\Audit;

use App\Central\Entity\AuditOutcome;

/**
 * Records audit entries for platform (Back Office) operations (ADR-002).
 *
 * Recording is synchronous and durable — it is part of the operation, never
 * deferred or queued for "later".
 */
interface PlatformOperationAuditor
{
    public function record(AuditContext $context, AuditOutcome $outcome): void;
}
