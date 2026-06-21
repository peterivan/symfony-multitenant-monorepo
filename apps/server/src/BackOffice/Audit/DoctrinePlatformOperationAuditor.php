<?php

declare(strict_types=1);

namespace App\BackOffice\Audit;

use App\Central\Entity\AuditAttribution;
use App\Central\Entity\AuditOutcome;
use App\Central\Entity\PlatformAuditEntry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persists audit entries to the CENTRAL database (ADR-002).
 *
 * The injected EntityManager is the default `central` manager: audit is
 * platform-owned metadata. The entry is persisted and flushed immediately so the
 * audit cannot be deferred past the operation it records.
 */
final class DoctrinePlatformOperationAuditor implements PlatformOperationAuditor
{
    public function __construct(
        private readonly EntityManagerInterface $centralEntityManager,
    ) {}

    public function record(AuditContext $context, AuditOutcome $outcome): void
    {
        $entry = new PlatformAuditEntry(
            $context->category,
            $context->operation,
            $outcome,
            new AuditAttribution($context->operatorReference, $context->tenantSlug),
            $context->scope,
        );

        $this->centralEntityManager->persist($entry);
        $this->centralEntityManager->flush();
    }
}
